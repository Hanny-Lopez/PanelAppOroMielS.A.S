# API HTTP

Base local: `/Paneapp/Proyect/api/`. JSON común: `{ "success": true|false, "data": ..., "message": "..." }`. La cookie de sesión se envía con `credentials: same-origin`. Luego de autenticar, incluir `X-CSRF-Token` en cambios.

| Endpoint | Método | Acceso | Uso |
|---|---|---|---|
| `auth.php` | GET | Público | Estado de sesión y token CSRF |
| `auth.php` | POST | Público | `action=login` con correo/password o `action=register` con nombre/correo/password; registra CLIENTE |
| `auth.php` | DELETE | Sesión + CSRF | Cerrar sesión |
| `productos.php` | GET | Público | Catálogo activo; `?id=` detalle. ADMIN ve inactivos |
| `productos.php` | POST | ADMIN + CSRF | Crear producto: nombre, descripcion, precio, stock, imagen opcional, estado |
| `productos.php?id={id}` | PUT | ADMIN + CSRF | Actualizar producto |
| `productos.php?id={id}` | DELETE | ADMIN + CSRF | Desactivar producto |
| `subir-imagen.php` | POST multipart | ADMIN + CSRF | Campo `imagen`; retorna ruta segura |
| `dashboard.php` | GET | ADMIN | Conteos del tablero |
| `clientes.php` | GET | ADMIN | Clientes y cantidad de pedidos |
| `pedidos.php` | GET | Sesión | ADMIN: todos; CLIENTE: propios. `?id=` incluye detalle |
| `pedidos.php` | POST | CLIENTE + CSRF | `{items:[{producto_id,cantidad}]}`; el servidor calcula precios/total |
| `pedidos.php?id={id}` | PUT | ADMIN + CSRF | `{estado}` |
| `pagos.php` | GET | Sesión | ADMIN todos; CLIENTE propios |
| `pagos.php` | POST | CLIENTE + CSRF | `{pedido_id,metodo}` crea solicitud pendiente, no cobra |
| `pagos.php` | PUT | ADMIN + CSRF | `{id,estado}` |
| `entregas.php` | GET | Sesión | ADMIN todas; CLIENTE las propias |
| `entregas.php` | PUT | ADMIN + CSRF | `{id,estado,direccion,fecha_programada}` |

Al crear un pedido, la API crea en la misma transacción una entrega `PENDIENTE` con la dirección proporcionada.

Estados habituales: 200 éxito, 201 creación, 400 validación, 401 no autenticado, 403 sin permiso/CSRF, 404 no encontrado, 405 método incorrecto, 409 duplicado, 500 error seguro.
