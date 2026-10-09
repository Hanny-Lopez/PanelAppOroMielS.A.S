<?php
declare(strict_types=1);

require_once __DIR__ . '/almacen.php';
require_once __DIR__ . '/sesion.php';

sesion_iniciar();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

/** Envía JSON y finaliza la petición. */
function api_responder(array $contenido, int $estado = 200): never
{
    http_response_code($estado);
    echo json_encode($contenido, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Envía un error de negocio con estado HTTP apropiado. */
function api_error(int $estado, string $mensaje, array $campos = []): never
{
    $respuesta = ['error' => $mensaje];
    if ($campos !== []) {
        $respuesta['campos'] = $campos;
    }
    api_responder($respuesta, $estado);
}

/** Envuelve endpoints para ocultar errores internos al navegador y registrarlos en Apache. */
function api_ejecutar(callable $controlador): never
{
    try {
        $controlador();
        api_responder(['ok' => true]);
    } catch (JsonException $error) {
        error_log('PanelApp JSON inválido: ' . $error->getMessage());
        api_error(500, 'Ocurrió un error interno. Intenta de nuevo.');
    } catch (Throwable $error) {
        error_log('PanelApp API: ' . $error->getMessage());
        api_error(500, 'Ocurrió un error interno. Intenta de nuevo.');
    }
}

function api_metodo(array $permitidos): string
{
    $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if (!in_array($metodo, $permitidos, true)) {
        header('Allow: ' . implode(', ', $permitidos));
        api_error(405, 'Método no permitido.');
    }
    return $metodo;
}

/** Lee JSON del cuerpo HTTP; los formularios multipart siguen disponibles en $_POST/$_FILES. */
function api_entrada(): array
{
    $tipo = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($tipo === 'application/json') {
        $contenido = file_get_contents('php://input');
        if ($contenido === false || $contenido === '') {
            return [];
        }
        $datos = json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($datos)) {
            api_error(422, 'El cuerpo de la solicitud debe ser un objeto JSON.');
        }
        return $datos;
    }
    return $_POST;
}

/** Obtiene la cuenta vigente desde el archivo de usuarios, no desde datos confiados del navegador. */
function api_usuario_actual(): ?array
{
    $id = (int) ($_SESSION['usuario_id'] ?? 0);
    if ($id < 1) {
        return null;
    }

    foreach (leer('usuarios') as $usuario) {
        if ((int) ($usuario['id'] ?? 0) === $id && ($usuario['estado'] ?? 'ACTIVO') === 'ACTIVO') {
            return $usuario;
        }
    }

    $_SESSION = [];
    session_regenerate_id(true);
    return null;
}

function api_requerir_usuario(?string $rol = null): array
{
    $usuario = api_usuario_actual();
    if ($usuario === null) {
        api_error(401, 'Tu sesión terminó. Ingresa nuevamente.');
    }
    if ($rol !== null && ($usuario['rol'] ?? '') !== $rol) {
        api_error(403, 'No tienes permiso para esta acción.');
    }
    return $usuario;
}

/** Previene CSRF en operaciones que cambian datos, incluyendo autenticación. */
function api_requerir_csrf(): void
{
    $enviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $guardado = $_SESSION['csrf'] ?? '';
    if ($enviado === '' || $guardado === '' || !hash_equals($guardado, $enviado)) {
        api_error(403, 'La sesión de seguridad venció. Recarga la página e inténtalo otra vez.');
    }
}

function api_id_desde_ruta(): int
{
    return max(0, (int) ($_GET['id'] ?? 0));
}

/** Nunca devuelve el hash de contraseña en las respuestas. */
function api_usuario_publico(array $usuario): array
{
    unset($usuario['clave_hash']);
    return $usuario;
}
