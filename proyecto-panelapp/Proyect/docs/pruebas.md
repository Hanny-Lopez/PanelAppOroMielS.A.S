# Pruebas de PanelApp

## Ejecutadas en este cambio
- PHP 8.2: lint a todos los archivos `.php` del proyecto, sin errores de sintaxis.
- JavaScript: `node --check js/script.js`, sin errores.
- HTML: parseo de las 13 páginas.
- API PHP integrada con el servidor de desarrollo: `GET api/auth.php` devolvió sesión y token CSRF; `GET api/productos.php` devolvió la colección; acceso directo a `data/usuarios.json` fue bloqueado por el router.
- `api/almacen.php`: lectura y transacción JSON de prueba.

## Pruebas manuales pendientes
- Crear el administrador con `tools/crear-admin.php`; ingresar por el selector Admin.
- Registrar cliente e ingresar por el selector Cliente.
- Crear, consultar, editar y desactivar producto desde administración.
- Crear pedido como cliente; comprobar que aparece como administrador y que el stock se descuenta en JSON.
- Probar stock insuficiente, cancelar pedido pendiente y comprobar devolución de unidades.
- Registrar pago, aprobarlo como administrador y comprobar que un pedido no se envía antes de aprobar el pago.
- Actualizar entrega y consultar su estado desde otra sesión/dispositivo.
- Crear, editar y desactivar perfil de cliente.
- Imprimir el dashboard y guardar el diálogo del navegador como PDF.
- Repetir las pruebas con dos dispositivos apuntando a la misma instancia PHP.

No se ha probado una pasarela de pagos ni GPS: no forman parte de los servicios conectados.
