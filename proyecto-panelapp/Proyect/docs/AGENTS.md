# Guía del proyecto para agentes

## Alcance actual
- PanelApp usa HTML, CSS, JavaScript puro y PHP 8; no usa MySQL ni paquetes/frameworks.
- El servidor PHP guarda usuarios, productos, pedidos, pagos y entregas en `data/*.json`.
- Todos los accesos a JSON pasan por `api/almacen.php` y sus funciones `leer`, `guardar`, `transaccion` y `siguiente_id`.
- `localStorage` se limita al carrito temporal; la sesión y datos del negocio son del servidor.

## Organización
- Páginas: archivos HTML de la raíz.
- Estilos compartidos: `css/style.css`.
- Lógica e interfaz dinámica: `js/script.js`.
- Endpoints PHP: `api/`; cada endpoint valida método, sesión, rol y CSRF según operación.
- Datos privados: `data/` (protegidos con `.htaccess` y `router.php`).
- Imágenes aprobadas: `img/` (`logo.png`, `bodega.jpg` y fotografías de catálogo).
- Contexto y decisiones: `MEMORIA.md`.

## Buenas prácticas
- Mantener el código legible: sangría consistente, nombres claros, funciones pequeñas y comentarios por sección.
- Escapar texto dinámico antes de insertarlo en HTML; preferir `textContent` para mensajes.
- Mantener las operaciones CRUD existentes y probar tanto el flujo de cliente como el de administrador.
- No mostrar stock exacto al cliente. No afirmar que un pago, correo o seguimiento GPS es real si no está conectado a un proveedor.
- Precios e inventario se verifican en PHP; nunca confiar en totales enviados por el navegador.
- Respetar accesibilidad, diseño responsive y `prefers-reduced-motion`.
- No eliminar trabajo previo sin comprobar su uso.

## Verificación
- Revisar JavaScript con `node --check js/script.js`.
- Revisar PHP con `C:\xampp\php\php.exe -l ruta\archivo.php`.
- Ejecutar en la raíz con `C:\xampp\php\php.exe -S localhost:8000 router.php` y probar en `http://localhost:8000`.
- Probar registro e ingreso por ambos roles, CRUD de productos, pedido y resumen del carrito, solicitud de pago, seguimiento, mensajes y vista imprimible del reporte.
