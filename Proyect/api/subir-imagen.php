<?php
require __DIR__.'/config.php';require_admin();require_method(['POST']);require_csrf();
if(!isset($_FILES['imagen'])||$_FILES['imagen']['error']!==UPLOAD_ERR_OK)json_response(false,null,'Selecciona una imagen para subir.',400);
$file=$_FILES['imagen'];if($file['size']>5*1024*1024)json_response(false,null,'La imagen no puede superar 5 MB.',400);
$finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file($file['tmp_name']);$extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];if(!isset($extensions[$mime])||!getimagesize($file['tmp_name']))json_response(false,null,'Formato no permitido. Usa JPG, PNG o WebP.',400);
$directory=dirname(__DIR__).'/uploads/productos';if(!is_dir($directory)&&!mkdir($directory,0755,true))json_response(false,null,'No fue posible guardar la imagen.',500);
$filename=bin2hex(random_bytes(16)).'.'.$extensions[$mime];if(!move_uploaded_file($file['tmp_name'],$directory.'/'.$filename))json_response(false,null,'No fue posible guardar la imagen.',500);
json_response(true,['imagen'=>'uploads/productos/'.$filename],'Imagen cargada correctamente.',201);
