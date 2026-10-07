# Ejecución local con XAMPP

1. Copia el proyecto en `C:\xampp\htdocs\Paneapp\Proyect` (o ajusta la ruta según tu instalación).
2. Abre XAMPP e inicia Apache y MySQL.
3. En `http://localhost/phpmyadmin`, importa `database/panelapp.sql`.
4. Si XAMPP usa `root` sin contraseña, la configuración local funciona por defecto. En otro caso define `PANELAPP_DB_HOST`, `PANELAPP_DB_NAME`, `PANELAPP_DB_USER` y `PANELAPP_DB_PASS` en el entorno de Apache. No uses credenciales locales débiles en producción.
5. Desde una terminal en la raíz del proyecto, ejecuta `C:\xampp\php\php.exe tools\crear-admin.php`; indica correo válido, nombre y contraseña de mínimo 8 caracteres. No existe una cuenta predeterminada.
6. Abre `http://localhost/Paneapp/Proyect/`.

Requisitos recomendados: PHP 8.1+ con extensiones PDO MySQL, Fileinfo y mbstring; MySQL 8+/MariaDB compatible con CHECK; Apache con `mod_rewrite` no requerido. Para producción configurar HTTPS, usuario DB limitado y secretos fuera de webroot.
