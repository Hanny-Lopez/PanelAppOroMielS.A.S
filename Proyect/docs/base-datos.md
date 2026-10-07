# Base de datos

Importar `database/panelapp.sql`. Tablas InnoDB, `utf8mb4`, claves primarias/foráneas e índices:

- `roles`: roles ADMIN y CLIENTE.
- `usuarios`: correo único, hash de contraseña, estado y rol.
- `clientes`: perfil 1:1 con usuario, teléfono, dirección, estado.
- `productos`: descripción, precio DECIMAL, stock, ruta de imagen y estado.
- `pedidos`: cliente, total DECIMAL, estado y fecha.
- `detalle_pedido`: producto, cantidad, precio unitario y subtotal históricos.
- `pagos`: pedido, monto, método, estado y referencia opcional.
- `entregas`: relación 1:1 con pedido, dirección, estado y fecha programada.

La creación de pedido toma el precio actual desde `productos`, bloquea el stock y descuenta inventario dentro de una transacción. No usar el total del navegador.
