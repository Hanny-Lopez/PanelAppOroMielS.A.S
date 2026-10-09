<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

api_ejecutar(static function (): void {
    $metodo = api_metodo(['GET', 'POST', 'PUT', 'DELETE']);
    $usuario = api_usuario_actual();
    $id = api_id_desde_ruta();

    if ($metodo === 'GET') {
        $productos = leer('productos');
        if (($usuario['rol'] ?? null) !== 'ADMIN') {
            $productos = array_values(array_filter($productos, static fn (array $producto): bool => ($producto['estado'] ?? '') === 'ACTIVO'));
            $productos = array_map(static function (array $producto): array {
                $producto['disponible'] = (int) ($producto['stock'] ?? 0) > 0;
                unset($producto['stock']);
                return $producto;
            }, $productos);
        }
        usort($productos, static fn (array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);

        if ($id > 0) {
            foreach ($productos as $producto) {
                if ((int) $producto['id'] === $id) {
                    api_responder($producto);
                }
            }
            api_error(404, 'Ese producto ya no existe.');
        }

        api_responder($productos);
    }

    api_requerir_usuario('ADMIN');
    api_requerir_csrf();

    if ($metodo === 'DELETE') {
        transaccion(static function (array &$datos) use ($id): void {
            foreach ($datos['productos'] as &$producto) {
                if ((int) $producto['id'] === $id) {
                    $producto['estado'] = 'INACTIVO';
                    unset($producto);
                    return;
                }
            }
            unset($producto);
            api_error(404, 'Ese producto ya no existe.');
        });
        api_responder(['mensaje' => 'Producto desactivado.']);
    }

    $entrada = api_entrada();
    $nombre = trim((string) ($entrada['nombre'] ?? ''));
    $descripcion = trim((string) ($entrada['descripcion'] ?? ''));
    $precio = filter_var($entrada['precio'] ?? null, FILTER_VALIDATE_FLOAT);
    $stock = filter_var($entrada['stock'] ?? null, FILTER_VALIDATE_INT);
    $estado = (string) ($entrada['estado'] ?? 'ACTIVO');
    $imagen = trim((string) ($entrada['imagen'] ?? ''));
    $errores = [];

    if ($nombre === '' || mb_strlen($nombre) > 140) $errores['nombre'] = 'El nombre es obligatorio (máximo 140 caracteres).';
    if (mb_strlen($descripcion) > 500) $errores['descripcion'] = 'La descripción no puede superar 500 caracteres.';
    if ($precio === false || $precio < 0) $errores['precio'] = 'Ingresa un precio igual o mayor que cero.';
    if ($stock === false || $stock < 0) $errores['stock'] = 'Ingresa unidades enteras, iguales o mayores que cero.';
    if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) $errores['estado'] = 'Elige un estado válido.';
    if ($imagen !== '' && !preg_match('~^uploads/productos/[a-f0-9]{32}\.(jpg|png|webp)$~', $imagen)) {
        $errores['imagen'] = 'La ruta de imagen no es válida.';
    }
    if ($errores !== []) api_error(422, 'Revisa los campos del producto.', $errores);

    $productoGuardado = transaccion(static function (array &$datos) use ($metodo, $id, $nombre, $descripcion, $precio, $stock, $estado, $imagen): array {
        if ($metodo === 'POST') {
            $producto = [
                'id' => siguiente_id($datos['productos']),
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'precio' => (float) $precio,
                'stock' => (int) $stock,
                'imagen' => $imagen,
                'estado' => $estado,
                'creado_en' => date(DATE_ATOM),
                'actualizado_en' => date(DATE_ATOM),
            ];
            $datos['productos'][] = $producto;
            return $producto;
        }

        foreach ($datos['productos'] as &$producto) {
            if ((int) $producto['id'] === $id) {
                $productoActualizado = array_merge($producto, [
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'precio' => (float) $precio,
                    'stock' => (int) $stock,
                    'estado' => $estado,
                    'actualizado_en' => date(DATE_ATOM),
                ]);
                if ($imagen !== '') $productoActualizado['imagen'] = $imagen;
                $producto = $productoActualizado;
                unset($producto);
                return $productoActualizado;
            }
        }
        unset($producto);
        api_error(404, 'Ese producto ya no existe.');
    });

    api_responder($productoGuardado, $metodo === 'POST' ? 201 : 200);
});
