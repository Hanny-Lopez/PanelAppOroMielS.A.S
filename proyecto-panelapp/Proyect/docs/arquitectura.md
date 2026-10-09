# Arquitectura actual de PanelApp

```text
HTML de cada página
       ↓
CSS compartido + JavaScript (fetch)
       ↓ HTTP / sesión PHP
api/*.php (validación, autorización, reglas del negocio)
       ↓
api/almacen.php (lectura, transacción y guardado JSON)
       ↓
data/*.json (bloqueo exclusivo durante cambios)
```

## Separación del código
- Los archivos HTML contienen estructura y puntos de montaje `data-*`.
- `css/style.css` define identidad visual, componentes, responsive y movimiento accesible.
- `js/script.js` monta encabezado y pie, gestiona sesiones, hace `fetch()` a PHP y renderiza vistas.
- `api/` valida sesión, rol, CSRF, entradas, inventario y estados del pedido.
- `api/almacen.php` es el único módulo autorizado para leer/escribir archivos de datos.

## Persistencia y límites
- Productos, usuarios, pedidos, pagos y entregas se almacenan en JSON en el servidor.
- Las sesiones usan cookie HttpOnly/SameSite y las mutaciones requieren CSRF.
- Los pagos siguen siendo solicitudes administrativas; no hay pasarela ni GPS.
- JSON es adecuado para el curso y un prototipo local; requiere copias de seguridad y no es la opción recomendada para alto tráfico.
