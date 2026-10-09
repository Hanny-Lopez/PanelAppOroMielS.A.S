<?php
declare(strict_types=1);

require_once __DIR__ . '/_common.php';

api_ejecutar(static function (): void {
    $metodo = api_metodo(['GET', 'POST', 'PUT', 'DELETE']);
    $usuarioActual = api_requerir_usuario();
    $id = api_id_desde_ruta();

    if ($metodo === 'GET') {
        if ($usuarioActual['rol'] === 'ADMIN') {
            api_responder(array_map('api_usuario_publico', leer('usuarios')));
        }
        api_responder(api_usuario_publico($usuarioActual));
    }

    api_requerir_usuario('ADMIN');
    api_requerir_csrf();
    $entrada = api_entrada();

    if ($metodo === 'POST') {
        $nombre = trim((string) ($entrada['nombre'] ?? ''));
        $correo = mb_strtolower(trim((string) ($entrada['correo'] ?? '')));
        $contrasena = (string) ($entrada['password'] ?? '');
        $rol = (string) ($entrada['rol'] ?? 'CLIENTE');
        if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 120 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            api_error(422, 'Revisa el nombre y correo del usuario.');
        }
        if (mb_strlen($contrasena) < 12 || !in_array($rol, ['ADMIN', 'CLIENTE'], true)) {
            api_error(422, 'La contraseña debe tener 12 caracteres y el rol debe ser válido.');
        }
        $nuevoUsuario = transaccion(static function (array &$datos) use ($nombre, $correo, $contrasena, $rol): array {
            foreach ($datos['usuarios'] as $usuario) {
                if (mb_strtolower((string) $usuario['correo']) === $correo) api_error(409, 'Ya existe una cuenta con ese correo.');
            }
            $registro = [
                'id' => siguiente_id($datos['usuarios']),
                'nombre' => $nombre,
                'correo' => $correo,
                'clave_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
                'rol' => $rol,
                'telefono' => '',
                'direccion' => '',
                'estado' => 'ACTIVO',
                'creado_en' => date(DATE_ATOM),
            ];
            $datos['usuarios'][] = $registro;
            return api_usuario_publico($registro);
        });
        api_responder($nuevoUsuario, 201);
    }

    if ($id < 1) api_error(422, 'Indica un usuario válido.');
    if ($metodo === 'DELETE') {
        if ($id === (int) $usuarioActual['id']) api_error(409, 'No puedes desactivar tu propia cuenta.');
        transaccion(static function (array &$datos) use ($id): void {
            foreach ($datos['usuarios'] as &$usuario) {
                if ((int) $usuario['id'] === $id) {
                    $usuario['estado'] = 'INACTIVO';
                    $usuario['actualizado_en'] = date(DATE_ATOM);
                    unset($usuario);
                    return;
                }
            }
            unset($usuario);
            api_error(404, 'Ese usuario ya no existe.');
        });
        api_responder(['mensaje' => 'Usuario desactivado.']);
    }

    $nombre = trim((string) ($entrada['nombre'] ?? ''));
    $correo = mb_strtolower(trim((string) ($entrada['correo'] ?? '')));
    $telefono = trim((string) ($entrada['telefono'] ?? ''));
    $direccion = trim((string) ($entrada['direccion'] ?? ''));
    $estado = (string) ($entrada['estado'] ?? 'ACTIVO');
    if (mb_strlen($nombre) < 2 || mb_strlen($nombre) > 120 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        api_error(422, 'Revisa el nombre y correo del usuario.');
    }
    if (mb_strlen($telefono) > 40 || mb_strlen($direccion) > 255 || !in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
        api_error(422, 'Revisa el teléfono, dirección y estado del usuario.');
    }

    $actualizado = transaccion(static function (array &$datos) use ($id, $nombre, $correo, $telefono, $direccion, $estado): array {
        foreach ($datos['usuarios'] as &$usuario) {
            if ((int) $usuario['id'] !== $id) continue;
            foreach ($datos['usuarios'] as $otro) {
                if ((int) $otro['id'] !== $id && mb_strtolower((string) $otro['correo']) === $correo) {
                    api_error(409, 'Ese correo ya pertenece a otra cuenta.');
                }
            }
            $usuario = array_merge($usuario, [
                'nombre' => $nombre,
                'correo' => $correo,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'estado' => $estado,
                'actualizado_en' => date(DATE_ATOM),
            ]);
            $respuesta = api_usuario_publico($usuario);
            unset($usuario);
            return $respuesta;
        }
        unset($usuario);
        api_error(404, 'Ese usuario ya no existe.');
    });
    api_responder($actualizado);
});
