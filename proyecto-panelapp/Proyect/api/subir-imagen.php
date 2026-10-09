<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

api_ejecutar(static function (): void {
    api_metodo(['POST']);
    api_requerir_usuario('ADMIN');
    api_requerir_csrf();

    $archivo = $_FILES['imagen'] ?? null;
    if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        api_error(422, 'Selecciona una imagen para cargar.');
    }
    if ((int) $archivo['size'] > 2 * 1024 * 1024) {
        api_error(422, 'La imagen no puede superar 2 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string) $archivo['tmp_name']);
    $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensiones[$mime]) || @getimagesize((string) $archivo['tmp_name']) === false) {
        api_error(422, 'Usa una imagen JPG, PNG o WebP válida.');
    }

    $directorio = __DIR__ . '/../uploads/productos';
    if (!is_dir($directorio) && !mkdir($directorio, 0750, true) && !is_dir($directorio)) {
        throw new RuntimeException('No se pudo crear la carpeta de imágenes.');
    }

    $nombre = bin2hex(random_bytes(16)) . '.' . $extensiones[$mime];
    if (!move_uploaded_file((string) $archivo['tmp_name'], $directorio . '/' . $nombre)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }

    api_responder(['imagen' => 'uploads/productos/' . $nombre], 201);
});
