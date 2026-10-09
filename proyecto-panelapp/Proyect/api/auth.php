<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

api_ejecutar(static function (): void {
    $metodo = api_metodo(['GET', 'POST', 'DELETE']);

    if ($metodo === 'GET') {
        $usuario = api_usuario_actual();
        api_responder([
            'user' => $usuario === null ? null : api_usuario_publico($usuario),
            'csrf' => $_SESSION['csrf'],
        ]);
    }

    api_requerir_csrf();

    if ($metodo === 'DELETE') {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parametros = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $parametros['path'],
                'domain' => $parametros['domain'],
                'secure' => $parametros['secure'],
                'httponly' => $parametros['httponly'],
                'samesite' => $parametros['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
        api_responder(['mensaje' => 'Sesión cerrada.']);
    }

    $entrada = api_entrada();
    $accion = (string) ($entrada['action'] ?? 'login');
    $correo = mb_strtolower(trim((string) ($entrada['correo'] ?? '')));
    $contrasena = (string) ($entrada['password'] ?? '');

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        api_error(422, 'Ingresa un correo electrónico válido.', ['correo' => 'Revisa el formato del correo.']);
    }

    if ($accion === 'register') {
        $nombre = trim((string) ($entrada['nombre'] ?? ''));
        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 120) {
            api_error(422, 'Revisa los datos del registro.', ['nombre' => 'Escribe un nombre entre 2 y 120 caracteres.']);
        }
        if (mb_strlen($contrasena) < 8) {
            api_error(422, 'Revisa los datos del registro.', ['password' => 'La contraseña debe tener al menos 8 caracteres.']);
        }

        transaccion(static function (array &$datos) use ($correo, $nombre, $contrasena): void {
            foreach ($datos['usuarios'] as $usuario) {
                if (mb_strtolower((string) $usuario['correo']) === $correo) {
                    api_error(409, 'Ya existe una cuenta con ese correo.');
                }
            }

            $datos['usuarios'][] = [
                'id' => siguiente_id($datos['usuarios']),
                'nombre' => $nombre,
                'correo' => $correo,
                'clave_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
                'rol' => 'CLIENTE',
                'telefono' => '',
                'direccion' => '',
                'estado' => 'ACTIVO',
                'creado_en' => date(DATE_ATOM),
            ];
        });

        api_responder(['mensaje' => 'Cuenta de cliente creada.'], 201);
    }

    if ($accion !== 'login') {
        api_error(422, 'La acción de acceso no es válida.');
    }

    $usuario = null;
    foreach (leer('usuarios') as $registro) {
        if (mb_strtolower((string) ($registro['correo'] ?? '')) === $correo) {
            $usuario = $registro;
            break;
        }
    }

    if ($usuario === null || ($usuario['estado'] ?? 'ACTIVO') !== 'ACTIVO'
        || !password_verify($contrasena, (string) ($usuario['clave_hash'] ?? ''))) {
        api_error(401, 'Correo o contraseña incorrectos.');
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    api_responder([
        'user' => api_usuario_publico($usuario),
        'csrf' => $_SESSION['csrf'],
    ]);
});
