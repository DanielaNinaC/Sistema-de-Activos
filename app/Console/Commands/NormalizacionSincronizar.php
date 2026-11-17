<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-ejecuta la población idempotente de las migraciones de normalización.
 *
 * Mientras el código siga escribiendo las columnas de texto antiguas, las
 * nuevas fk_id_* de las filas creadas después de migrar quedan vacías. Este
 * comando las completa (solo toca filas que aún no tienen su FK) y es el paso
 * previo a `php artisan migrate --path=database/contraccion`.
 */
class NormalizacionSincronizar extends Command
{
    protected $signature = 'normalizacion:sincronizar';

    protected $description = 'Completa las columnas fk_id_* de filas creadas después de la normalización (idempotente)';

    public function handle(): int
    {
        $archivos = glob(database_path('migrations/2026_10_12_1*_*.php')) ?: [];
        sort($archivos);

        $ejecutadas = 0;

        foreach ($archivos as $archivo) {
            $migracion = require $archivo;

            if (!is_object($migracion) || !method_exists($migracion, 'poblar')) {
                continue;
            }

            DB::transaction(fn () => $migracion->poblar());
            $this->line('  ✓ ' . basename($archivo, '.php'));
            $ejecutadas++;
        }

        $this->info("Sincronización completa ({$ejecutadas} migraciones).");

        return self::SUCCESS;
    }
}
