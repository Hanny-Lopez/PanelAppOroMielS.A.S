# Responsabilidades de agentes

- Arquitectura: mantener separación de presentación, API y datos; registrar decisiones.
- Backend PHP: validar entradas, roles, sesión, CSRF, SQL preparado y reglas de dominio.
- Frontend: accesibilidad, responsive, consumo JSON y presentación segura de texto.
- QA: probar casos correctos y negativos, permisos, sesiones, API, SQL y tamaños móviles.
- Documentación: mantener alcance, rutas de ejecución, decisiones y pendientes consistentes.

Ningún agente debe incluir secretos reales, dejar contraseñas en texto plano, exponer excepciones SQL o introducir frameworks no autorizados.
