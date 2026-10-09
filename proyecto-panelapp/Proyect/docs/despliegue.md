# Ejecución con PHP 8 y XAMPP

## Servidor de desarrollo (recomendado para esta carpeta)

Desde PowerShell, con el directorio actual en la raíz del proyecto:

```powershell
& 'C:\xampp\php\php.exe' -S localhost:8000 router.php
```

Abre `http://localhost:8000`. El router evita que los archivos de `data/` sean servidos por el servidor integrado de PHP.

## Apache de XAMPP

1. Copia el proyecto a `C:\xampp\htdocs\panelapp`.
2. Inicia Apache desde XAMPP Control Panel.
3. Abre `http://localhost/panelapp/`.
4. Verifica que Apache respete `.htaccess` y que PHP pueda escribir en `data/` y `uploads/productos/`.
5. Crea el administrador con `C:\xampp\php\php.exe tools\crear-admin.php` desde la carpeta del proyecto.

## Acceso desde otros equipos de la misma red

Inicia PHP en `0.0.0.0:8000`, permite PHP solo en una red privada de Windows y comparte `http://IPV4_DEL_SERVIDOR:8000`. Las sesiones son distintas por usuario, mientras los registros JSON son comunes en el servidor.

No expongas el servidor PHP integrado a Internet. Para acceso público usa hosting PHP con HTTPS, permisos de escritura restringidos y respaldos. Los archivos JSON son apropiados para este prototipo local, no para tráfico alto.
