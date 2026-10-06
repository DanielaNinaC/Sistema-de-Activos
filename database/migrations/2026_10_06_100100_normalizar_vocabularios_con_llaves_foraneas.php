<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enlaza cada columna que guardaba un vocabulario como texto libre con su
 * catálogo (llave foránea sobre la clave natural), de modo que la base de
 * datos rechaza valores fuera del vocabulario. Los datos existentes y las
 * consultas del código no cambian: la columna sigue guardando el mismo texto.
 */
return new class extends Migration
{
    /** [tabla, columna, catálogo, columna del catálogo] */
    private const VINCULOS = [
        ['activos_tecnologicos', 'estado_operativo', 'estados_operativos', 'nombre'],
        ['activos_tecnologicos', 'etapa_ciclo_vida', 'etapas_ciclo_vida', 'nombre'],
        ['activos_tecnologicos', 'motivo_baja', 'motivos_baja', 'nombre'],
        ['activos_tecnologicos', 'estado_previo_baja', 'estados_operativos', 'nombre'],
        ['activos_tecnologicos', 'nivel_riesgo', 'niveles_riesgo', 'nombre'],
        ['categorias', 'nivel_dependencia', 'niveles_dependencia', 'nombre'],
        ['modos_falla', 'nivel_severidad', 'niveles_severidad', 'clave'],
        ['modos_falla', 'nivel_msr', 'niveles_msr', 'clave'],
        ['evaluacion_riesgo', 'nivel_riesgo', 'niveles_riesgo', 'nombre'],
        ['alertas', 'tipo_alerta', 'tipos_alerta', 'nombre'],
        ['incidentes', 'estado_incidente', 'estados_incidente', 'nombre'],
        ['incidentes', 'prioridad', 'prioridades_incidente', 'nombre'],
        ['mantenimientos', 'tipo_mantenimiento', 'tipos_mantenimiento', 'nombre'],
        ['mantenimientos', 'estado_mantenimiento', 'estados_mantenimiento', 'nombre'],
        ['plan_tratamiento_riesgo', 'estrategia', 'estrategias_tratamiento', 'nombre'],
        ['plan_tratamiento_riesgo', 'estrategia_sugerida', 'estrategias_tratamiento', 'nombre'],
        ['plan_tratamiento_riesgo', 'estado_tratamiento', 'estados_tratamiento', 'nombre'],
        ['plan_tratamiento_riesgo', 'origen', 'origenes_tratamiento', 'nombre'],
    ];

    /**
     * Padres cuyo borrado arrastraba datos principales (borrar un rol borraba
     * a todos sus usuarios, borrar una categoría borraba sus activos, borrar
     * un usuario borraba su bitácora). Pasan a RESTRICT: hay que reasignar
     * antes de borrar. [tabla, columna, tabla padre, columna padre]
     */
    private const CASCADAS_A_RESTRINGIR = [
        ['usuarios', 'fk_id_rol', 'roles', 'id_rol'],
        ['logs', 'id_usuario', 'usuarios', 'id_usuario'],
        ['activos_tecnologicos', 'fk_id_categoria', 'categorias', 'id_categoria'],
        ['activos_tecnologicos', 'fk_id_ubicacion', 'ubicaciones', 'id_ubicacion'],
        ['modos_falla', 'fk_id_categoria', 'categorias', 'id_categoria'],
        ['evaluacion_riesgo', 'fk_id_modo_falla', 'modos_falla', 'id_modo_falla'],
        ['incidentes', 'fk_id_usuario_reporta', 'usuarios', 'id_usuario'],
        ['mantenimientos', 'fk_id_usuario_asignado', 'usuarios', 'id_usuario'],
    ];

    public function up(): void
    {
        // Deriva de datos: el código usa claves en snake_case ('muy_alta') y
        // la etiqueta visible se deriva de ellas; algunas filas guardaban la
        // etiqueta ('Muy Alta'). Se unifican a la clave.
        foreach (['nivel_severidad', 'nivel_msr'] as $columna) {
            DB::statement("UPDATE modos_falla SET {$columna} = LOWER(REPLACE({$columna}, ' ', '_')) WHERE {$columna} IS NOT NULL");
        }

        foreach (self::VINCULOS as [$tabla, $columna, $catalogo, $referencia]) {
            Schema::table($tabla, function (Blueprint $table) use ($columna, $catalogo, $referencia) {
                $table->foreign($columna)->references($referencia)->on($catalogo)
                    ->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        foreach (self::CASCADAS_A_RESTRINGIR as [$tabla, $columna, $padre, $referencia]) {
            Schema::table($tabla, function (Blueprint $table) use ($columna, $padre, $referencia) {
                $table->dropForeign([$columna]);
                $table->foreign($columna)->references($referencia)->on($padre)
                    ->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        // Unicidad que solo se validaba en el controlador.
        Schema::table('categorias', function (Blueprint $table) {
            $table->unique('nombre_categoria');
        });

        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->unique(['seccion', 'aula']);
        });
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $table->dropUnique(['seccion', 'aula']);
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->dropUnique(['nombre_categoria']);
        });

        foreach (self::CASCADAS_A_RESTRINGIR as [$tabla, $columna, $padre, $referencia]) {
            Schema::table($tabla, function (Blueprint $table) use ($columna, $padre, $referencia) {
                $table->dropForeign([$columna]);
                $table->foreign($columna)->references($referencia)->on($padre)->cascadeOnDelete();
            });
        }

        foreach (array_reverse(self::VINCULOS) as [$tabla, $columna]) {
            Schema::table($tabla, function (Blueprint $table) use ($columna) {
                $table->dropForeign([$columna]);
            });
        }
    }
};
