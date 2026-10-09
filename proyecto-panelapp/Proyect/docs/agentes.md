# Responsabilidades de agentes

- Arquitectura: mantener páginas HTML, estilos y lógica separados; registrar decisiones en `MEMORIA.md`.
- Frontend: preservar JavaScript puro, accesibilidad, diseño responsive y salida segura; el carrito es la única parte del navegador.
- Backend: PHP 8 sin base de datos; JSON solo mediante `api/almacen.php`, roles y CSRF en cada mutación.
- QA: probar casos correctos y negativos de ambos roles, CRUD, carrito, pagos simulados y estados de entrega.
- Documentación: mantener alcance, rutas de ejecución, decisiones y limitaciones consistentes con la versión PHP local.

No incluir datos personales reales ni credenciales. No presentar pagos o seguimiento como servicios reales. No agregar base de datos, frameworks o dependencias sin autorización.
