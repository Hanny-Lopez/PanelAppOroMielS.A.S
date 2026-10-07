<?php
// Run only from a terminal: php tools/crear-admin.php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../api/config.php';
fwrite(STDOUT,"Configuración del primer administrador\nCorreo: ");$email=trim((string)fgets(STDIN));
if(!filter_var($email,FILTER_VALIDATE_EMAIL)){fwrite(STDERR,"Correo no válido.\n");exit(1);}
fwrite(STDOUT,"Nombre: ");$name=trim((string)fgets(STDIN));if($name===''||mb_strlen($name)>120){fwrite(STDERR,"Nombre no válido.\n");exit(1);}
fwrite(STDOUT,"Contraseña (mínimo 8 caracteres): ");$password=rtrim((string)fgets(STDIN),"\r\n");if(mb_strlen($password)<8){fwrite(STDERR,"La contraseña debe tener al menos 8 caracteres.\n");exit(1);}
$pdo=db();$pdo->exec("INSERT IGNORE INTO roles(nombre) VALUES('ADMIN'),('CLIENTE')");$q=$pdo->prepare('SELECT id FROM roles WHERE nombre=\'ADMIN\'');$q->execute();$role=(int)$q->fetchColumn();
try{$q=$pdo->prepare('INSERT INTO usuarios(rol_id,nombre,correo,password_hash) VALUES(?,?,?,?)');$q->execute([$role,$name,$email,password_hash($password,PASSWORD_DEFAULT)]);fwrite(STDOUT,"Administrador creado correctamente.\n");}catch(PDOException $e){if($e->getCode()==='23000'){fwrite(STDERR,"Ya existe una cuenta con ese correo.\n");exit(1);}throw $e;}
