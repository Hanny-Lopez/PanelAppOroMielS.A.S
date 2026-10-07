<?php
require __DIR__.'/config.php';$user=require_login();$method=$_SERVER['REQUEST_METHOD']??'';
if($method==='GET'){
 if($user['rol']==='ADMIN')$rows=db()->query('SELECT pa.*,p.cliente_id,u.nombre AS cliente FROM pagos pa JOIN pedidos p ON p.id=pa.pedido_id JOIN clientes c ON c.id=p.cliente_id JOIN usuarios u ON u.id=c.usuario_id ORDER BY pa.fecha DESC')->fetchAll();
 else{$q=db()->prepare('SELECT pa.* FROM pagos pa JOIN pedidos p ON p.id=pa.pedido_id JOIN clientes c ON c.id=p.cliente_id WHERE c.usuario_id=? ORDER BY pa.fecha DESC');$q->execute([$user['id']]);$rows=$q->fetchAll();}
 json_response(true,$rows,'Pagos consultados.');
}
if($method==='POST'){
 require_csrf();
 if($user['rol']!=='CLIENTE')json_response(false,null,'Solo los clientes pueden registrar solicitudes de pago.',403);$data=body();$orderId=filter_var($data['pedido_id']??null,FILTER_VALIDATE_INT);$methodName=$data['metodo']??'';$methods=['EFECTIVO','TRANSFERENCIA','PSE','TARJETA','CONTRAENTREGA'];if(!$orderId||!in_array($methodName,$methods,true))json_response(false,null,'Indica un pedido y un método válido.',400);
 $q=db()->prepare("SELECT p.id,p.total FROM pedidos p JOIN clientes c ON c.id=p.cliente_id WHERE p.id=? AND c.usuario_id=? AND p.estado<>'CANCELADO'");$q->execute([$orderId,$user['id']]);$order=$q->fetch();if(!$order)json_response(false,null,'Pedido no encontrado.',404);$q=db()->prepare("INSERT INTO pagos(pedido_id,monto,metodo,estado) VALUES(?,?,?,'PENDIENTE')");$q->execute([$orderId,$order['total'],$methodName]);json_response(true,['id'=>(int)db()->lastInsertId()],'Solicitud de pago registrada. No se procesó ningún cobro.',201);
}
if($method==='PUT'){
 require_admin();require_csrf();$data=body();$id=filter_var($data['id']??null,FILTER_VALIDATE_INT);$state=$data['estado']??'';if(!$id||!in_array($state,['PENDIENTE','APROBADO','RECHAZADO','REEMBOLSADO'],true))json_response(false,null,'Indica un pago y estado válidos.',400);$q=db()->prepare('UPDATE pagos SET estado=? WHERE id=?');$q->execute([$state,$id]);if(!$q->rowCount())json_response(false,null,'Pago no encontrado o sin cambios.',404);json_response(true,null,'Estado del pago actualizado.');
}
require_method(['GET','POST','PUT']);
