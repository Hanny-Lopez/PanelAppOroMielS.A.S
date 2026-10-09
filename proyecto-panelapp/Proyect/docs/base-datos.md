# Almacenamiento JSON

PanelApp no usa MySQL, PDO ni una base de datos relacional. PHP guarda los registros en `data/`:

- `usuarios.json`: perfiles, roles y hashes de contraseña.
- `productos.json`: catálogo, precios, inventario y ruta de imagen.
- `pedidos.json`: comprador, detalle histórico, total calculado por PHP, dirección y estado.
- `pagos.json`: método, monto, referencia y estado de revisión.
- `entregas.json`: dirección, fecha programada y último estado.

Los archivos los manipula exclusivamente `api/almacen.php`. `transaccion()` usa un bloqueo exclusivo compartido por todos los endpoints para que pedidos simultáneos no descuenten el mismo stock. `data/` debe permanecer fuera del acceso HTTP directo; el `.htaccess` y `router.php` lo bloquean.

Haz copias de seguridad antes de editar manualmente los JSON y detén el servidor durante una restauración.
