# Plan de pruebas

| Caso | Resultado esperado | Estado |
|---|---|---|
| Importar SQL en MySQL | Tablas, relaciones y roles se crean | Pendiente en XAMPP |
| Registro con correo inválido / clave <8 | HTTP 400, no crea usuario | Pendiente |
| Registro duplicado | HTTP 409, mensaje seguro | Pendiente |
| Login válido / inválido | Sesión y rol / HTTP 401 | Pendiente |
| Logout | Cookie/sesión terminan | Pendiente |
| Cliente llama endpoint de clientes | HTTP 403 | Pendiente |
| Crear/editar/desactivar producto con ADMIN | Persistencia y catálogo actualizado | Pendiente |
| Subir archivo falso o >5 MB | HTTP 400, archivo no aceptado | Pendiente |
| Pedido correcto | Total de DB, stock descontado, HTTP 201 | Pendiente |
| Pedido con stock insuficiente | Rechazo y rollback total | Pendiente |
| Cliente intenta consultar pedido ajeno | HTTP 404 sin filtrar datos | Pendiente |
| Pago duplicidad/estado inválido | Validación segura | Pendiente |
| Pedido crea entrega y cliente ve únicamente las propias | Datos de seguimiento restringidos por rol/propiedad | Pendiente |
| SQL Injection / XSS en campos | No ejecuta SQL ni HTML inyectado | Pendiente |
| Navegación en móvil | Menú usable, tablas desplazables | Revisión manual pendiente |
| Servidor apagado / JSON inválido | Mensaje amigable, sin bloqueo | Pendiente |

## Verificaciones realizadas en el entorno de desarrollo
- `php -l` en todos los PHP: sin errores de sintaxis.
- `node --check js/script.js`: sin errores de sintaxis.
- Endpoint `GET api/auth.php` ejecutado con servidor PHP local: HTTP 200 y sobre JSON válido.
- XAMPP Apache/MySQL no estaban activos durante la revisión; flujos que requieren base de datos quedan pendientes.
