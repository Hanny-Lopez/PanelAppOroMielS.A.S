<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

api_ejecutar(static function (): void {
    $metodo = api_metodo(['GET', 'POST', 'PUT', 'DELETE']);
    $usuario = api_requerir_usuario();

    if ($metodo === 'GET') {
        $pagos = leer('pagos');
        if ($usuario['rol'] !== 'ADMIN') {
            $pagos = array_values(array_filter($pagos, static fn (array $pago): bool => (int) $pago['cliente_id'] === (int) $usuario['id']));
        }
        usort($pagos, static fn (array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);
        api_responder($pagos);
    }

    api_requerir_csrf();
    $entrada = api_entrada();

    if ($metodo === 'POST') {
        if ($usuario['rol'] !== 'CLIENTE') api_error(403, 'Solo los clientes pueden registrar pagos.');
        $pedidoId = (int) ($entrada['pedido_id'] ?? 0);
        $metodoPago = (string) ($entrada['metodo'] ?? '');
        $referencia = trim((string) ($entrada['referencia'] ?? ''));
        if (!in_array($metodoPago, ['EFECTIVO', 'TRANSFERENCIA', 'PSE', 'TARJETA', 'CONTRAENTREGA'], true)) {
            api_error(422, 'Selecciona un método de pago válido.');
        }
        if (mb_strlen($referencia) > 120) api_error(422, 'La referencia no puede superar 120 caracteres.');

        $pago = transaccion(static function (array &$datos) use ($pedidoId, $metodoPago, $referencia, $usuario): array {
            $pedido = null;
            foreach ($datos['pedidos'] as $registro) {
                if ((int) $registro['id'] === $pedidoId && (int) $registro['cliente_id'] === (int) $usuario['id']) $pedido = $registro;
            }
            if ($pedido === null || $pedido['estado'] === 'CANCELADO') api_error(404, 'Ese pedido no está disponible para registrar el pago.');

            $nuevoPago = [
                'id' => siguiente_id($datos['pagos']),
                'pedido_id' => $pedidoId,
                'cliente_id' => (int) $usuario['id'],
                'cliente' => (string) $usuario['nombre'],
                'monto' => (float) $pedido['total'],
                'metodo' => $metodoPago,
                'referencia' => $referencia,
                'estado' => 'PENDIENTE',
                'fecha' => date(DATE_ATOM),
            ];
            $datos['pagos'][] = $nuevoPago;
            return $nuevoPago;
        });
        api_responder($pago, 201);
    }

    api_requerir_usuario('ADMIN');
    $entrada = api_entrada();
    $id = (int) ($entrada['id'] ?? $_GET['id'] ?? 0);

    if ($metodo === 'DELETE') {
        transaccion(static function (array &$datos) use ($id): void {
            foreach ($datos['pagos'] as $indice => $pago) {
                if ((int) $pago['id'] === $id) {
                    if ($pago['estado'] === 'APROBADO') api_error(409, 'No se puede eliminar un pago aprobado.');
                    unset($datos['pagos'][$indice]);
                    $datos['pagos'] = array_values($datos['pagos']);
                    return;
                }
            }
            api_error(404, 'Ese registro de pago ya no existe.');
        });
        api_responder(['mensaje' => 'Registro de pago eliminado.']);
    }

    $estado = (string) ($entrada['estado'] ?? '');
    if (!in_array($estado, ['PENDIENTE', 'APROBADO', 'RECHAZADO', 'REEMBOLSADO'], true)) {
        api_error(422, 'Selecciona un estado válido para el pago.');
    }
    transaccion(static function (array &$datos) use ($id, $estado): void {
        foreach ($datos['pagos'] as &$pago) {
            if ((int) $pago['id'] === $id) {
                $pago['estado'] = $estado;
                $pago['actualizado_en'] = date(DATE_ATOM);
                unset($pago);
                return;
            }
        }
        unset($pago);
        api_error(404, 'Ese registro de pago ya no existe.');
    });
    api_responder(['mensaje' => 'Estado del pago actualizado.']);
});
