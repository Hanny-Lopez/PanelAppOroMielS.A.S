<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

api_ejecutar(static function (): void {
    api_metodo(['GET']);
    api_requerir_usuario('ADMIN');
    $pedidos = leer('pedidos');
    $clientes = array_values(array_filter(leer('usuarios'), static fn (array $usuario): bool => ($usuario['rol'] ?? '') === 'CLIENTE'));

    $respuesta = array_map(static function (array $cliente) use ($pedidos): array {
        $cliente = api_usuario_publico($cliente);
        $cliente['cantidad_pedidos'] = count(array_filter(
            $pedidos,
            static fn (array $pedido): bool => (int) $pedido['cliente_id'] === (int) $cliente['id']
        ));
        return $cliente;
    }, $clientes);

    api_responder($respuesta);
});
