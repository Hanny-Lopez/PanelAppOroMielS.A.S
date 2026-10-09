<?php
declare(strict_types=1);

// En el servidor de desarrollo de PHP, no publiques jamás la carpeta JSON.
$ruta = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
$rutaNormalizada = preg_replace('~/+~', '/', str_replace('\\', '/', $ruta)) ?: '/';

if (str_contains($rutaNormalizada, '..')
    || preg_match('~^/(data|api/(almacen|sesion|_common)\.php)(/|$)~i', $rutaNormalizada)) {
    http_response_code(404);
    exit;
}

$archivo = __DIR__ . $rutaNormalizada;
if ($rutaNormalizada !== '/' && is_file($archivo)) {
    return false;
}

if ($rutaNormalizada === '/') {
    return false;
}

http_response_code(404);
echo 'Página no encontrada.';
