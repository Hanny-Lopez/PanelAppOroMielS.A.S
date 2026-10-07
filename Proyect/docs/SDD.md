# SDD — PanelApp

## Objetivo y alcance
Primera versión web para apoyar la gestión de catálogo, clientes, pedidos e inscripción de solicitudes de pago de Oro Miel S.A.S. Está orientada a operación local con XAMPP. No procesa dinero ni ofrece una integración de pasarela.

## Arquitectura
Navegador (HTML semántico, CSS y JavaScript Vanilla) → endpoints PHP que responden JSON → MySQL mediante PDO y consultas preparadas. El navegador nunca accede directamente a MySQL.

## Módulos y roles
- Público: páginas corporativas y consulta del catálogo activo.
- CLIENTE: registro, sesión, consulta de productos, creación y seguimiento de pedidos propios, solicitudes de pago de sus pedidos.
- ADMIN: tablero, CRUD/desactivación de productos (incluida carga de imagen), consulta de clientes, gestión de estados de pedido y pago.
- Entregas: cada pedido crea una entrega pendiente con dirección; ADMIN puede actualizar estado/dirección/fecha y CLIENTE consulta seguimiento.

## Tecnologías y decisiones
HTML5, CSS3, JavaScript Vanilla, PHP 8+ (PDO, sesiones) y MySQL/MariaDB de XAMPP. No se usan frameworks. Contraseñas con `password_hash`/`password_verify`; primer ADMIN se crea desde una utilidad CLI, no desde registro público. La API calcula totales y valida stock en una transacción.

## API, datos y seguridad
Ver `api.md`, `base-datos.md` y `seguridad.md`. La respuesta común tiene `success`, `data` y `message`. Roles se verifican en PHP. Las mutaciones autenticadas usan token CSRF de sesión.

## Errores
Errores de validación, autenticación, autorización, no encontrado, conflicto y servidor usan JSON en español y estados HTTP. Excepciones se registran con `error_log`; no se envía SQL al navegador.

## Pruebas y despliegue
Casos propuestos en `pruebas.md`; la validación automatizada en este entorno queda pendiente. Pasos de XAMPP en `despliegue.md`.

## Limitaciones conocidas
No hay integración de pago, correo de contacto se prepara con mailto, imágenes limitadas a 5 MB JPG/PNG/WebP, no hay servicio de envío de correos, ni perfil de cliente para editar teléfono/dirección. No se incluyen imágenes corporativas licenciadas ni el logo final.
