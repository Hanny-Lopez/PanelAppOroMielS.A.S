# Seguridad aplicada y pendiente

## Aplicado en el prototipo
- Contraseñas se guardan con `password_hash(PASSWORD_DEFAULT)` y se verifican con `password_verify`.
- Registro público crea CLIENTE; cuenta ADMIN se crea desde terminal con `tools/crear-admin.php`.
- Cada endpoint privado consulta sesión y rol en PHP. Cliente solo recibe sus pedidos, pagos y entregas.
- Mutaciones requieren token CSRF en cabecera `X-CSRF-Token`; cookie de sesión HttpOnly y SameSite=Lax.
- Se regenera el ID de sesión al ingresar y se expira tras 30 minutos de inactividad.
- Totales, precios y stock se calculan/validan en servidor dentro de `transaccion()`.
- `data/` se deniega por `.htaccess` y por `router.php`; archivos de imágenes aceptan MIME permitidos, tamaño límite y nombre aleatorio.
- Se elimina `clave_hash` de respuestas de usuarios; errores inesperados van a `error_log`.

## Antes de publicar en Internet
- Usar HTTPS, respaldos automáticos y permisos mínimos de escritura.
- Revisar rate limiting, recuperación de contraseñas, verificación de correo, registro de auditoría y política de retención.
- JSON con bloqueo no reemplaza una base de datos para una operación con alta concurrencia o tráfico.
- No hay pasarela de pago ni rastreo GPS conectados. No solicitar ni almacenar datos de pago reales.
