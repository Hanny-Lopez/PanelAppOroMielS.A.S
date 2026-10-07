<?php
require __DIR__.'/config.php'; $method=$_SERVER['REQUEST_METHOD']??'';
if($method==='GET')json_response(true,['user'=>current_user(),'csrf'=>$_SESSION['csrf']??null],'Sesión consultada.');
if($method==='DELETE'){require_login();require_csrf();$_SESSION=[]; if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);} session_destroy();json_response(true,null,'Sesión cerrada correctamente.');}
require_method(['POST']); $data=body(); $action=$data['action']??'login';
$email=filter_var(trim((string)($data['correo']??'')),FILTER_VALIDATE_EMAIL); $password=(string)($data['password']??'');
if(!$email||strlen($email)>190)json_response(false,null,'Ingresa un correo electrónico válido.',400);
if($action==='register'){
 if(mb_strlen($password)<8)json_response(false,null,'La contraseña debe tener al menos 8 caracteres.',400);
 $name=trim((string)($data['nombre']??'')); if($name===''||mb_strlen($name)>120)json_response(false,null,'Ingresa un nombre válido.',400);
 $pdo=db(); try{$pdo->beginTransaction();$q=$pdo->prepare("INSERT INTO usuarios(rol_id,nombre,correo,password_hash) VALUES((SELECT id FROM roles WHERE nombre='CLIENTE'),?,?,?)");$q->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);$uid=(int)$pdo->lastInsertId();$pdo->prepare('INSERT INTO clientes(usuario_id) VALUES(?)')->execute([$uid]);$pdo->commit();json_response(true,null,'Cuenta creada. Ya puedes iniciar sesión.',201);}catch(PDOException $e){if($pdo->inTransaction())$pdo->rollBack();if($e->getCode()==='23000')json_response(false,null,'Ya existe una cuenta con ese correo.',409);throw $e;}
}
if($action!=='login')json_response(false,null,'La operación solicitada no es válida.',400);
if($password==='')json_response(false,null,'Ingresa tu contraseña.',400);
$q=db()->prepare('SELECT u.id,u.nombre,u.correo,u.password_hash,r.nombre AS rol FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.correo=? AND u.activo=1 LIMIT 1');$q->execute([$email]);$record=$q->fetch();
if(!$record||!password_verify($password,$record['password_hash']))json_response(false,null,'Correo o contraseña incorrectos.',401);
session_regenerate_id(true);unset($record['password_hash']);$_SESSION['user']=$record;$_SESSION['csrf']=bin2hex(random_bytes(32));json_response(true,['user'=>$record,'csrf'=>$_SESSION['csrf']],'Inicio de sesión correcto.');
