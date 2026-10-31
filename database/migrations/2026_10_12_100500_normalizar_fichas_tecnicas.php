<?php

use Database\Support\NormalizacionMigracion as N;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

require_once database_path('support/NormalizacionMigracion.php');

/**
 * FASE 3 · EXPANDIR (6/8). Fichas técnicas (1FN y 3FN).
 *
 *  - detalles_computadora: procesador y sistema operativo pasan a catálogos
 *    (con variantes unificadas: "Core i7" / "CORE i7" / "core i7").
 *  - Discos: el par disco_solido_gb / disco_rigido_gb (grupo repetido) pasa a
 *    `activo_almacenamiento`, una fila por unidad con su tipo.
 *  - Periféricos: las 5 columnas booleanas pasan a `activo_componente`.
 *  - detalles_equipo_red.direccion_ip: UNIQUE, si los datos lo permiten.
 *
 * Decisión sobre RAM: `ram_gb` es una medida numérica, no un valor de
 * catálogo; se conserva como columna entera (no se crea `capacidades_ram`).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (N::tiene('activo_almacenamiento')) {
            return;
        }

        Schema::create('activo_almacenamiento', function (Blueprint $t) {
            $t->id('id_activo_almacenamiento');
            $t->unsignedBigInteger('fk_id_activo');
            $t->unsignedBigInteger('fk_id_tipo_almacenamiento');
            $t->unsignedInteger('capacidad_gb');
            $t->timestamps();

            $t->unique(['fk_id_activo', 'fk_id_tipo_almacenamiento', 'capacidad_gb'], 'activo_almacenamiento_unico');
            $t->foreign('fk_id_activo')->references('id_activo')->on('activos_tecnologicos')->cascadeOnUpdate()->cascadeOnDelete();
            $t->foreign('fk_id_tipo_almacenamiento')->references('id_tipo_almacenamiento')->on('tipos_almacenamiento')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('activo_componente', function (Blueprint $t) {
            $t->unsignedBigInteger('fk_id_activo');
            $t->unsignedBigInteger('fk_id_componente');
            $t->timestamps();

            $t->primary(['fk_id_activo', 'fk_id_componente']);
            $t->foreign('fk_id_activo')->references('id_activo')->on('activos_tecnologicos')->cascadeOnUpdate()->cascadeOnDelete();
            $t->foreign('fk_id_componente')->references('id_componente')->on('componentes')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::table('detalles_computadora', function (Blueprint $t) {
            $t->unsignedBigInteger('fk_id_procesador')->nullable();
            $t->unsignedBigInteger('fk_id_sistema_operativo')->nullable();
        });

        $this->poblar();

        Schema::table('detalles_computadora', function (Blueprint $t) {
            $t->foreign('fk_id_procesador')->references('id_procesador')->on('procesadores')->cascadeOnUpdate()->restrictOnDelete();
            $t->foreign('fk_id_sistema_operativo')->references('id_sistema_operativo')->on('sistemas_operativos')->cascadeOnUpdate()->restrictOnDelete();
        });

        $this->unicidadDeIp();
    }

    /** Idempotente. */
    public function poblar(): void
    {
        $this->poblarProcesadores();
        $this->poblarSistemasOperativos();
        $this->poblarAlmacenamiento();
        $this->poblarComponentes();
    }

    private function poblarProcesadores(): void
    {
        $ahora = now();
        $pendientes = DB::table('detalles_computadora')->whereNull('fk_id_procesador')->whereNotNull('procesador')->get(['fk_id_activo', 'procesador']);

        $id = [];
        foreach (DB::table('procesadores')->get() as $p) {
            $id[N::clave($p->nombre) . '|' . ($p->frecuencia_ghz === null ? '' : (float) $p->frecuencia_ghz)] = $p->id_procesador;
        }

        foreach ($pendientes as $d) {
            if (N::limpiar($d->procesador) === '') {
                continue;
            }
            [$nombre, $ghz] = N::procesador($d->procesador);
            $llave = N::clave($nombre) . '|' . ($ghz ?? '');

            if (!isset($id[$llave])) {
                $etiqueta = $ghz !== null ? $nombre . ' ' . number_format($ghz, 2, '.', '') . ' GHz' : $nombre;
                $id[$llave] = DB::table('procesadores')->insertGetId([
                    'codigo' => N::codigoUnico('procesadores', $etiqueta), 'nombre' => $nombre, 'frecuencia_ghz' => $ghz,
                    'orden' => 0, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            }

            DB::table('detalles_computadora')->where('fk_id_activo', $d->fk_id_activo)->update(['fk_id_procesador' => $id[$llave]]);
        }
    }

    private function poblarSistemasOperativos(): void
    {
        $ahora = now();
        $pendientes = DB::table('detalles_computadora')->whereNull('fk_id_sistema_operativo')->whereNotNull('sistema_operativo')->get(['fk_id_activo', 'sistema_operativo']);

        $conteos = [];
        foreach ($pendientes as $d) {
            $t = N::limpiar($d->sistema_operativo);
            if ($t !== '') {
                $conteos[$t] = ($conteos[$t] ?? 0) + 1;
            }
        }
        $canonicos = N::canonicos($conteos);

        $id = [];
        foreach (DB::table('sistemas_operativos')->get() as $s) {
            $id[N::clave($s->nombre)] = $s->id_sistema_operativo;
        }

        foreach ($pendientes as $d) {
            $texto = N::limpiar($d->sistema_operativo);
            if ($texto === '') {
                continue;
            }
            $clave = N::clave($texto);

            if (!isset($id[$clave])) {
                $nombre = $canonicos[$clave];
                $id[$clave] = DB::table('sistemas_operativos')->insertGetId([
                    'codigo' => N::codigoUnico('sistemas_operativos', $nombre), 'nombre' => $nombre,
                    'orden' => 0, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            }

            DB::table('detalles_computadora')->where('fk_id_activo', $d->fk_id_activo)->update(['fk_id_sistema_operativo' => $id[$clave]]);
        }
    }

    private function poblarAlmacenamiento(): void
    {
        $ahora = now();
        $tipo = DB::table('tipos_almacenamiento')->pluck('id_tipo_almacenamiento', 'codigo');

        foreach (['disco_solido_gb' => 'SSD', 'disco_rigido_gb' => 'HDD'] as $columna => $codigo) {
            $filas = DB::table('detalles_computadora')->where($columna, '>', 0)->get(['fk_id_activo', $columna]);

            foreach ($filas as $d) {
                $existe = DB::table('activo_almacenamiento')->where([
                    'fk_id_activo' => $d->fk_id_activo, 'fk_id_tipo_almacenamiento' => $tipo[$codigo], 'capacidad_gb' => $d->{$columna},
                ])->exists();

                if (!$existe) {
                    DB::table('activo_almacenamiento')->insert([
                        'fk_id_activo' => $d->fk_id_activo, 'fk_id_tipo_almacenamiento' => $tipo[$codigo],
                        'capacidad_gb' => $d->{$columna}, 'created_at' => $ahora, 'updated_at' => $ahora,
                    ]);
                }
            }
        }
    }

    private function poblarComponentes(): void
    {
        $ahora = now();
        $componente = DB::table('componentes')->pluck('id_componente', 'codigo');

        foreach ([
            'lector' => 'LECTOR', 'parlantes' => 'PARLANTES', 'teclado_mouse' => 'TECLADO_Y_MOUSE',
            'monitor' => 'MONITOR', 'camara_web' => 'CAMARA_WEB',
        ] as $columna => $codigo) {
            foreach (DB::table('detalles_computadora')->where($columna, true)->pluck('fk_id_activo') as $idActivo) {
                $existe = DB::table('activo_componente')->where(['fk_id_activo' => $idActivo, 'fk_id_componente' => $componente[$codigo]])->exists();

                if (!$existe) {
                    DB::table('activo_componente')->insert([
                        'fk_id_activo' => $idActivo, 'fk_id_componente' => $componente[$codigo],
                        'created_at' => $ahora, 'updated_at' => $ahora,
                    ]);
                }
            }
        }
    }

    /**
     * La IP es identificador del equipo en la red: debe ser única. Si hoy hay
     * repetidas no se fuerza (se perderían datos): queda registrado en el log y
     * la restricción se agrega cuando se depuren.
     */
    private function unicidadDeIp(): void
    {
        DB::table('detalles_equipo_red')->whereRaw("TRIM(direccion_ip) = ''")->update(['direccion_ip' => null]);

        $repetidas = DB::table('detalles_equipo_red')->whereNotNull('direccion_ip')
            ->select('direccion_ip', DB::raw('COUNT(*) as n'))->groupBy('direccion_ip')->having('n', '>', 1)->pluck('n', 'direccion_ip');

        if ($repetidas->isNotEmpty()) {
            Log::warning('Normalización: no se agregó UNIQUE a detalles_equipo_red.direccion_ip; IP repetidas: ' . json_encode($repetidas));

            return;
        }

        Schema::table('detalles_equipo_red', fn (Blueprint $t) => $t->unique('direccion_ip'));
    }

    public function down(): void
    {
        if (N::tiene('detalles_equipo_red')) {
            try {
                Schema::table('detalles_equipo_red', fn (Blueprint $t) => $t->dropUnique(['direccion_ip']));
            } catch (Throwable) {
                // No se había agregado (IP repetidas).
            }
        }

        Schema::table('detalles_computadora', function (Blueprint $t) {
            $t->dropForeign(['fk_id_procesador']);
            $t->dropForeign(['fk_id_sistema_operativo']);
            $t->dropColumn(['fk_id_procesador', 'fk_id_sistema_operativo']);
        });

        Schema::dropIfExists('activo_componente');
        Schema::dropIfExists('activo_almacenamiento');
        DB::table('procesadores')->delete();
        DB::table('sistemas_operativos')->delete();
    }
};
