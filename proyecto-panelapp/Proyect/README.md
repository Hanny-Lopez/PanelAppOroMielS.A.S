# PanelApp — Panela Oro Miel S.A.S.

Aplicación local construida con HTML, CSS, JavaScript puro y PHP 8. Los datos se guardan en JSON en el servidor; no usa MySQL, Composer ni npm.

## Iniciar en localhost con PHP

En PowerShell, abre la carpeta del proyecto y ejecuta:

```powershell
& 'C:\xampp\php\php.exe' -S localhost:8000 router.php
```

Mantén la ventana abierta y visita **http://localhost:8000**. Para detener el servidor, presiona `Ctrl+C`.

También puedes copiar la carpeta a `C:\xampp\htdocs\panelapp`, iniciar Apache desde XAMPP y abrir **http://localhost/panelapp/**.

## Crear la cuenta administradora

El registro público crea únicamente cuentas de cliente. Crea el primer administrador desde otra ventana de PowerShell:

```powershell
& 'C:\xampp\php\php.exe' tools/crear-admin.php
```

El script pide correo, nombre y una contraseña de al menos 12 caracteres. Luego ingresa en **http://localhost:8000/login.html?rol=ADMIN**. Los clientes se registran desde `registro.html` o ingresan en `login.html?rol=CLIENTE`.

## Compartir con equipos en la misma red

Inicia el servidor escuchando en la red local:

```powershell
& 'C:\xampp\php\php.exe' -S 0.0.0.0:8000 router.php
```

Busca la IPv4 de este equipo con `ipconfig`. Otro dispositivo conectado a esa red podrá abrir `http://IPV4_DEL_EQUIPO:8000`. Si Windows pregunta, permite PHP solo en redes privadas.

Todos esos dispositivos consultan el mismo servidor y los mismos archivos JSON. Para exponer la aplicación desde Internet se necesita alojamiento PHP con HTTPS; no publiques el servidor de desarrollo directamente.

## Catálogo y operaciones

- Para cambiar la sección promocional del inicio, edita `index.html` y busca `id="catalogo"`.
- El catálogo se presenta en `productos.html`, dentro de `data-product-list`.
- Para agregar productos, ingresa como administrador, abre **Productos** y usa **Gestionar catálogo**. El formulario permite crear, consultar, editar y desactivar registros.
- Las fotos de productos se cargan mediante `api/subir-imagen.php` y se guardan en `uploads/productos/`.
- Logo y foto corporativa: coloca los archivos en `img/logo.png` y `img/bodega.jpg`.

## Datos y seguridad

- `data/` contiene `usuarios.json`, `productos.json`, `pedidos.json`, `pagos.json` y `entregas.json`.
- Solo `api/almacen.php` puede leer o modificar esos datos. Cada cambio usa una transacción con bloqueo para proteger el inventario.
- Contraseñas guardadas con `password_hash`; roles, sesiones y CSRF se validan en PHP.
- Pagos todavía son solicitudes para revisión; no hay cobro por pasarela, ni seguimiento GPS real.
- JSON funciona para este prototipo educativo. Antes de uso público, configura HTTPS, respaldos y controles de servidor adecuados.

Consulta [`AGENTS.md`](AGENTS.md), [`MEMORIA.md`](MEMORIA.md) y los documentos de `docs/` para más contexto.
