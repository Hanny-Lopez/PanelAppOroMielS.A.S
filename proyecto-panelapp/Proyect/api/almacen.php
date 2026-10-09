<?php
declare(strict_types=1);

/**
 * Almacenamiento JSON central. Ningún otro endpoint debe leer o escribir
 * directamente los archivos dentro de data/.
 */
const PANELAPP_DATA_DIR = __DIR__ . '/../data';
const PANELAPP_ENTIDADES = ['usuarios', 'productos', 'pedidos', 'pagos', 'entregas'];

function almacen_ruta(string $entidad): string
{
    if (!in_array($entidad, PANELAPP_ENTIDADES, true)) {
        throw new InvalidArgumentException('Entidad de almacenamiento no permitida.');
    }

    return PANELAPP_DATA_DIR . '/' . $entidad . '.json';
}

/** Lee una entidad JSON usando bloqueo compartido para evitar lecturas parciales. */
function leer(string $entidad): array
{
    if (!is_dir(PANELAPP_DATA_DIR) && !mkdir(PANELAPP_DATA_DIR, 0770, true) && !is_dir(PANELAPP_DATA_DIR)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento local.');
    }

    $lock = fopen(PANELAPP_DATA_DIR . '/.lock', 'c+');
    if ($lock === false || !flock($lock, LOCK_SH)) {
        throw new RuntimeException('No se pudo bloquear el almacenamiento local.');
    }

    try {
        return almacen_leer_sin_bloqueo($entidad);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/** Guarda una entidad a través de una transacción exclusiva. */
function guardar(string $entidad, array $registros): void
{
    transaccion(static function (array &$datos) use ($entidad, $registros): void {
        $datos[$entidad] = array_values($registros);
    });
}

/**
 * Ejecuta cambios con bloqueo exclusivo para que dos pedidos simultáneos no
 * descuenten el mismo inventario. Si el callback falla, no se guardan cambios.
 */
function transaccion(callable $operacion): mixed
{
    if (!is_dir(PANELAPP_DATA_DIR) && !mkdir(PANELAPP_DATA_DIR, 0770, true) && !is_dir(PANELAPP_DATA_DIR)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento local.');
    }

    $lock = fopen(PANELAPP_DATA_DIR . '/.lock', 'c+');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('No se pudo bloquear el almacenamiento local.');
    }

    try {
        $datos = [];
        foreach (PANELAPP_ENTIDADES as $entidad) {
            $datos[$entidad] = almacen_leer_sin_bloqueo($entidad);
        }

        $resultado = $operacion($datos);

        foreach (PANELAPP_ENTIDADES as $entidad) {
            almacen_escribir_sin_bloqueo($entidad, $datos[$entidad] ?? []);
        }

        return $resultado;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/** Calcula el próximo ID de una lista de registros mientras hay una transacción. */
function siguiente_id(array $registros): int
{
    $mayor = 0;
    foreach ($registros as $registro) {
        $mayor = max($mayor, (int) ($registro['id'] ?? 0));
    }

    return $mayor + 1;
}

/** Funciones internas: sus llamadas requieren que el bloqueo global ya esté adquirido. */
function almacen_leer_sin_bloqueo(string $entidad): array
{
    $ruta = almacen_ruta($entidad);
    if (!is_file($ruta)) {
        return [];
    }

    $contenido = file_get_contents($ruta);
    if ($contenido === false) {
        throw new RuntimeException('No se pudo leer el almacenamiento local.');
    }

    $registros = json_decode($contenido, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($registros)) {
        throw new RuntimeException('El archivo de datos no tiene el formato esperado.');
    }

    return $registros;
}

function almacen_escribir_sin_bloqueo(string $entidad, array $registros): void
{
    $json = json_encode(
        array_values($registros),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );

    if (file_put_contents(almacen_ruta($entidad), $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('No se pudo guardar el almacenamiento local.');
    }
}
