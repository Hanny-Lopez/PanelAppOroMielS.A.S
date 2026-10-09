<?php
declare(strict_types=1);

/** Configura cookies de sesión seguras antes de llamar session_start(). */
function sesion_iniciar(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $esHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('panelapp_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $esHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    // Invalida la sesión tras 30 minutos sin solicitudes al servidor.
    $ahora = time();
    $ultimaActividad = (int) ($_SESSION['ultima_actividad'] ?? 0);
    if ($ultimaActividad > 0 && $ahora - $ultimaActividad > 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['ultima_actividad'] = $ahora;

    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}
