<?php

namespace App\Console\Commands;

use App\Services\ImportadorInventarioService;
use Database\Support\NormalizacionMigracion as N;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

require_once database_path('support/NormalizacionMigracion.php');

class ImportarInventario extends Command
{
    protected $signature = 'inventario:importar
        {archivo : Ruta del libro Excel (.xlsx) del inventario}
        {--simular : Solo muestra qué se importaría, sin escribir en la base}
        {--categoria=* : Resuelve una hoja ambigua. Formato: hoja=Nombre de la categoría}';

    protected $description = 'Importa el inventario de equipos desde Excel (por encabezados) y registra las filas rechazadas';

    public function handle(ImportadorInventarioService $importador): int
    {
        $archivo = $this->argument('archivo');
        if (!is_file($archivo)) {
            $this->error("No existe el archivo: {$archivo}");

            return self::FAILURE;
        }

        $mapa = [];
        foreach ((array) $this->option('categoria') as $par) {
            [$hoja, $nombre] = array_pad(explode('=', $par, 2), 2, '');
            if ($hoja !== '' && $nombre !== '') {
                $mapa[N::clave($hoja)] = $nombre;
            }
        }

        $simular = (bool) $this->option('simular');
        $informe = $importador->importar($archivo, $simular, $mapa);

        $this->table(
            ['Hoja', 'Leídas', $simular ? 'Se crearían' : 'Creadas', $simular ? 'Se actualizarían' : 'Actualizadas', 'Rechazadas'],
            collect($informe['hojas'])->map(fn ($h, $nombre) => [$nombre, $h['leidas'], $h['creadas'], $h['actualizadas'], $h['rechazadas']])->values()->all()
        );

        foreach ($informe['advertencias'] as $aviso) {
            $this->warn($aviso);
        }

        if ($informe['rechazadas']) {
            $ruta = storage_path('logs/importacion-inventario-' . now()->format('Ymd-His') . '.csv');
            $gestor = fopen($ruta, 'w');
            fputcsv($gestor, ['hoja', 'fila', 'motivo']);
            foreach ($informe['rechazadas'] as $r) {
                fputcsv($gestor, [$r['hoja'], $r['fila'], $r['motivo']]);
            }
            fclose($gestor);

            $this->warn(count($informe['rechazadas']) . " fila(s) rechazada(s). Detalle en: {$ruta}");
        }

        if (!$simular) {
            Artisan::call('normalizacion:sincronizar');
        }

        return self::SUCCESS;
    }
}
