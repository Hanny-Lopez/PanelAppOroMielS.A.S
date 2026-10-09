<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

api_ejecutar(static function (): void {
    api_metodo(['GET']);
    api_requerir_usuario('ADMIN');

    $pedidos = leer('pedidos');
    $pagos = leer('pagos');
    $productos = leer('productos');
    $usuarios = leer('usuarios');
    $pagosAprobados = array_values(array_filter($pagos, static function (array $pago) use ($pedidos): bool {
        if (($pago['estado'] ?? '') !== 'APROBADO') return false;
        foreach ($pedidos as $pedido) {
            if ((int) $pedido['id'] === (int) $pago['pedido_id']) return ($pedido['estado'] ?? '') !== 'CANCELADO';
        }
        return false;
    }));

    $pedidosPagados = array_unique(array_map(static fn (array $pago): int => (int) $pago['pedido_id'], $pagosAprobados));
    $unidades = 0;
    foreach ($pedidos as $pedido) {
        if (!in_array((int) $pedido['id'], $pedidosPagados, true)) continue;
        foreach ($pedido['detalle'] ?? [] as $linea) $unidades += (int) ($linea['cantidad'] ?? 0);
    }

    api_responder([
        'pedidos' => count($pedidos),
        'clientes' => count(array_filter($usuarios, static fn (array $usuario): bool => ($usuario['rol'] ?? '') === 'CLIENTE')),
        'productos' => count(array_filter($productos, static fn (array $producto): bool => ($producto['estado'] ?? '') === 'ACTIVO')),
        'pagos_pendientes' => count(array_filter($pagos, static fn (array $pago): bool => ($pago['estado'] ?? '') === 'PENDIENTE')),
        'ventas' => array_sum(array_map(static fn (array $pago): float => (float) ($pago['monto'] ?? 0), $pagosAprobados)),
        'ventas_confirmadas' => count($pagosAprobados),
        'unidades' => $unidades,
    ]);
});
