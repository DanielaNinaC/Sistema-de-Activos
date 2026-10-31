<?php

use Database\Support\NormalizacionMigracion as N;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once database_path('support/NormalizacionMigracion.php');

/**
 * FASE 3 · CONTRAER (2/4). Riesgo, incidentes, mantenimiento, tratamientos,
 * usuarios y auditoría: se eliminan las columnas de texto que ya viven en una FK.
 *
 * Se ejecuta con `php artisan migrate --path=database/contraccion`, después de
 * la Fase 4. Cada bloque verifica antes de borrar y aborta sin tocar nada si
 * quedaría información sin destino.
 *
 * Riesgo R-1 (modos_falla.valor_*_base): se borran solo si el valor que el
 * motor usa hoy coincide con el que daría nivel × versión vigente.
 */
return new class extends Migration
{
    /** [tabla, columna de texto, FK que la reemplaza, catálogo, PK, columna del catálogo] */
    private const TEXTOS = [
        ['modos_falla', 'nivel_severidad', 'fk_id_nivel_severidad', 'niveles_severidad', 'id_nivel_severidad', 'clave'],
        ['modos_falla', 'nivel_msr', 'fk_id_nivel_msr', 'niveles_msr', 'id_nivel_msr', 'clave'],
        ['evaluacion_riesgo', 'nivel_riesgo', 'fk_id_nivel_riesgo', 'niveles_riesgo', 'id_nivel_riesgo', 'nombre'],
        ['incidentes', 'estado_incidente', 'fk_id_estado_incidente', 'estados_incidente', 'id_estado_incidente', 'nombre'],
        ['incidentes', 'prioridad', 'fk_id_prioridad_incidente', 'prioridades_incidente', 'id_prioridad_incidente', 'nombre'],
        ['mantenimientos', 'tipo_mantenimiento', 'fk_id_tipo_mantenimiento', 'tipos_mantenimiento', 'id_tipo_mantenimiento', 'nombre'],
        ['mantenimientos', 'estado_mantenimiento', 'fk_id_estado_mantenimiento', 'estados_mantenimiento', 'id_estado_mantenimiento', 'nombre'],
        ['mantenimiento_actividades', 'tarea', 'fk_id_tarea_mantenimiento', 'tareas_mantenimiento', 'id_tarea_mantenimiento', 'nombre'],
        ['plan_tratamiento_riesgo', 'estrategia', 'fk_id_estrategia_tratamiento', 'estrategias_tratamiento', 'id_estrategia_tratamiento', 'nombre'],
        ['plan_tratamiento_riesgo', 'estrategia_sugerida', 'fk_id_estrategia_sugerida', 'estrategias_tratamiento', 'id_estrategia_tratamiento', 'nombre'],
        ['plan_tratamiento_riesgo', 'estado_tratamiento', 'fk_id_estado_tratamiento', 'estados_tratamiento', 'id_estado_tratamiento', 'nombre'],
        ['plan_tratamiento_riesgo', 'origen', 'fk_id_origen_tratamiento', 'origenes_tratamiento', 'id_origen_tratamiento', 'nombre'],
        ['auditorias_internas', 'estado', 'fk_id_estado_auditoria', 'estados_auditoria', 'id_estado_auditoria', 'codigo'],
        ['auditorias_internas', 'opinion_calculada', 'fk_id_opinion_auditoria', 'opiniones_auditoria', 'id_opinion_auditoria', 'codigo'],
        ['auditoria_muestra', 'motivo_inclusion', 'fk_id_motivo_inclusion', 'motivos_inclusion_muestra', 'id_motivo_inclusion', 'codigo'],
        ['auditoria_muestra', 'nivel_riesgo_al_muestrear', 'fk_id_nivel_riesgo', 'niveles_riesgo', 'id_nivel_riesgo', 'nombre'],
        ['hallazgos_auditoria', 'apartado', 'fk_id_apartado', 'apartados_auditoria', 'id_apartado', 'numero'],
        ['hallazgos_auditoria', 'clasificacion', 'fk_id_clasificacion', 'clasificaciones_hallazgo', 'id_clasificacion_hallazgo', 'codigo'],
        ['hallazgos_auditoria', 'clasificacion_sugerida', 'fk_id_clasificacion_sugerida', 'clasificaciones_hallazgo', 'id_clasificacion_hallazgo', 'codigo'],
        ['hallazgos_auditoria', 'estado', 'fk_id_estado_hallazgo', 'estados_hallazgo', 'id_estado_hallazgo', 'codigo'],
    ];

    /** Columnas que pasan a NOT NULL una vez verificadas: [tabla, fk]. */
    private const OBLIGATORIAS = [
        ['modos_falla', 'fk_id_nivel_severidad'], ['modos_falla', 'fk_id_nivel_msr'],
        ['evaluacion_riesgo', 'fk_id_nivel_riesgo'], ['evaluacion_riesgo', 'fk_id_version_criterio'],
        ['incidentes', 'fk_id_estado_incidente'], ['incidentes', 'fk_id_prioridad_incidente'],
        ['mantenimientos', 'fk_id_tipo_mantenimiento'], ['mantenimientos', 'fk_id_estado_mantenimiento'],
        ['plan_tratamiento_riesgo', 'fk_id_estrategia_tratamiento'], ['plan_tratamiento_riesgo', 'fk_id_estado_tratamiento'],
        ['plan_tratamiento_riesgo', 'fk_id_origen_tratamiento'],
    ];

    private const PERMISOS = ['dashboard', 'activos', 'incidentes', 'riesgos', 'mantenimiento', 'informes', 'usuarios'];

    public function up(): void
    {
        $this->verificar();

        foreach (self::TEXTOS as [$tabla, $texto]) {
            N::soltarColumna($tabla, $texto);
        }
        // numero_tarea se deriva del catálogo (tareas_mantenimiento.numero).
        N::soltarColumna('mantenimiento_actividades', 'numero_tarea');

        // Valores base del modo de falla: derivables de nivel × versión vigente (D-7).
        N::soltarColumnas('modos_falla', ['valor_severidad_base', 'valor_msr_base']);
        // El tipo de alerta se deriva de evaluación → nivel → tipo (decisión del modelo).
        N::soltarColumna('alertas', 'tipo_alerta');
        // La vigencia pasa a la cabecera de la versión de criterios.
        N::soltarColumnas('pesos_ahp', ['vigente', 'fecha_actualizacion']);
        N::soltarColumnas('usuarios', array_map(fn ($m) => 'permiso_' . $m, self::PERMISOS));

        foreach (self::OBLIGATORIAS as [$tabla, $fk]) {
            Schema::table($tabla, fn (Blueprint $t) => $t->unsignedBigInteger($fk)->nullable(false)->change());
        }
    }

    private function verificar(): void
    {
        foreach (self::TEXTOS as [$tabla, $texto, $fk]) {
            // 'Sin evaluar' en la muestra de auditoría se representa con FK nula.
            if ($tabla === 'auditoria_muestra' && $texto === 'nivel_riesgo_al_muestrear') {
                N::abortarSi(
                    DB::table($tabla)->whereNull($fk)->whereNotNull($texto)->where($texto, '<>', 'Sin evaluar')->count(),
                    "fila(s) de «{$tabla}» con nivel de riesgo sin FK."
                );
                continue;
            }

            N::verificarCompleto($tabla, $fk, $texto);
        }

        // Obligatorias: deben estar completas aunque el texto original también fuera nulo.
        foreach (self::OBLIGATORIAS as [$tabla, $fk]) {
            N::abortarSi(DB::table($tabla)->whereNull($fk)->count(), "fila(s) de «{$tabla}» sin «{$fk}» (columna que pasará a NOT NULL).");
        }

        // R-1: el valor base que el motor usa hoy debe coincidir con la escala vigente.
        foreach ([
            ['valor_severidad_base', 'valores_severidad', 'fk_id_nivel_severidad'],
            ['valor_msr_base', 'valores_msr', 'fk_id_nivel_msr'],
        ] as [$base, $valores, $fkNivel]) {
            if (!N::tiene('modos_falla', $base)) {
                continue;
            }

            $discrepancias = DB::table('modos_falla as m')
                ->join("{$valores} as v", "v.{$fkNivel}", '=', "m.{$fkNivel}")
                ->join('versiones_criterios as c', 'c.id_version_criterio', '=', 'v.fk_id_version_criterio')
                ->where('c.vigente', true)
                ->whereNotNull("m.{$base}")
                ->whereColumn("m.{$base}", '<>', 'v.valor')
                ->count();
            N::abortarSi($discrepancias, "modo(s) de falla con «{$base}» distinto del valor de su nivel en la versión vigente (R-1). Corrija el nivel y reintente.");
        }

        // alertas.tipo_alerta debe coincidir con el derivado de la evaluación.
        if (N::tiene('alertas', 'tipo_alerta')) {
            $distintas = DB::table('alertas as a')
                ->join('evaluacion_riesgo as e', 'e.id_evaluacion', '=', 'a.fk_id_evaluacion')
                ->join('niveles_riesgo as n', 'n.id_nivel_riesgo', '=', 'e.fk_id_nivel_riesgo')
                ->leftJoin('tipos_alerta as t', 't.id_tipo_alerta', '=', 'n.fk_id_tipo_alerta')
                ->where(fn ($q) => $q->whereNull('t.nombre')->orWhereColumn('t.nombre', '<>', 'a.tipo_alerta'))
                ->count();
            N::abortarSi($distintas, 'alerta(s) cuyo tipo no coincide con el derivado de su evaluación.');
        }

        N::verificarCompleto('pesos_ahp', 'fk_id_version_criterio');

        // Permisos: una fila usuario_modulo por usuario y módulo con columna permiso_*.
        $esperadas = DB::table('usuarios')->count() * count(array_filter(
            self::PERMISOS,
            fn ($m) => N::tiene('usuarios', 'permiso_' . $m)
        ));
        if ($esperadas > 0) {
            N::abortarSi(
                max(0, $esperadas - DB::table('usuario_modulo')->count()),
                'permiso(s) de usuario aún sin fila en usuario_modulo.'
            );
        }
    }

    public function down(): void
    {
        // Permisos: columnas booleanas reconstruidas desde usuario_modulo.
        foreach (self::PERMISOS as $modulo) {
            $columna = 'permiso_' . $modulo;
            if (N::tiene('usuarios', $columna)) {
                continue;
            }
            Schema::table('usuarios', fn (Blueprint $t) => $t->boolean($columna)->default(false));
            DB::statement("UPDATE usuarios SET {$columna} = COALESCE(
                (SELECT um.habilitado FROM usuario_modulo um JOIN modulos m ON m.id_modulo = um.fk_id_modulo
                  WHERE um.fk_id_usuario = usuarios.id_usuario AND m.codigo = '" . strtoupper($modulo) . "'), 0)");
        }

        if (!N::tiene('pesos_ahp', 'vigente')) {
            Schema::table('pesos_ahp', function (Blueprint $t) {
                $t->boolean('vigente')->default(false);
                $t->dateTime('fecha_actualizacion')->nullable();
            });
            DB::statement('UPDATE pesos_ahp SET
                vigente = (SELECT v.vigente FROM versiones_criterios v WHERE v.id_version_criterio = pesos_ahp.fk_id_version_criterio),
                fecha_actualizacion = (SELECT v.vigente_desde FROM versiones_criterios v WHERE v.id_version_criterio = pesos_ahp.fk_id_version_criterio)');
        }

        if (!N::tiene('alertas', 'tipo_alerta')) {
            Schema::table('alertas', fn (Blueprint $t) => $t->string('tipo_alerta', 100)->nullable());
            DB::statement('UPDATE alertas SET tipo_alerta =
                (SELECT t.nombre FROM evaluacion_riesgo e
                   JOIN niveles_riesgo n ON n.id_nivel_riesgo = e.fk_id_nivel_riesgo
                   JOIN tipos_alerta t ON t.id_tipo_alerta = n.fk_id_tipo_alerta
                  WHERE e.id_evaluacion = alertas.fk_id_evaluacion)');
        }

        // Valores base: se reconstruyen desde la escala de la versión de cada evaluación/vigente.
        if (!N::tiene('modos_falla', 'valor_severidad_base')) {
            Schema::table('modos_falla', function (Blueprint $t) {
                $t->integer('valor_severidad_base')->nullable();
                $t->integer('valor_msr_base')->nullable();
            });
            foreach ([['valor_severidad_base', 'valores_severidad', 'fk_id_nivel_severidad'], ['valor_msr_base', 'valores_msr', 'fk_id_nivel_msr']] as [$base, $valores, $fk]) {
                DB::statement("UPDATE modos_falla SET {$base} =
                    (SELECT v.valor FROM {$valores} v JOIN versiones_criterios c ON c.id_version_criterio = v.fk_id_version_criterio
                      WHERE c.vigente = 1 AND v.{$fk} = modos_falla.{$fk})");
            }
        }

        // Los NOT NULL se relajan para poder restaurar sin romper filas históricas.
        foreach (self::OBLIGATORIAS as [$tabla, $fk]) {
            Schema::table($tabla, fn (Blueprint $t) => $t->unsignedBigInteger($fk)->nullable()->change());
        }

        if (!N::tiene('mantenimiento_actividades', 'numero_tarea')) {
            Schema::table('mantenimiento_actividades', fn (Blueprint $t) => $t->unsignedTinyInteger('numero_tarea')->nullable());
            DB::statement('UPDATE mantenimiento_actividades SET numero_tarea =
                (SELECT c.numero FROM tareas_mantenimiento c WHERE c.id_tarea_mantenimiento = mantenimiento_actividades.fk_id_tarea_mantenimiento)');
        }

        foreach (self::TEXTOS as [$tabla, $texto, $fk, $catalogo, $pk, $columna]) {
            N::restaurarTexto($tabla, $texto, $fk, $catalogo, $pk, $columna, $texto === 'tarea' ? 255 : 100);
        }

        // Índices de rendimiento de 2026_07_22_033900_add_performance_indexes: su down() los espera.
        foreach ([
            ['evaluacion_riesgo', 'nivel_riesgo', 'idx_evaluacion_nivel_riesgo'],
            ['incidentes', 'estado_incidente', 'idx_incidentes_estado'],
            ['mantenimientos', 'estado_mantenimiento', 'idx_mantenimientos_estado'],
            ['alertas', 'tipo_alerta', 'idx_alertas_tipo'],
        ] as [$tabla, $columna, $indice]) {
            if (N::tiene($tabla, $columna) && !N::tieneIndice($tabla, $indice)) {
                Schema::table($tabla, fn (Blueprint $t) => $t->index($columna, $indice));
            }
        }
    }
};
