<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\Artisan;

/**
 * Los seeders de datos escriben los textos libres que el código actual lee
 * (marca, estado_operativo…). Al terminar, este trait completa las fk_id_*
 * de la normalización para que el esquema nuevo quede coherente.
 *
 * Es andamiaje de transición: en la Fase 4, cuando el código y los seeders
 * escriban directamente las FK, deja de ser necesario.
 */
trait SincronizaNormalizacion
{
    public function __invoke(array $parameters = [])
    {
        $resultado = parent::__invoke($parameters);

        Artisan::call('normalizacion:sincronizar');

        return $resultado;
    }
}
