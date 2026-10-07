# Seguridad

- `password_hash(PASSWORD_DEFAULT)` y `password_verify`; contraseñas nunca se almacenan en texto plano.
- Registro público siempre crea CLIENTE. El ADMIN inicial se genera con `php tools/crear-admin.php` desde CLI.
- Roles verificados en PHP en cada endpoint privado; los clientes solo consultan sus pedidos/pagos.
- PDO con prepared statements y excepciones registradas en log; respuesta 500 genérica.
- Validación de datos del lado del servidor y salida HTML codificada por JS mediante escape.
- Sesión estricta, cookie HttpOnly, SameSite=Lax, regeneración de ID al autenticar y expiración por inactividad de 30 minutos; HTTPS marca cookie Secure.
- Token CSRF requerido para operaciones con cambios autenticadas; no hay credenciales de producción en el repositorio.
- Carga de imagen con límite 5 MB, validación MIME y contenido, nombre aleatorio y restricción de ejecución PHP en uploads.
- Para producción: HTTPS obligatorio, contraseña MySQL no vacía, secretos por variables de entorno, actualizar dependencias/servidor y respaldos.
