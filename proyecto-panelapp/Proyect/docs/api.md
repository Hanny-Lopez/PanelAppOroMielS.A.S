# API PHP de PanelApp

Todas las rutas devuelven JSON en español. Las mutaciones usan sesión y cabecera `X-CSRF-Token`; los permisos se validan en PHP.

| Endpoint | Métodos | Acceso / función |
|---|---|---|
| `api/auth.php` | GET, POST, DELETE | Sesión, registro de cliente, ingreso y salida |
| `api/productos.php` | GET, POST, PUT, DELETE | Catálogo público; CRUD de producto solo ADMIN |
| `api/subir-imagen.php` | POST | ADMIN; valida y guarda foto de producto |
| `api/pedidos.php` | GET, POST, PUT, DELETE | Cliente ve/crea/cancela los propios; ADMIN ve todos y cambia estado |
| `api/pagos.php` | GET, POST, PUT, DELETE | Cliente crea solicitud propia; ADMIN revisa estado |
| `api/entregas.php` | GET, PUT | Cliente ve las propias; ADMIN actualiza estado y fecha |
| `api/clientes.php` | GET | ADMIN consulta perfiles y pedidos |
| `api/usuarios.php` | GET, POST, PUT, DELETE | ADMIN administra usuarios; cliente consulta su propio perfil |
| `api/dashboard.php` | GET | ADMIN consulta contadores y ventas confirmadas |

## Contrato general
- IDs de registros se envían por `?id=...` o dentro del JSON según el endpoint.
- JSON inválido o validación: HTTP 422. Falta de sesión: 401. Permiso/CSRF: 403. Conflicto de stock/estado: 409. Registro inexistente: 404.
- El error público es genérico para fallos 500; detalles van a `error_log`.
- Frontend usa rutas relativas como `api('productos.php')`; la función `api()` agrega el prefijo `/api/`, envía cookies y CSRF.
- `api/almacen.php` centraliza `leer`, `guardar`, `transaccion` y `siguiente_id`.
