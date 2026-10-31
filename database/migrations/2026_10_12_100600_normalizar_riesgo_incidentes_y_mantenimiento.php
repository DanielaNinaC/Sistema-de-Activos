<?php

use Database\Support\NormalizacionMigracion as N;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once database_path('support/NormalizacionMigracion.php');

/**
 * FASE 3 · EXPANDIR (7/8). Modos de falla, incidentes, mantenimientos,
 * tratamientos y checklist: cada vocabulario guardado como texto pasa a
 * columna `fk_id_*` con su catálogo.
 *
 * `modos_falla.valor_severidad_base` / `valor_msr_base` NO se tocan aquí: son
 * derivables de nivel × versión de criterios y se eliminan en contracción,
 * después de verificar con la prueba de equivalencia (riesgo R-1 del diseño).
 */
return new class extends Migration
{
    /** [tabla, columna nueva, catálogo, PK del catálogo, columna de texto, columna del catálogo] */
    private const ENLACES = [
        ['modos_falla', 'fk_id_nivel_severidad', 'niveles_severidad', 'id_nivel_severidad', 'nivel_severidad', 'clave'],
        ['modos_falla', 'fk_id_nivel_msr', 'niveles_msr', 'id_nivel_msr', 'nivel_msr', 'clave'],
        ['incidentes', 'fk_id_estado_incidente', 'estados_incidente', 'id_estado_incidente', 'estado_incidente', 'nombre'],
        ['incidentes', 'fk_id_prioridad_incidente', 'prioridades_incidente', 'id_prioridad_incidente', 'prioridad', 'nombre'],
        ['mantenimientos', 'fk_id_tipo_mantenimiento', 'tipos_mantenimiento', 'id_tipo_mantenimiento', 'tipo_mantenimiento', 'nombre'],
        ['mantenimientos', 'fk_id_estado_mantenimiento', 'estados_mantenimiento', 'id_estado_mantenimiento', 'estado_mantenimiento', 'nombre'],
        ['plan_tratamiento_riesgo', 'fk_id_estrategia_tratamiento', 'estrategias_tratamiento', 'id_estrategia_tratamiento', 'estrategia', 'nombre'],
        ['plan_tratamiento_riesgo', 'fk_id_estrategia_sugerida', 'estrategias_tratamiento', 'id_estrategia_tratamiento', 'estrategia_sugerida', 'nombre'],
        ['plan_tratamiento_riesgo', 'fk_id_estado_tratamiento', 'estados_tratamiento', 'id_estado_tratamiento', 'estado_tratamiento', 'nombre'],
        ['plan_tratamiento_riesgo', 'fk_id_origen_tratamiento', 'origenes_tratamiento', 'id_origen_tratamiento', 'origen', 'nombre'],
    ];

    public function up(): void
    {
        if (N::tiene('incidentes', 'fk_id_estado_incidente')) {
            return;
        }

        foreach (self::ENLACES as [$tabla, $nueva]) {
            Schema::table($tabla, fn (Blueprint $t) => $t->unsignedBigInteger($nueva)->nullable());
        }

        Schema::table('mantenimiento_actividades', function (Blueprint $t) {
            $t->unsignedBigInteger('fk_id_tarea_mantenimiento')->nullable();
        });

        // Origen de mantenimiento ya tenía id: se uniforma al patrón (codigo, orden, activo).
        Schema::table('origen_mantenimiento', function (Blueprint $t) {
            $t->string('codigo', 50)->nullable();
            $t->string('descripcion')->nullable();
            $t->unsignedTinyInteger('orden')->default(0);
            $t->boolean('activo')->default(true);
        });

        $this->poblar();

        Schema::table('origen_mantenimiento', fn (Blueprint $t) => $t->unique('codigo'));

        foreach (self::ENLACES as [$tabla, $nueva, $catalogo, $pk]) {
            Schema::table($tabla, function (Blueprint $t) use ($nueva, $catalogo, $pk) {
                $t->foreign($nueva)->references($pk)->on($catalogo)->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        Schema::table('mantenimiento_actividades', function (Blueprint $t) {
            $t->foreign('fk_id_tarea_mantenimiento')->references('id_tarea_mantenimiento')->on('tareas_mantenimiento')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    /** Idempotente. */
    public function poblar(): void
    {
        foreach (self::ENLACES as [$tabla, $nueva, $catalogo, $pk, $texto, $columna]) {
            DB::statement("UPDATE {$tabla} SET {$nueva} =
                (SELECT c.{$pk} FROM {$catalogo} c WHERE c.{$columna} = {$tabla}.{$texto})
                WHERE {$nueva} IS NULL AND {$texto} IS NOT NULL");
        }

        // Modos de falla sin nivel textual pero con valor base: el motor ya usa ese
        // valor, así que el nivel se deduce de la escala (misma severidad/MSR → mismo puntaje).
        foreach ([
            ['fk_id_nivel_severidad', 'nivel_severidad', 'valor_severidad_base', 'niveles_severidad', 'id_nivel_severidad'],
            ['fk_id_nivel_msr', 'nivel_msr', 'valor_msr_base', 'niveles_msr', 'id_nivel_msr'],
        ] as [$fk, $texto, $base, $catalogo, $pk]) {
            DB::statement("UPDATE modos_falla SET {$fk} =
                (SELECT c.{$pk} FROM {$catalogo} c WHERE c.valor = modos_falla.{$base})
                WHERE {$fk} IS NULL AND {$base} IS NOT NULL");
        }

        // Checklist: primero por número de tarea; si no, por coincidencia exacta del texto.
        DB::statement('UPDATE mantenimiento_actividades SET fk_id_tarea_mantenimiento =
            (SELECT c.id_tarea_mantenimiento FROM tareas_mantenimiento c WHERE c.numero = mantenimiento_actividades.numero_tarea)
            WHERE fk_id_tarea_mantenimiento IS NULL AND numero_tarea IS NOT NULL');

        DB::statement('UPDATE mantenimiento_actividades SET fk_id_tarea_mantenimiento =
            (SELECT c.id_tarea_mantenimiento FROM tareas_mantenimiento c WHERE c.nombre = mantenimiento_actividades.tarea)
            WHERE fk_id_tarea_mantenimiento IS NULL');

        foreach (DB::table('origen_mantenimiento')->whereNull('codigo')->orderBy('id_origen')->get() as $o) {
            DB::table('origen_mantenimiento')->where('id_origen', $o->id_origen)->update([
                'codigo' => N::codigoUnico('origen_mantenimiento', $o->nombre),
                'orden' => min(255, $o->id_origen),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('mantenimiento_actividades', function (Blueprint $t) {
            $t->dropForeign(['fk_id_tarea_mantenimiento']);
            $t->dropColumn('fk_id_tarea_mantenimiento');
        });

        Schema::table('origen_mantenimiento', function (Blueprint $t) {
            $t->dropUnique(['codigo']);
            $t->dropColumn(['codigo', 'descripcion', 'orden', 'activo']);
        });

        foreach (array_reverse(self::ENLACES) as [$tabla, $nueva]) {
            Schema::table($tabla, function (Blueprint $t) use ($nueva) {
                $t->dropForeign([$nueva]);
                $t->dropColumn($nueva);
            });
        }
    }
};
