<?php
declare(strict_types=1);

// Crear el primer administrador desde una terminal; nunca exponer este script por HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../api/almacen.php';

fwrite(STDOUT, "Crear cuenta administradora de PanelApp\nCorreo: ");
$correo = mb_strtolower(trim((string) fgets(STDIN)));
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Correo no válido.\n");
    exit(1);
}

fwrite(STDOUT, "Nombre: ");
$nombre = trim((string) fgets(STDIN));
if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 120) {
    fwrite(STDERR, "Nombre no válido.\n");
    exit(1);
}

fwrite(STDOUT, "Contraseña (mínimo 12 caracteres): ");
$contrasena = rtrim((string) fgets(STDIN), "\r\n");
if (mb_strlen($contrasena) < 12) {
    fwrite(STDERR, "La contraseña debe tener al menos 12 caracteres.\n");
    exit(1);
}

try {
    transaccion(static function (array &$datos) use ($correo, $nombre, $contrasena): void {
        foreach ($datos['usuarios'] as $usuario) {
            if (mb_strtolower((string) $usuario['correo']) === $correo) {
                throw new RuntimeException('Ya existe una cuenta con ese correo.');
            }
        }

        $datos['usuarios'][] = [
            'id' => siguiente_id($datos['usuarios']),
            'nombre' => $nombre,
            'correo' => $correo,
            'clave_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
            'rol' => 'ADMIN',
            'telefono' => '',
            'direccion' => '',
            'estado' => 'ACTIVO',
            'creado_en' => date(DATE_ATOM),
        ];
    });
    fwrite(STDOUT, "Administrador creado. Ya puede ingresar desde login.html?rol=ADMIN\n");
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
