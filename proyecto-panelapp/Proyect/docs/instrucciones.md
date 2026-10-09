# Instrucciones de desarrollo

1. Mantener HTML, CSS, JavaScript puro y PHP 8; no usar base de datos ni dependencias.
2. Los datos de negocio son JSON en `data/`; solo manipularlos mediante `api/almacen.php`.
3. Mantener los mensajes e interfaz en español y escapar los textos dinámicos.
4. Validar en PHP y en el navegador; autorizar cada endpoint del lado del servidor.
5. Preservar CRUD, sesiones, roles y reglas de inventario.
6. Actualizar `MEMORIA.md` y los casos de prueba cuando cambie el comportamiento.
7. No almacenar credenciales reales en el repositorio; usar `password_hash`/`password_verify`.
