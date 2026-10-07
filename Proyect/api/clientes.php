<?php
require __DIR__.'/config.php'; require_admin(); require_method(['GET']);
$q=db()->query("SELECT c.id,u.nombre,u.correo,c.telefono,c.direccion,c.estado,COUNT(p.id) AS cantidad_pedidos FROM clientes c JOIN usuarios u ON u.id=c.usuario_id LEFT JOIN pedidos p ON p.cliente_id=c.id GROUP BY c.id,u.nombre,u.correo,c.telefono,c.direccion,c.estado ORDER BY u.nombre");json_response(true,$q->fetchAll(),'Clientes consultados.');
