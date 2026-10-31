<?php

use Database\Support\NormalizacionMigracion as N;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once database_path('support/NormalizacionMigracion.php');

/**
 * FASE 3 · EXPANDIR (1/8). Los 16 catálogos creados el 6-oct con clave natural
 * (PK = texto) pasan al patrón estándar: id_<x>, codigo, nombre, descripcion,
 * orden, activo y timestamps. Los datos de los catálogos se conservan.
 *
 * Es una migración de expansión: NO cambia nombres de tabla ni quita columnas
 * que el código actual lee (puntaje_minimo, tipo_alerta, clave, valor…), para
 * que el sistema siga funcionando hasta el refactor de la Fase 4. Esas columnas
 * transitorias, y los renombres (estados_operativos → estados_activo,
 * origen_mantenimiento → origenes_mantenimiento), se resuelven en
 * database/contraccion/.
 *
 * Los vínculos de texto (p. ej. incidentes.estado_incidente → estados_incidente.nombre)
 * se vuelven a poner sobre `nombre` (UNIQUE) para que la base siga rechazando
 * valores fuera del vocabulario mientras convive con las nuevas fk_id_*.
 */
return new class extends Migration
{
    /** [tabla, columna, catálogo, columna referenciada] — mismos que el 6-oct. */
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

    /** Vínculos entre catálogos: [tabla, columna, catálogo, referencia, onDelete]. */
    private const INTERNOS = [
        ['niveles_riesgo', 'tipo_alerta', 'tipos_alerta', 'nombre', 'restrict'],
        ['niveles_riesgo', 'prioridad_sugerida', 'prioridades_incidente', 'nombre', 'restrict'],
        ['tratamientos_sugeridos', 'nivel_riesgo', 'niveles_riesgo', 'nombre', 'cascade'],
        ['tratamientos_sugeridos', 'estrategia', 'estrategias_tratamiento', 'nombre', 'restrict'],
    ];

    /** Orden de borrado: primero los que dependen de otros. */
    private const CATALOGOS = [
        'tratamientos_sugeridos', 'niveles_riesgo', 'estados_mantenimiento', 'tipos_mantenimiento',
        'estados_incidente', 'motivos_baja', 'etapas_ciclo_vida', 'estados_operativos',
        'origenes_tratamiento', 'estados_tratamiento', 'estrategias_tratamiento',
        'prioridades_incidente', 'tipos_alerta', 'niveles_msr', 'niveles_severidad', 'niveles_dependencia',
    ];

    public function up(): void
    {
        // Idempotencia: si ya se aplicó, no hacer nada.
        if (N::tiene('tipos_alerta', 'id_tipo_alerta')) {
            return;
        }

        $respaldo = $this->respaldar();

        $this->soltarVinculos();
        foreach (self::CATALOGOS as $tabla) {
            Schema::dropIfExists($tabla);
        }

        $this->crearEstructura();
        $this->sembrar($respaldo);
        $this->ponerVinculos();
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    private function respaldar(): array
    {
        $respaldo = [];
        foreach (self::CATALOGOS as $tabla) {
            $respaldo[$tabla] = Schema::hasTable($tabla)
                ? DB::table($tabla)->orderBy('orden')->get()->map(fn ($f) => (array) $f)->all()
                : [];
        }

        return $respaldo;
    }

    private function soltarVinculos(): void
    {
        foreach (self::INTERNOS as [$tabla, $columna]) {
            if (N::tiene($tabla)) {
                Schema::table($tabla, fn (Blueprint $t) => $t->dropForeign([$columna]));
            }
        }

        foreach (self::VINCULOS as [$tabla, $columna]) {
            Schema::table($tabla, fn (Blueprint $t) => $t->dropForeign([$columna]));
        }
    }

    private function ponerVinculos(): void
    {
        foreach (self::INTERNOS as [$tabla, $columna, $catalogo, $referencia, $alBorrar]) {
            Schema::table($tabla, function (Blueprint $t) use ($columna, $catalogo, $referencia, $alBorrar) {
                $fk = $t->foreign($columna)->references($referencia)->on($catalogo)->cascadeOnUpdate();
                $alBorrar === 'cascade' ? $fk->cascadeOnDelete() : $fk->restrictOnDelete();
            });
        }

        foreach (self::VINCULOS as [$tabla, $columna, $catalogo, $referencia]) {
            Schema::table($tabla, function (Blueprint $t) use ($columna, $catalogo, $referencia) {
                $t->foreign($columna)->references($referencia)->on($catalogo)
                    ->cascadeOnUpdate()->restrictOnDelete();
            });
        }
    }

    private function crearEstructura(): void
    {
        Schema::create('clasificaciones_estado', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_clasificacion_estado');
        });

        Schema::create('tipos_alerta', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_tipo_alerta');
            $t->string('color_hex', 7);
        });

        Schema::create('prioridades_incidente', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_prioridad_incidente');
            $t->unsignedTinyInteger('peso'); // 1 = más urgente
        });

        Schema::create('niveles_dependencia', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_nivel_dependencia');
            $t->unsignedTinyInteger('valor'); // transitoria: pasa a valores_dependencia por versión
        });

        Schema::create('niveles_severidad', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_nivel_severidad');
            $t->string('clave', 50)->unique(); // transitoria: el código actual referencia la clave
            $t->string('etiqueta', 100);       // transitoria: = nombre
            $t->unsignedTinyInteger('valor');  // transitoria: pasa a valores_severidad
        });

        Schema::create('niveles_msr', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_nivel_msr');
            $t->string('clave', 50)->unique();
            $t->string('etiqueta', 100);
            $t->unsignedTinyInteger('valor');
        });

        Schema::create('niveles_riesgo', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_nivel_riesgo');
            $t->string('criticidad', 100);
            $t->boolean('requiere_tratamiento')->default(false);
            $t->boolean('notifica_alerta')->default(false);
            $t->boolean('incluir_en_muestra')->default(false);
            $t->unsignedBigInteger('fk_id_tipo_alerta')->nullable();
            $t->unsignedBigInteger('fk_id_prioridad_sugerida')->nullable();
            // Transitorias: las lee el código actual (Catalogo::nivelPorScore).
            $t->decimal('puntaje_minimo', 4, 2);
            $t->string('tipo_alerta', 100);
            $t->string('prioridad_sugerida', 100);

            $t->foreign('fk_id_tipo_alerta')->references('id_tipo_alerta')->on('tipos_alerta')->cascadeOnUpdate()->restrictOnDelete();
            $t->foreign('fk_id_prioridad_sugerida')->references('id_prioridad_incidente')->on('prioridades_incidente')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('estrategias_tratamiento', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_estrategia_tratamiento');
            $t->boolean('genera_mantenimiento')->default(false);
        });

        Schema::create('estados_tratamiento', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_estado_tratamiento');
            $t->boolean('es_abierto')->default(false);
            $t->boolean('es_inicial')->default(false);
            $t->boolean('es_cierre')->default(false);
        });

        Schema::create('origenes_tratamiento', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_origen_tratamiento');
        });

        Schema::create('tratamientos_sugeridos', function (Blueprint $t) {
            $t->id('id_tratamiento_sugerido');
            $t->unsignedBigInteger('fk_id_version_criterio')->nullable(); // FK se agrega con versiones_criterios
            $t->unsignedBigInteger('fk_id_nivel_riesgo')->nullable();
            $t->unsignedBigInteger('fk_id_estrategia_tratamiento')->nullable();
            $t->text('accion_preventiva');
            $t->unsignedSmallInteger('dias_plazo');
            $t->unsignedTinyInteger('orden')->default(0);
            // Transitorias: las lee Catalogo::tratamientoSugerido().
            $t->string('nivel_riesgo', 100);
            $t->string('estrategia', 100);
            $t->timestamps();

            $t->foreign('fk_id_nivel_riesgo')->references('id_nivel_riesgo')->on('niveles_riesgo')->cascadeOnUpdate()->cascadeOnDelete();
            $t->foreign('fk_id_estrategia_tratamiento')->references('id_estrategia_tratamiento')->on('estrategias_tratamiento')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('estados_operativos', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_estado_activo');
            $t->boolean('es_baja')->default(false);
            $t->unsignedBigInteger('fk_id_clasificacion_estado')->nullable();

            $t->foreign('fk_id_clasificacion_estado')->references('id_clasificacion_estado')->on('clasificaciones_estado')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('etapas_ciclo_vida', fn (Blueprint $t) => N::columnasCatalogo($t, 'id_etapa_ciclo_vida'));
        Schema::create('motivos_baja', fn (Blueprint $t) => N::columnasCatalogo($t, 'id_motivo_baja'));

        Schema::create('estados_incidente', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_estado_incidente');
            $t->boolean('cierra_incidente')->default(false);
            $t->boolean('es_inicial')->default(false);
        });

        Schema::create('tipos_mantenimiento', fn (Blueprint $t) => N::columnasCatalogo($t, 'id_tipo_mantenimiento'));

        Schema::create('estados_mantenimiento', function (Blueprint $t) {
            N::columnasCatalogo($t, 'id_estado_mantenimiento');
            $t->boolean('es_abierto')->default(false);
            $t->boolean('es_finalizado')->default(false);
            $t->boolean('es_inicial')->default(false);
        });
    }

    private function sembrar(array $respaldo): void
    {
        $ahora = now();
        $base = fn (array $fila, ?string $nombre = null) => [
            'codigo' => null, // se completa en insertar()
            'nombre' => $nombre ?? $fila['nombre'],
            'descripcion' => $fila['descripcion'] ?? null,
            'orden' => $fila['orden'] ?? 0,
            'activo' => true,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ];

        // Clasificación del estado del activo (misma regla que InformesController::clasificarEstadoActivo).
        foreach (['Óptimo', 'Regular', 'Obsoleto'] as $i => $nombre) {
            DB::table('clasificaciones_estado')->insert([
                'codigo' => N::codigo($nombre), 'nombre' => $nombre, 'orden' => $i + 1,
                'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
        $clasificacion = DB::table('clasificaciones_estado')->pluck('id_clasificacion_estado', 'nombre');

        $insertar = function (string $tabla, array $fila, string $texto) {
            $fila['codigo'] = N::codigoUnico($tabla, $texto);
            DB::table($tabla)->insert($fila);
        };

        foreach ($respaldo['tipos_alerta'] as $f) {
            $insertar('tipos_alerta', $base($f) + ['color_hex' => $f['color_hex']], $f['nombre']);
        }

        foreach ($respaldo['prioridades_incidente'] as $f) {
            $insertar('prioridades_incidente', $base($f) + ['peso' => $f['peso']], $f['nombre']);
        }

        foreach ($respaldo['niveles_dependencia'] as $f) {
            $insertar('niveles_dependencia', $base($f) + ['valor' => $f['valor']], $f['nombre']);
        }

        foreach (['niveles_severidad', 'niveles_msr'] as $tabla) {
            foreach ($respaldo[$tabla] as $f) {
                $insertar($tabla, $base($f, $f['etiqueta']) + [
                    'clave' => $f['clave'], 'etiqueta' => $f['etiqueta'], 'valor' => $f['valor'],
                ], $f['clave']);
            }
        }

        $tipoAlerta = DB::table('tipos_alerta')->pluck('id_tipo_alerta', 'nombre');
        $prioridad = DB::table('prioridades_incidente')->pluck('id_prioridad_incidente', 'nombre');
        foreach ($respaldo['niveles_riesgo'] as $f) {
            $insertar('niveles_riesgo', $base($f) + [
                'criticidad' => $f['criticidad'],
                'requiere_tratamiento' => (bool) $f['requiere_tratamiento'],
                'notifica_alerta' => (bool) $f['notifica_alerta'],
                'incluir_en_muestra' => in_array($f['nombre'], ['Crítico', 'Alto'], true),
                'fk_id_tipo_alerta' => $tipoAlerta[$f['tipo_alerta']] ?? null,
                'fk_id_prioridad_sugerida' => $prioridad[$f['prioridad_sugerida']] ?? null,
                'puntaje_minimo' => $f['puntaje_minimo'],
                'tipo_alerta' => $f['tipo_alerta'],
                'prioridad_sugerida' => $f['prioridad_sugerida'],
            ], $f['nombre']);
        }

        foreach ($respaldo['estrategias_tratamiento'] as $f) {
            $insertar('estrategias_tratamiento', $base($f) + ['genera_mantenimiento' => (bool) $f['genera_mantenimiento']], $f['nombre']);
        }

        foreach ($respaldo['estados_tratamiento'] as $f) {
            $insertar('estados_tratamiento', $base($f) + [
                'es_abierto' => (bool) $f['es_abierto'],
                'es_inicial' => $f['nombre'] === 'Pendiente',
                'es_cierre' => $f['nombre'] === 'Completado',
            ], $f['nombre']);
        }

        foreach ($respaldo['origenes_tratamiento'] as $f) {
            $insertar('origenes_tratamiento', $base($f), $f['nombre']);
        }

        $nivel = DB::table('niveles_riesgo')->pluck('id_nivel_riesgo', 'nombre');
        $estrategia = DB::table('estrategias_tratamiento')->pluck('id_estrategia_tratamiento', 'nombre');
        foreach ($respaldo['tratamientos_sugeridos'] as $f) {
            DB::table('tratamientos_sugeridos')->insert([
                'fk_id_nivel_riesgo' => $nivel[$f['nivel_riesgo']] ?? null,
                'fk_id_estrategia_tratamiento' => $estrategia[$f['estrategia']] ?? null,
                'accion_preventiva' => $f['accion_preventiva'],
                'dias_plazo' => $f['dias_plazo'],
                'orden' => $f['orden'] ?? 0,
                'nivel_riesgo' => $f['nivel_riesgo'],
                'estrategia' => $f['estrategia'],
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }

        foreach ($respaldo['estados_operativos'] as $f) {
            $minusculas = mb_strtolower($f['nombre']);
            $clase = match (true) {
                $this->contiene($minusculas, ['inoperativo', 'baja', 'obsolet', 'cambio', 'dañad']) => 'Obsoleto',
                $this->contiene($minusculas, ['mantenimiento', 'regular', 'antiguo', 'revis', 'medio']) => 'Regular',
                default => 'Óptimo',
            };

            $insertar('estados_operativos', $base($f) + [
                'es_baja' => $f['nombre'] === 'De Baja',
                'fk_id_clasificacion_estado' => $clasificacion[$clase],
            ], $f['nombre']);
        }

        foreach (['etapas_ciclo_vida', 'motivos_baja', 'tipos_mantenimiento'] as $tabla) {
            foreach ($respaldo[$tabla] as $f) {
                $insertar($tabla, $base($f), $f['nombre']);
            }
        }

        foreach ($respaldo['estados_incidente'] as $f) {
            $insertar('estados_incidente', $base($f) + [
                'cierra_incidente' => (bool) $f['cierra_incidente'],
                'es_inicial' => $f['nombre'] === 'Reportado',
            ], $f['nombre']);
        }

        foreach ($respaldo['estados_mantenimiento'] as $f) {
            $insertar('estados_mantenimiento', $base($f) + [
                'es_abierto' => (bool) $f['es_abierto'],
                'es_finalizado' => (bool) $f['es_finalizado'],
                'es_inicial' => $f['nombre'] === 'Programado',
            ], $f['nombre']);
        }
    }

    private function contiene(string $texto, array $fragmentos): bool
    {
        foreach ($fragmentos as $fragmento) {
            if (str_contains($texto, $fragmento)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vuelve a la estructura del 6-oct (clave natural). Los datos de los
     * catálogos se conservan; se pierden solo las columnas nuevas.
     */
    public function down(): void
    {
        $respaldo = $this->respaldar();

        $this->soltarVinculos();
        foreach (self::CATALOGOS as $tabla) {
            Schema::dropIfExists($tabla);
        }
        Schema::dropIfExists('clasificaciones_estado');

        $this->crearEstructuraAnterior();

        $filas = fn (string $tabla, array $mapa) => collect($respaldo[$tabla])->map(function ($f) use ($mapa) {
            $nueva = [];
            foreach ($mapa as $destino => $origen) {
                $nueva[$destino] = is_callable($origen) ? $origen($f) : ($f[$origen] ?? null);
            }

            return $nueva;
        })->all();

        DB::table('niveles_dependencia')->insert($filas('niveles_dependencia', ['nombre' => 'nombre', 'valor' => 'valor', 'orden' => 'orden']));
        foreach (['niveles_severidad', 'niveles_msr'] as $t) {
            DB::table($t)->insert($filas($t, ['clave' => 'clave', 'etiqueta' => 'etiqueta', 'valor' => 'valor', 'descripcion' => fn ($f) => $f['descripcion'] ?? '', 'orden' => 'orden']));
        }
        DB::table('tipos_alerta')->insert($filas('tipos_alerta', ['nombre' => 'nombre', 'color_hex' => 'color_hex', 'orden' => 'orden']));
        DB::table('prioridades_incidente')->insert($filas('prioridades_incidente', ['nombre' => 'nombre', 'descripcion' => 'descripcion', 'peso' => 'peso', 'orden' => 'orden']));
        DB::table('niveles_riesgo')->insert($filas('niveles_riesgo', [
            'nombre' => 'nombre', 'criticidad' => 'criticidad', 'puntaje_minimo' => 'puntaje_minimo',
            'tipo_alerta' => 'tipo_alerta', 'prioridad_sugerida' => 'prioridad_sugerida',
            'requiere_tratamiento' => 'requiere_tratamiento', 'notifica_alerta' => 'notifica_alerta', 'orden' => 'orden',
        ]));
        DB::table('estrategias_tratamiento')->insert($filas('estrategias_tratamiento', ['nombre' => 'nombre', 'genera_mantenimiento' => 'genera_mantenimiento', 'orden' => 'orden']));
        DB::table('estados_tratamiento')->insert($filas('estados_tratamiento', ['nombre' => 'nombre', 'es_abierto' => 'es_abierto', 'orden' => 'orden']));
        DB::table('origenes_tratamiento')->insert($filas('origenes_tratamiento', ['nombre' => 'nombre', 'orden' => 'orden']));
        DB::table('tratamientos_sugeridos')->insert($filas('tratamientos_sugeridos', [
            'nivel_riesgo' => 'nivel_riesgo', 'estrategia' => 'estrategia', 'accion_preventiva' => 'accion_preventiva',
            'dias_plazo' => 'dias_plazo', 'orden' => 'orden',
        ]));
        foreach (['estados_operativos', 'etapas_ciclo_vida', 'motivos_baja'] as $t) {
            DB::table($t)->insert($filas($t, ['nombre' => 'nombre', 'orden' => 'orden']));
        }
        DB::table('estados_incidente')->insert($filas('estados_incidente', ['nombre' => 'nombre', 'descripcion' => 'descripcion', 'cierra_incidente' => 'cierra_incidente', 'orden' => 'orden']));
        DB::table('tipos_mantenimiento')->insert($filas('tipos_mantenimiento', ['nombre' => 'nombre', 'descripcion' => 'descripcion', 'orden' => 'orden']));
        DB::table('estados_mantenimiento')->insert($filas('estados_mantenimiento', ['nombre' => 'nombre', 'es_abierto' => 'es_abierto', 'es_finalizado' => 'es_finalizado', 'orden' => 'orden']));

        $this->ponerVinculos();
    }

    private function crearEstructuraAnterior(): void
    {
        $natural = fn (string $tabla, ?Closure $extra = null) => Schema::create($tabla, function (Blueprint $t) use ($extra) {
            $t->string('nombre', 100)->primary();
            if ($extra) {
                $extra($t);
            }
            $t->unsignedTinyInteger('orden');
        });

        $natural('niveles_dependencia', fn ($t) => $t->unsignedTinyInteger('valor'));

        foreach (['niveles_severidad', 'niveles_msr'] as $tabla) {
            Schema::create($tabla, function (Blueprint $t) {
                $t->string('clave', 50)->primary();
                $t->string('etiqueta', 100);
                $t->unsignedTinyInteger('valor');
                $t->text('descripcion');
                $t->unsignedTinyInteger('orden');
            });
        }

        $natural('tipos_alerta', fn ($t) => $t->string('color_hex', 7));
        $natural('prioridades_incidente', function ($t) {
            $t->string('descripcion')->nullable();
            $t->unsignedTinyInteger('peso');
        });

        Schema::create('niveles_riesgo', function (Blueprint $t) {
            $t->string('nombre', 100)->primary();
            $t->string('criticidad', 100);
            $t->decimal('puntaje_minimo', 4, 2);
            $t->string('tipo_alerta', 100);
            $t->string('prioridad_sugerida', 100);
            $t->boolean('requiere_tratamiento')->default(false);
            $t->boolean('notifica_alerta')->default(false);
            $t->unsignedTinyInteger('orden');
        });

        $natural('estrategias_tratamiento', fn ($t) => $t->boolean('genera_mantenimiento')->default(false));
        $natural('estados_tratamiento', fn ($t) => $t->boolean('es_abierto')->default(false));
        $natural('origenes_tratamiento');

        Schema::create('tratamientos_sugeridos', function (Blueprint $t) {
            $t->string('nivel_riesgo', 100)->primary();
            $t->string('estrategia', 100);
            $t->text('accion_preventiva');
            $t->unsignedSmallInteger('dias_plazo');
            $t->unsignedTinyInteger('orden');
        });

        $natural('estados_operativos');
        $natural('etapas_ciclo_vida');
        $natural('motivos_baja');
        $natural('estados_incidente', function ($t) {
            $t->string('descripcion')->nullable();
            $t->boolean('cierra_incidente')->default(false);
        });
        $natural('tipos_mantenimiento', fn ($t) => $t->string('descripcion')->nullable());
        $natural('estados_mantenimiento', function ($t) {
            $t->boolean('es_abierto')->default(false);
            $t->boolean('es_finalizado')->default(false);
        });
    }
};
