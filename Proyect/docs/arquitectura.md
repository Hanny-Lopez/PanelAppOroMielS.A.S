# Arquitectura

```text
HTML/CSS/JavaScript (fetch, JSON, cookies de sesión)
                 ↓ HTTP
          API PHP (auth, reglas, permisos)
                 ↓ PDO / consultas preparadas
          MySQL / MariaDB
```

PHP valida identidad, autorización, entradas, stock y precios. El total de pedidos se deriva de los precios consultados en la base de datos. El frontend solo presenta información y mejora la experiencia.
