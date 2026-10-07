<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ini_set('session.cookie_secure', '1');
session_start();
if (isset($_SESSION['LAST_ACTIVITY']) && time() - (int)$_SESSION['LAST_ACTIVITY'] > 1800) { $_SESSION=[]; session_regenerate_id(true); }
$_SESSION['LAST_ACTIVITY']=time();
function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $host=getenv('PANELAPP_DB_HOST')?:'127.0.0.1'; $name=getenv('PANELAPP_DB_NAME')?:'panelapp';
    $user=getenv('PANELAPP_DB_USER')?:'root'; $pass=getenv('PANELAPP_DB_PASS')?:'';
    $pdo=new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    return $pdo;
}
function json_response(bool $success,mixed $data,string $message,int $status=200): never { http_response_code($status); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store'); echo json_encode(['success'=>$success,'data'=>$data,'message'=>$message],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE); exit; }
function body(): array { $raw=file_get_contents('php://input'); if(!$raw)return []; $parsed=json_decode($raw,true); if(!is_array($parsed))json_response(false,null,'El contenido enviado no es válido.',400); return $parsed; }
function require_method(array $allowed): void { if(!in_array($_SERVER['REQUEST_METHOD']??'',$allowed,true)){header('Allow: '.implode(', ',$allowed));json_response(false,null,'Método no permitido.',405);} }
function current_user(): ?array { return $_SESSION['user']??null; }
function require_login(): array { $user=current_user(); if(!$user)json_response(false,null,'Tu sesión ha expirado. Inicia sesión nuevamente.',401); return $user; }
function require_admin(): array { $user=require_login(); if($user['rol']!=='ADMIN')json_response(false,null,'No tienes permisos para realizar esta operación.',403); return $user; }
function require_csrf(): void { $sent=$_SERVER['HTTP_X_CSRF_TOKEN']??''; $expected=$_SESSION['csrf']??''; if(!$expected||!$sent||!hash_equals($expected,$sent))json_response(false,null,'La solicitud expiró. Recarga la página e inténtalo nuevamente.',403); }
set_exception_handler(function(Throwable $error): void { error_log('[PanelApp] '.$error->__toString()); json_response(false,null,'Ocurrió un error interno. Intenta nuevamente.',500); });
