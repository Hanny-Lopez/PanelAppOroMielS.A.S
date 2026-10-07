<?php
require __DIR__.'/config.php';$user=require_login();if($user['rol']!=='ADMIN')json_response(false,null,'No tienes permisos para consultar este resumen.',403);$pdo=db();$data=[];
foreach(['pedidos'=>'SELECT COUNT(*) FROM pedidos','clientes'=>'SELECT COUNT(*) FROM clientes','productos'=>"SELECT COUNT(*) FROM productos WHERE estado='ACTIVO'",'pagos_pendientes'=>"SELECT COUNT(*) FROM pagos WHERE estado='PENDIENTE'"] as $key=>$sql)$data[$key]=(int)$pdo->query($sql)->fetchColumn();json_response(true,$data,'Resumen consultado.');
