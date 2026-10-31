<?php

use Database\Support\NormalizacionMigracion as N;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once database_path('support/NormalizacionMigracion.php');

/**
 * FASE 3 · EXPANDIR (2/8). Catálogos nuevos y los parámetros que hoy viven en
 * constantes PHP y en config/auditoria.php, sembrados 1:1 con esos valores
 * (ver docs/modelo-normalizado-fase2.md §3.2, §3.6 y §4).
 *
 * No toca el código vigente: son tablas que el sistema todavía no lee.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (N::tiene('fichas_tecnicas')) {
            return;
        }

        // ── Activos ─────────────────────────────────────────────────────
        Schema::create('fichas_tecnicas', fn (Blueprint $t) => N::columnasCatalogo($t, 'id_ficha_tecnica'));

        Schema::create('tipos_activo', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_tipo_activo');
            $t->unsignedBigInteger('fk_id_categoria');
            $t->unsignedBigInteger('fk_id_ficha_tecnica');

            $t->foreign('fk_id_categoria')->references('id_categoria')->on('categorias')->cascadeOnUpdate()->restrictOnDelete();
            $t->foreign('fk_id_ficha_tecnica')->references('id_ficha_tecnica')->on('fichas_tecnicas')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('marcas', fn (Blueprint $t) => N::columnasCatalogo($t, 'id_marca'));

        Schema::create('procesadores', function (Blueprint $t) {
            $t->id('id_procesador');
            $t->string('codigo', 50)->unique();
            $t->string('nombre', 150);
            $t->decimal('frecuencia_ghz', 4, 2)->nullable();
            $t->string('descripcion')->nullable();
            $t->unsignedTinyInteger('orden')->default(0);
            $t->boolean('activo')->default(true);
            $t->timestamps();

            $t->unique(['nombre', 'frecuencia_ghz']);
        });

        Schema::create('sistemas_operativos', fn (Blueprint $t) => N::columnasCatalogo($t, 'id_sistema_operativo'));
        Schema::create('tipos_almacenamiento', fn (Blueprint $t) => N::columnasCatalogo($t, 'id_tipo_almacenamiento'));
        Schema::create('componentes', fn (Blueprint $t) => N::columnasCatalogo($t, 'id_componente'));
        Schema::create('secciones', fn (Blueprint $t) => N::columnasCatalogo($t, 'id_seccion'));

        // ── Mantenimiento y bitácora ────────────────────────────────────
        Schema::create('tareas_mantenimiento', function (Blueprint $t) {
            $t->id('id_tarea_mantenimiento');
            $t->string('codigo', 50)->unique();
            $t->string('nombre', 255);
            $t->unsignedTinyInteger('numero')->unique(); // el texto de una tarea no es único; su número sí
            $t->string('descripcion')->nullable();
            $t->unsignedTinyInteger('orden')->default(0);
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });

        Schema::create('tipos_anotacion', fn (Blueprint $t) => N::columnasCatalogo($t, 'id_tipo_anotacion'));

        // ── Seguridad ───────────────────────────────────────────────────
        Schema::create('modulos', fn (Blueprint $t) => N::columnasCatalogo($t, 'id_modulo'));

        // ── Auditoría interna ───────────────────────────────────────────
        Schema::create('estados_auditoria', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_estado_auditoria');
            $t->boolean('es_inicial')->default(false);
            $t->boolean('es_inmutable')->default(false);
        });

        Schema::create('opiniones_auditoria', fn (Blueprint $t) => N::columnasCatalogo($t, 'id_opinion_auditoria'));
        Schema::create('motivos_inclusion_muestra', fn (Blueprint $t) => N::columnasCatalogo($t, 'id_motivo_inclusion'));

        Schema::create('apartados_auditoria', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_apartado');
            $t->string('numero', 10)->unique();
            $t->string('reporte_referencia', 50)->nullable();
        });

        Schema::create('clasificaciones_hallazgo', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_clasificacion_hallazgo');
            $t->unsignedSmallInteger('plazo_dias')->nullable();
            $t->unsignedTinyInteger('orden_gravedad');
        });

        Schema::create('estados_hallazgo', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_estado_hallazgo');
            $t->boolean('es_cierre')->default(false);
        });

        Schema::create('criterios_auditoria', function (Blueprint $t) {
            $t->id('id_criterio_auditoria');
            $t->unsignedBigInteger('fk_id_apartado');
            $t->string('texto');
            $t->unsignedTinyInteger('orden')->default(0);
            $t->boolean('activo')->default(true);
            $t->timestamps();

            $t->foreign('fk_id_apartado')->references('id_apartado')->on('apartados_auditoria')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('indicadores_auditoria', function (Blueprint $t) {
            $t->id('id_indicador');
            $t->string('codigo', 50)->unique();
            $t->string('nombre', 150);
            $t->unsignedBigInteger('fk_id_apartado');
            $t->string('operador', 2)->nullable(); // '>=' o '<='; NULL si es informativo
            $t->decimal('meta_valor', 6, 2)->nullable();
            $t->string('unidad', 30)->nullable();
            $t->boolean('es_informativo')->default(false);
            $t->unsignedTinyInteger('orden')->default(0);
            $t->boolean('activo')->default(true);
            $t->timestamps();

            $t->foreign('fk_id_apartado')->references('id_apartado')->on('apartados_auditoria')->cascadeOnUpdate()->restrictOnDelete();
        });

        // ── Parámetros generales ────────────────────────────────────────
        Schema::create('parametros_sistema', function (Blueprint $t) {
            $t->id('id_parametro');
            $t->string('clave', 100)->unique();
            $t->string('valor');
            $t->string('tipo_dato', 20); // entero | decimal | texto
            $t->string('unidad', 30)->nullable();
            $t->string('descripcion')->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });

        $this->sembrar();
    }

    private function sembrar(): void
    {
        $ahora = now();

        /** Inserta filas estándar [nombre, descripcion?, extra[]] con codigo derivado y orden secuencial. */
        $catalogo = function (string $tabla, array $filas) use ($ahora) {
            foreach (array_values($filas) as $i => $f) {
                [$nombre, $descripcion, $extra, $codigo] = $f + [1 => null, 2 => [], 3 => null];
                DB::table($tabla)->insert($extra + [
                    'codigo' => $codigo ?? N::codigo($nombre),
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'orden' => $i + 1,
                    'activo' => true,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        };

        $catalogo('fichas_tecnicas', [
            ['Computadora', 'Ficha de cómputo: procesador, memoria, almacenamiento, sistema operativo y periféricos'],
            ['Equipo de red', 'Ficha de red e impresión: dirección IP y versión de firmware'],
            ['Proyector', 'Ficha de proyector: tipo de cable y calidad de imagen'],
            ['Sin ficha específica', 'La categoría no tiene ficha técnica propia'],
        ]);

        $catalogo('tipos_almacenamiento', [
            ['SSD', 'Unidad de estado sólido'],
            ['HDD', 'Disco duro mecánico'],
        ]);

        $catalogo('componentes', [
            ['Lector'], ['Parlantes'], ['Teclado y mouse'], ['Monitor'], ['Cámara web'],
        ]);

        // Mantenimiento::TAREAS_CHECKLIST
        foreach ([
            1 => 'Inspección visual del estado físico',
            2 => 'Limpieza externa del equipo',
            3 => 'Limpieza interna de componentes, cuando corresponda',
            4 => 'Verificación de conexiones, cables y conectores',
            5 => 'Verificación de alimentación eléctrica',
            6 => 'Comprobación de encendido y apagado',
            7 => 'Prueba general de funcionamiento',
            8 => 'Identificación de daños, desgaste o anomalías',
            9 => 'Prueba final de funcionamiento',
        ] as $numero => $nombre) {
            DB::table('tareas_mantenimiento')->insert([
                'codigo' => 'TAREA_' . str_pad((string) $numero, 2, '0', STR_PAD_LEFT),
                'nombre' => $nombre, 'numero' => $numero, 'orden' => $numero, 'activo' => true,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }

        // resources/views/activos/show.blade.php:910-915 (value => etiqueta visible)
        $catalogo('tipos_anotacion', [
            ['Inspección Física', 'Inspección Física / Revisión de Rutina'],
            ['Revisión Técnica', 'Revisión Técnica de Hardware'],
            ['Actualización Software', 'Actualización de Software / SO'],
            ['Traslado de Ubicación', 'Traslado / Reubicación de Equipo'],
            ['Cambio de Responsable', 'Cambio de Usuario Responsable'],
            ['Observación General', 'Observación General / Nota Técnica'],
        ]);

        // Claves de módulo = sufijo de usuarios.permiso_*
        $catalogo('modulos', [
            ['Dashboard'], ['Activos Tecnológicos', null, [], 'ACTIVOS'], ['Incidentes'],
            ['Evaluación de Riesgos', null, [], 'RIESGOS'], ['Mantenimiento'],
            ['Informes y Reportes', null, [], 'INFORMES'], ['Gestión de Usuarios', null, [], 'USUARIOS'],
        ]);

        // AuditoriaInterna::ESTADOS / OPINIONES, AuditoriaMuestra::MOTIVOS_INCLUSION
        $catalogo('estados_auditoria', [
            ['Borrador', null, ['es_inicial' => true, 'es_inmutable' => false], 'BORRADOR'],
            ['En revisión', null, ['es_inicial' => false, 'es_inmutable' => false], 'EN_REVISION'],
            ['Aprobado', null, ['es_inicial' => false, 'es_inmutable' => true], 'APROBADO'],
        ]);
        $catalogo('opiniones_auditoria', [
            ['Satisfactorio', null, [], 'SATISFACTORIO'],
            ['Con observaciones', null, [], 'CON_OBSERVACIONES'],
            ['Insatisfactorio', null, [], 'INSATISFACTORIO'],
        ]);
        $catalogo('motivos_inclusion_muestra', [
            ['Riesgo Alto o Crítico', null, [], 'RIESGO_ALTO_CRITICO'],
            ['Aleatorio', null, [], 'ALEATORIO'],
        ]);

        // HallazgoAuditoria::APARTADOS + $refsRa del informe PDF
        $apartados = [
            '7.1' => ['Inventario y registro de activos', 'RA-01 / RA-04'],
            '7.2' => ['Ciclo de vida y vida útil', 'RA-01'],
            '7.3' => ['Mantenimiento', 'RA-02'],
            '7.4' => ['Gestión de incidentes', 'RA-03'],
            '7.5' => ['Roles y control de acceso', 'RA-05'],
            '7.6' => ['Seguimiento de hallazgos anteriores', null],
        ];
        $orden = 0;
        foreach ($apartados as $numero => [$nombre, $referencia]) {
            DB::table('apartados_auditoria')->insert([
                'codigo' => 'APARTADO_' . str_replace('.', '_', $numero), 'nombre' => $nombre, 'numero' => $numero,
                'reporte_referencia' => $referencia, 'orden' => ++$orden, 'activo' => true,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }

        // HallazgoAuditoria::CLASIFICACIONES + config('auditoria.plazos_dias')
        $catalogo('clasificaciones_hallazgo', [
            ['No Conformidad Mayor', null, ['plazo_dias' => 30, 'orden_gravedad' => 1], 'NC_MAYOR'],
            ['No Conformidad Menor', null, ['plazo_dias' => 90, 'orden_gravedad' => 2], 'NC_MENOR'],
            ['Observación', null, ['plazo_dias' => null, 'orden_gravedad' => 3], 'OBSERVACION'],
            ['Oportunidad de Mejora', null, ['plazo_dias' => null, 'orden_gravedad' => 4], 'OPORTUNIDAD_MEJORA'],
        ]);
        $catalogo('estados_hallazgo', [
            ['Abierto', null, ['es_cierre' => false], 'ABIERTO'],
            ['En proceso', null, ['es_cierre' => false], 'EN_PROCESO'],
            ['Cerrado', null, ['es_cierre' => true], 'CERRADO'],
        ]);

        // config('auditoria.criterios')
        $apartadoId = DB::table('apartados_auditoria')->pluck('id_apartado', 'numero');
        $criterios = [
            '7.1' => ['ISO/IEC 27001:2022 A.5.9', 'ISO 55001:2014 7.6'],
            '7.2' => ['ISO/IEC 27001:2022 A.7.13', 'ISO 55001:2014 8.1'],
            '7.3' => ['ISO/IEC 27001:2022 A.7.13', 'ISO 55001:2014 8.1'],
            '7.4' => ['ISO/IEC 27001:2022 A.5.24–A.5.28', 'ISO 55001:2014 10.1'],
            '7.5' => ['ISO/IEC 27001:2022 A.5.15–A.5.18', 'NAG 275'],
            '7.6' => ['ISO 19011:2018 §6.5', 'NAG 275', 'ISACA ITAF 2401'],
        ];
        foreach ($criterios as $numero => $textos) {
            foreach ($textos as $i => $texto) {
                DB::table('criterios_auditoria')->insert([
                    'fk_id_apartado' => $apartadoId[$numero], 'texto' => $texto, 'orden' => $i + 1,
                    'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            }
        }

        // config('auditoria.metas') + indicador informativo de etapas
        $indicadores = [
            ['EXACTITUD_INVENTARIO', 'Exactitud del inventario', '7.1', '>=', 95, '%'],
            ['ACTIVOS_SIN_UBICACION_PCT', 'Activos sin ubicación', '7.1', '<=', 2, '%'],
            ['VIDA_UTIL_VENCIDA_PCT', 'Activos con vida útil vencida', '7.2', '<=', 15, '%'],
            ['ACTIVOS_POR_ETAPA', 'Activos por etapa del ciclo de vida', '7.2', null, null, null],
            ['CUMPLIMIENTO_MANTENIMIENTO', 'Cumplimiento del plan de mantenimiento', '7.3', '>=', 90, '%'],
            ['PREVENTIVO_PCT', 'Mantenimiento preventivo', '7.3', '>=', 70, '%'],
            ['TMR_INCIDENTES_DIAS', 'Tiempo medio de resolución de incidentes', '7.4', '<=', 5, 'días'],
            ['INCIDENTES_ABIERTOS_PCT', 'Incidentes abiertos a la fecha de corte', '7.4', '<=', 5, '%'],
            ['ADMINS_SISTEMA', 'Usuarios con rol administrador', '7.5', '<=', 3, 'usuarios'],
            ['HALLAZGOS_ANTERIORES_CERRADOS', 'Hallazgos anteriores cerrados', '7.6', '>=', 80, '%'],
        ];
        foreach ($indicadores as $i => [$codigo, $nombre, $numero, $operador, $meta, $unidad]) {
            DB::table('indicadores_auditoria')->insert([
                'codigo' => $codigo, 'nombre' => $nombre, 'fk_id_apartado' => $apartadoId[$numero],
                'operador' => $operador, 'meta_valor' => $meta, 'unidad' => $unidad,
                'es_informativo' => $operador === null, 'orden' => $i + 1, 'activo' => true,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }

        // Valores que estaban escritos en config/auditoria.php y en el código del motor y los informes.
        $parametros = [
            ['muestra_porcentaje', '10', 'entero', '%', 'Porcentaje del inventario que entra a la muestra de auditoría'],
            ['muestra_minimo', '30', 'entero', 'activos', 'Tamaño mínimo de la muestra de auditoría'],
            ['semaforo_verde', '85', 'entero', '%', 'Cumplimiento mínimo de un apartado para semáforo verde'],
            ['semaforo_amarillo', '60', 'entero', '%', 'Cumplimiento mínimo de un apartado para semáforo amarillo'],
            ['codigo_prefijo_auditoria', 'INF-AUD-TI', 'texto', null, 'Prefijo del código de la auditoría (PREFIJO-AAAA-NN)'],
            ['vida_util_defecto_meses', '60', 'entero', 'meses', 'Vida útil que se asume si la categoría no la define'],
            ['antiguedad_defecto_meses', '12', 'entero', 'meses', 'Antigüedad que se asume si el activo no tiene fecha de adquisición'],
            ['umbral_reemplazo_meses', '12', 'entero', 'meses', 'Meses restantes de vida útil desde los que se sugiere reemplazo'],
            ['dias_aviso_mantenimiento', '3', 'entero', 'días', 'Anticipación con que se avisa un mantenimiento próximo'],
            ['plazo_mantenimiento_defecto_dias', '7', 'entero', 'días', 'Plazo del mantenimiento automático si el tratamiento no define fecha'],
        ];
        foreach ($parametros as [$clave, $valor, $tipo, $unidad, $descripcion]) {
            DB::table('parametros_sistema')->insert([
                'clave' => $clave, 'valor' => $valor, 'tipo_dato' => $tipo, 'unidad' => $unidad,
                'descripcion' => $descripcion, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        foreach ([
            'parametros_sistema', 'indicadores_auditoria', 'criterios_auditoria', 'estados_hallazgo',
            'clasificaciones_hallazgo', 'apartados_auditoria', 'motivos_inclusion_muestra',
            'opiniones_auditoria', 'estados_auditoria', 'modulos', 'tipos_anotacion',
            'tareas_mantenimiento', 'secciones', 'componentes', 'tipos_almacenamiento',
            'sistemas_operativos', 'procesadores', 'marcas', 'tipos_activo', 'fichas_tecnicas',
        ] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
