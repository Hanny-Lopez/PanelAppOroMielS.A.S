# Memoria del proyecto PanelApp

> Contexto técnico actual. No guardar credenciales ni datos personales en este documento.

## Tecnologías y ejecución
- HTML, CSS, JavaScript puro y PHP 8. XAMPP trae el ejecutable en `C:\xampp\php\php.exe` en este equipo.
- No se usa MySQL/PDO/SQL ni librerías externas del proyecto.
- Desde PowerShell en la raíz: `& 'C:\xampp\php\php.exe' -S localhost:8000 router.php` → `http://localhost:8000`.
- Los equipos de la misma red pueden conectarse usando `-S 0.0.0.0:8000 router.php` y la IPv4 del servidor.

## Arquitectura
- HTML de páginas en la raíz; estilos en `css/style.css`; lógica de UI y `fetch()` en `js/script.js`.
- Endpoints: `api/auth.php`, `productos.php`, `subir-imagen.php`, `pedidos.php`, `pagos.php`, `entregas.php`, `clientes.php`, `usuarios.php` y `dashboard.php`.
- `api/almacen.php` es el acceso central a `data/*.json`, usa lock exclusivo para transacciones y asignación de IDs.
- Sesiones PHP con cookie HttpOnly/SameSite y CSRF para operaciones que cambian datos.

## Roles y áreas
- Registro público crea Cliente. Admin se crea por CLI con `& 'C:\xampp\php\php.exe' tools/crear-admin.php`.
- Acceso Cliente: `login.html?rol=CLIENTE`; acceso Admin: `login.html?rol=ADMIN`.
- CRUD de productos en `productos.html`; administración de perfiles en `clientes.html`; pedidos aparecen a ambos roles según permisos.
- Imágenes de marca: `img/logo.png` y `img/bodega.jpg`; catálogo de productos sube imágenes a `uploads/productos/`.

## Límites por implementar
- Pagos se registran y confirman en administración; todavía no hay pasarela real.
- El seguimiento guarda estados y fechas, no ubicación GPS ni servicio de transportadora.
- Reporte tiene ventas confirmadas y botón imprimir/guardar PDF; no hay compras/costos, por eso no calcula utilidad.
- JSON es adecuado para práctica/prototipo con bajo volumen, no para operación con alta concurrencia. En Internet usar hosting PHP, HTTPS, respaldo y controles adicionales.

## Pruebas
- PHP: `& 'C:\xampp\php\php.exe' -l api\archivo.php`.
- JavaScript: `node --check js/script.js`.
- Revisar cada flujo en localhost con una cuenta cliente y otra admin.
