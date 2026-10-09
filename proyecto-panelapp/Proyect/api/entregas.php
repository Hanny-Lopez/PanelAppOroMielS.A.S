<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

api_ejecutar(static function (): void {
    $metodo = api_metodo(['GET', 'PUT']);
    $usuario = api_requerir_usuario();

    if ($metodo === 'GET') {
        $entregas = leer('entregas');
        if ($usuario['rol'] !== 'ADMIN') {
            $entregas = array_values(array_filter($entregas, static fn (array $entrega): bool => (int) $entrega['cliente_id'] === (int) $usuario['id']));
        }
        usort($entregas, static fn (array $a, array $b): int => (int) $b['id'] <=> (int) $a['id']);
        api_responder($entregas);
    }

    api_requerir_usuario('ADMIN');
    api_requerir_csrf();
    $entrada = api_entrada();
    $id = (int) ($entrada['id'] ?? 0);
    $direccion = trim((string) ($entrada['direccion'] ?? ''));
    $estado = (string) ($entrada['estado'] ?? '');
    $fecha = trim((string) ($entrada['fecha_programada'] ?? ''));
    if ($id < 1 || $direccion === '' || mb_strlen($direccion) > 255) api_error(422, 'Revisa la dirección de entrega.');
    if (!in_array($estado, ['PENDIENTE', 'EN_RUTA', 'ENTREGADA', 'NOVEDAD'], true)) api_error(422, 'Selecciona un estado de entrega válido.');

    transaccion(static function (array &$datos) use ($id, $direccion, $estado, $fecha): void {
        foreach ($datos['entregas'] as &$entrega) {
            if ((int) $entrega['id'] !== $id) continue;
            $entrega['direccion'] = $direccion;
            $entrega['estado'] = $estado;
            $entrega['fecha_programada'] = $fecha === '' ? null : $fecha;
            $entrega['actualizado_en'] = date(DATE_ATOM);
            unset($entrega);
            return;
        }
        unset($entrega);
        api_error(404, 'Esa entrega ya no existe.');
    });
    api_responder(['mensaje' => 'Entrega actualizada.']);
});
