<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

api_ejecutar(static function (): void {
    $metodo = api_metodo(['GET', 'POST', 'PUT', 'DELETE']);
    $usuario = api_requerir_usuario();
    $id = api_id_desde_ruta();

    if ($metodo === 'GET') {
        $pedidos = leer('pedidos');
        if ($usuario['rol'] !== 'ADMIN') {
            $pedidos = array_values(array_filter($pedidos, static fn (array $pedido): bool => (int) $pedido['cliente_id'] === (int) $usuario['id']));
        }
        usort($pedidos, static fn (array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);
        if ($id > 0) {
            foreach ($pedidos as $pedido) {
                if ((int) $pedido['id'] === $id) api_responder($pedido);
            }
            api_error(404, 'Ese pedido ya no existe.');
        }
        api_responder($pedidos);
    }

    api_requerir_csrf();
    $entrada = api_entrada();

    if ($metodo === 'POST') {
        if ($usuario['rol'] !== 'CLIENTE') api_error(403, 'Solo los clientes pueden crear pedidos.');
        $items = $entrada['items'] ?? [];
        $direccion = trim((string) ($entrada['direccion'] ?? ''));
        if (!is_array($items) || $items === [] || $direccion === '' || mb_strlen($direccion) > 255) {
            api_error(422, 'Agrega productos y una dirección válida para continuar.');
        }

        $pedido = transaccion(static function (array &$datos) use ($items, $direccion, $usuario): array {
            $detalle = [];
            $total = 0.0;
            foreach ($items as $item) {
                $productoId = (int) ($item['producto_id'] ?? 0);
                $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT);
                if ($productoId < 1 || $cantidad === false || $cantidad < 1 || $cantidad > 10000) {
                    api_error(422, 'Revisa las cantidades de los productos.');
                }

                $indice = null;
                foreach ($datos['productos'] as $posicion => $producto) {
                    if ((int) $producto['id'] === $productoId && ($producto['estado'] ?? '') === 'ACTIVO') {
                        $indice = $posicion;
                        break;
                    }
                }
                if ($indice === null) api_error(404, 'Uno de los productos seleccionados ya no está disponible.');

                $producto = &$datos['productos'][$indice];
                if ((int) $producto['stock'] < $cantidad) {
                    api_error(409, 'No hay unidades suficientes de ' . $producto['nombre'] . '. Actualiza el carrito.');
                }
                $precio = (float) $producto['precio'];
                $subtotal = $precio * $cantidad;
                $producto['stock'] -= $cantidad;
                unset($producto);

                $total += $subtotal;
                $detalle[] = [
                    'producto_id' => $productoId,
                    'producto' => (string) $datos['productos'][$indice]['nombre'],
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $subtotal,
                ];
            }

            $pedidoId = siguiente_id($datos['pedidos']);
            $nuevoPedido = [
                'id' => $pedidoId,
                'cliente_id' => (int) $usuario['id'],
                'cliente' => (string) $usuario['nombre'],
                'total' => $total,
                'estado' => 'PENDIENTE',
                'fecha' => date(DATE_ATOM),
                'direccion' => $direccion,
                'detalle' => $detalle,
            ];
            $datos['pedidos'][] = $nuevoPedido;
            $datos['entregas'][] = [
                'id' => siguiente_id($datos['entregas']),
                'pedido_id' => $pedidoId,
                'cliente_id' => (int) $usuario['id'],
                'cliente' => (string) $usuario['nombre'],
                'direccion' => $direccion,
                'estado' => 'PENDIENTE',
                'fecha_programada' => null,
                'observaciones' => '',
            ];
            return $nuevoPedido;
        });

        api_responder($pedido, 201);
    }

    if ($metodo === 'DELETE') {
        transaccion(static function (array &$datos) use ($id, $usuario): void {
            foreach ($datos['pedidos'] as $indice => $pedido) {
                if ((int) $pedido['id'] !== $id) continue;
                if ($usuario['rol'] !== 'ADMIN' && (int) $pedido['cliente_id'] !== (int) $usuario['id']) {
                    api_error(403, 'No puedes modificar pedidos de otra persona.');
                }

                if ($usuario['rol'] === 'ADMIN') {
                    if ($pedido['estado'] !== 'CANCELADO') api_error(409, 'Solo se pueden eliminar pedidos cancelados.');
                    unset($datos['pedidos'][$indice]);
                    $datos['pedidos'] = array_values($datos['pedidos']);
                    $datos['entregas'] = array_values(array_filter($datos['entregas'], static fn (array $entrega): bool => (int) $entrega['pedido_id'] !== $id));
                    return;
                }

                if ($pedido['estado'] !== 'PENDIENTE') api_error(409, 'Solo puedes cancelar un pedido pendiente.');
                foreach ($pedido['detalle'] as $linea) {
                    foreach ($datos['productos'] as &$producto) {
                        if ((int) $producto['id'] === (int) $linea['producto_id']) {
                            $producto['stock'] += (int) $linea['cantidad'];
                            break;
                        }
                    }
                    unset($producto);
                }
                $datos['pedidos'][$indice]['estado'] = 'CANCELADO';
                foreach ($datos['entregas'] as &$entrega) {
                    if ((int) $entrega['pedido_id'] === $id) $entrega['estado'] = 'NOVEDAD';
                }
                unset($entrega);
                return;
            }
            api_error(404, 'Ese pedido ya no existe.');
        });
        api_responder(['mensaje' => $usuario['rol'] === 'ADMIN' ? 'Pedido eliminado.' : 'Pedido cancelado y unidades devueltas al inventario.']);
    }

    // PUT: el cliente edita dirección mientras está pendiente; el admin cambia estados.
    if ($usuario['rol'] === 'CLIENTE') {
        $direccion = trim((string) ($entrada['direccion'] ?? ''));
        if ($direccion === '' || mb_strlen($direccion) > 255) api_error(422, 'Escribe una dirección válida.');
        transaccion(static function (array &$datos) use ($id, $usuario, $direccion): void {
            foreach ($datos['pedidos'] as &$pedido) {
                if ((int) $pedido['id'] !== $id) continue;
                if ((int) $pedido['cliente_id'] !== (int) $usuario['id']) api_error(403, 'No puedes modificar pedidos de otra persona.');
                if ($pedido['estado'] !== 'PENDIENTE') api_error(409, 'Solo puedes editar la dirección de un pedido pendiente.');
                $pedido['direccion'] = $direccion;
                foreach ($datos['entregas'] as &$entrega) {
                    if ((int) $entrega['pedido_id'] === $id) $entrega['direccion'] = $direccion;
                }
                unset($entrega, $pedido);
                return;
            }
            unset($pedido);
            api_error(404, 'Ese pedido ya no existe.');
        });
        api_responder(['mensaje' => 'Dirección del pedido actualizada.']);
    }

    api_requerir_usuario('ADMIN');
    $estado = (string) ($entrada['estado'] ?? '');
    $estados = ['PENDIENTE', 'CONFIRMADO', 'EN_PREPARACION', 'ENVIADO', 'ENTREGADO', 'CANCELADO'];
    if (!in_array($estado, $estados, true)) api_error(422, 'Selecciona un estado válido para el pedido.');

    transaccion(static function (array &$datos) use ($id, $estado): void {
        $indice = null;
        foreach ($datos['pedidos'] as $posicion => $pedido) {
            if ((int) $pedido['id'] === $id) $indice = $posicion;
        }
        if ($indice === null) api_error(404, 'Ese pedido ya no existe.');

        $estadoAnterior = $datos['pedidos'][$indice]['estado'];
        if ($estadoAnterior === 'CANCELADO' && $estado !== 'CANCELADO') {
            api_error(409, 'Un pedido cancelado no se puede reactivar.');
        }

        if (in_array($estado, ['ENVIADO', 'ENTREGADO'], true)) {
            $pagoAprobado = false;
            foreach ($datos['pagos'] as $pago) {
                if ((int) $pago['pedido_id'] === $id && $pago['estado'] === 'APROBADO') $pagoAprobado = true;
            }
            if (!$pagoAprobado) api_error(409, 'Confirma el pago antes de avanzar el pedido a entrega.');
        }

        if ($estado === 'CANCELADO' && $estadoAnterior !== 'CANCELADO') {
            foreach ($datos['pedidos'][$indice]['detalle'] as $linea) {
                foreach ($datos['productos'] as &$producto) {
                    if ((int) $producto['id'] === (int) $linea['producto_id']) {
                        $producto['stock'] += (int) $linea['cantidad'];
                        break;
                    }
                }
                unset($producto);
            }
        }

        $datos['pedidos'][$indice]['estado'] = $estado;
        foreach ($datos['entregas'] as &$entrega) {
            if ((int) $entrega['pedido_id'] !== $id) continue;
            if ($estado === 'ENVIADO') $entrega['estado'] = 'EN_RUTA';
            elseif ($estado === 'ENTREGADO') $entrega['estado'] = 'ENTREGADA';
            elseif ($estado === 'CANCELADO') $entrega['estado'] = 'NOVEDAD';
        }
        unset($entrega);
    });

    api_responder(['mensaje' => 'Estado del pedido actualizado.']);
});
