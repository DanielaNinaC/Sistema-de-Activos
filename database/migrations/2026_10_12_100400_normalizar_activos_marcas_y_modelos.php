<?php

use Database\Support\NormalizacionMigracion as N;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once database_path('support/NormalizacionMigracion.php');

/**
 * FASE 3 · EXPANDIR (5/8). Activos: tipo, marca/modelo, estado, etapa, nivel
 * de riesgo y baja.
 *
 *  - marcas / modelos: se crean a partir de los textos libres `marca` y
 *    `modelo` con las variantes de escritura unificadas (la grafía más
 *    frecuente de cada una). El modelo determina la marca (3FN).
 *  - FK compuesta (fk_id_modelo, fk_id_tipo_activo) → modelos: la base impide
 *    asignar a un activo un modelo de otro tipo.
 *  - bajas_activos: los 4 datos de baja (fecha, motivo, estado previo, usuario)
 *    salen de `activos_tecnologicos`, donde eran nulos salvo en 'De Baja'.
 *
 * Las columnas de texto originales siguen en su lugar hasta database/contraccion/.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (N::tiene('activos_tecnologicos', 'fk_id_tipo_activo')) {
            return;
        }

        Schema::create('modelos', function (Blueprint $t) {
            $t->id('id_modelo');
            $t->unsignedBigInteger('fk_id_marca');
            $t->unsignedBigInteger('fk_id_tipo_activo');
            $t->string('nombre', 150);
            $t->string('descripcion')->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();

            $t->unique(['fk_id_marca', 'fk_id_tipo_activo', 'nombre']);
            // Referencia de la FK compuesta de activos_tecnologicos.
            $t->unique(['id_modelo', 'fk_id_tipo_activo']);
            $t->foreign('fk_id_marca')->references('id_marca')->on('marcas')->cascadeOnUpdate()->restrictOnDelete();
            $t->foreign('fk_id_tipo_activo')->references('id_tipo_activo')->on('tipos_activo')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::table('activos_tecnologicos', function (Blueprint $t) {
            $t->unsignedBigInteger('fk_id_tipo_activo')->nullable();
            $t->unsignedBigInteger('fk_id_modelo')->nullable();
            $t->unsignedBigInteger('fk_id_estado_activo')->nullable();
            $t->unsignedBigInteger('fk_id_etapa_ciclo_vida')->nullable();
            $t->unsignedBigInteger('fk_id_nivel_riesgo')->nullable();
            $t->index('nombre_equipo');
        });

        Schema::create('bajas_activos', function (Blueprint $t) {
            $t->unsignedBigInteger('fk_id_activo')->primary();
            $t->date('fecha_baja')->nullable(); // histórico: hay bajas sin fecha; no se inventa
            $t->unsignedBigInteger('fk_id_motivo_baja')->nullable(); // histórico: hay bajas sin motivo registrado
            $t->unsignedBigInteger('fk_id_estado_previo')->nullable();
            $t->unsignedBigInteger('fk_id_usuario_baja')->nullable();
            $t->timestamps();

            $t->foreign('fk_id_activo')->references('id_activo')->on('activos_tecnologicos')->cascadeOnUpdate()->cascadeOnDelete();
            $t->foreign('fk_id_motivo_baja')->references('id_motivo_baja')->on('motivos_baja')->cascadeOnUpdate()->restrictOnDelete();
            $t->foreign('fk_id_estado_previo')->references('id_estado_activo')->on('estados_operativos')->cascadeOnUpdate()->restrictOnDelete();
            $t->foreign('fk_id_usuario_baja')->references('id_usuario')->on('usuarios')->cascadeOnUpdate()->nullOnDelete();
        });

        $this->poblar();

        Schema::table('activos_tecnologicos', function (Blueprint $t) {
            $t->foreign('fk_id_tipo_activo')->references('id_tipo_activo')->on('tipos_activo')->cascadeOnUpdate()->restrictOnDelete();
            $t->foreign(['fk_id_modelo', 'fk_id_tipo_activo'])
                ->references(['id_modelo', 'fk_id_tipo_activo'])->on('modelos')->cascadeOnUpdate()->restrictOnDelete();
            $t->foreign('fk_id_estado_activo')->references('id_estado_activo')->on('estados_operativos')->cascadeOnUpdate()->restrictOnDelete();
            $t->foreign('fk_id_etapa_ciclo_vida')->references('id_etapa_ciclo_vida')->on('etapas_ciclo_vida')->cascadeOnUpdate()->restrictOnDelete();
            $t->foreign('fk_id_nivel_riesgo')->references('id_nivel_riesgo')->on('niveles_riesgo')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    /** Idempotente: solo toca filas que aún no tienen su FK. */
    public function poblar(): void
    {
        DB::statement('UPDATE activos_tecnologicos SET fk_id_tipo_activo =
            (SELECT t.id_tipo_activo FROM tipos_activo t WHERE t.fk_id_categoria = activos_tecnologicos.fk_id_categoria)
            WHERE fk_id_tipo_activo IS NULL');

        DB::statement('UPDATE activos_tecnologicos SET fk_id_estado_activo =
            (SELECT e.id_estado_activo FROM estados_operativos e WHERE e.nombre = activos_tecnologicos.estado_operativo)
            WHERE fk_id_estado_activo IS NULL');

        DB::statement('UPDATE activos_tecnologicos SET fk_id_etapa_ciclo_vida =
            (SELECT e.id_etapa_ciclo_vida FROM etapas_ciclo_vida e WHERE e.nombre = activos_tecnologicos.etapa_ciclo_vida)
            WHERE fk_id_etapa_ciclo_vida IS NULL');

        DB::statement('UPDATE activos_tecnologicos SET fk_id_nivel_riesgo =
            (SELECT n.id_nivel_riesgo FROM niveles_riesgo n WHERE n.nombre = activos_tecnologicos.nivel_riesgo)
            WHERE fk_id_nivel_riesgo IS NULL AND nivel_riesgo IS NOT NULL');

        $this->poblarMarcasYModelos();
        $this->poblarBajas();
    }

    private function poblarMarcasYModelos(): void
    {
        $ahora = now();
        $activos = DB::table('activos_tecnologicos')
            ->whereNull('fk_id_modelo')
            ->where(fn ($q) => $q->whereNotNull('marca')->orWhereNotNull('modelo'))
            ->get(['id_activo', 'fk_id_tipo_activo', 'marca', 'modelo']);

        $activos = $activos->filter(fn ($a) => N::limpiar($a->marca) !== '' || N::limpiar($a->modelo) !== '');
        if ($activos->isEmpty()) {
            return;
        }

        // Marcas: grafía canónica por clave; se reutilizan las que ya existen.
        $conteoMarca = [];
        foreach ($activos as $a) {
            $m = N::limpiar($a->marca);
            if ($m !== '') {
                $conteoMarca[$m] = ($conteoMarca[$m] ?? 0) + 1;
            }
        }
        $canonicaMarca = N::canonicos($conteoMarca);

        $marcaId = [];
        foreach (DB::table('marcas')->get() as $m) {
            $marcaId[N::clave($m->nombre)] = $m->id_marca;
        }
        $obtenerMarca = function (string $nombre) use (&$marcaId, $ahora): int {
            $clave = N::clave($nombre);
            if (!isset($marcaId[$clave])) {
                $marcaId[$clave] = DB::table('marcas')->insertGetId([
                    'codigo' => N::codigoUnico('marcas', $nombre), 'nombre' => $nombre,
                    'orden' => 0, 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            }

            return $marcaId[$clave];
        };

        // Modelos: grafía canónica dentro de cada (marca, tipo).
        $conteoModelo = [];
        foreach ($activos as $a) {
            $marca = N::limpiar($a->marca) !== '' ? $canonicaMarca[N::clave($a->marca)] : N::SIN_MARCA;
            $modelo = N::limpiar($a->modelo) !== '' ? N::limpiar($a->modelo) : N::SIN_MODELO;
            $conteoModelo[N::clave($marca) . '|' . $a->fk_id_tipo_activo][$modelo] = ($conteoModelo[N::clave($marca) . '|' . $a->fk_id_tipo_activo][$modelo] ?? 0) + 1;
        }
        $canonicaModelo = array_map(fn ($c) => N::canonicos($c), $conteoModelo);

        $modeloId = [];
        foreach (DB::table('modelos')->get() as $m) {
            $modeloId[$m->fk_id_marca . '|' . $m->fk_id_tipo_activo . '|' . N::clave($m->nombre)] = $m->id_modelo;
        }

        foreach ($activos as $a) {
            $marca = N::limpiar($a->marca) !== '' ? $canonicaMarca[N::clave($a->marca)] : N::SIN_MARCA;
            $modeloTexto = N::limpiar($a->modelo) !== '' ? N::limpiar($a->modelo) : N::SIN_MODELO;
            $grupo = N::clave($marca) . '|' . $a->fk_id_tipo_activo;
            $modelo = $canonicaModelo[$grupo][N::clave($modeloTexto)] ?? $modeloTexto;

            $idMarca = $obtenerMarca($marca);
            $llave = $idMarca . '|' . $a->fk_id_tipo_activo . '|' . N::clave($modelo);

            if (!isset($modeloId[$llave])) {
                $modeloId[$llave] = DB::table('modelos')->insertGetId([
                    'fk_id_marca' => $idMarca, 'fk_id_tipo_activo' => $a->fk_id_tipo_activo, 'nombre' => $modelo,
                    'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            }

            DB::table('activos_tecnologicos')->where('id_activo', $a->id_activo)->update(['fk_id_modelo' => $modeloId[$llave]]);
        }
    }

    private function poblarBajas(): void
    {
        $yaRegistrados = DB::table('bajas_activos')->pluck('fk_id_activo')->all();

        $motivo = DB::table('motivos_baja')->pluck('id_motivo_baja', 'nombre');
        $estado = DB::table('estados_operativos')->pluck('id_estado_activo', 'nombre');
        $ahora = now();

        $candidatos = DB::table('activos_tecnologicos')
            ->where(fn ($q) => $q->where('estado_operativo', 'De Baja')->orWhereNotNull('fecha_baja')->orWhereNotNull('motivo_baja'))
            ->get(['id_activo', 'fecha_baja', 'motivo_baja', 'estado_previo_baja', 'fk_id_usuario_baja']);

        foreach ($candidatos as $a) {
            if (in_array($a->id_activo, $yaRegistrados, true)) {
                continue;
            }

            DB::table('bajas_activos')->insert([
                'fk_id_activo' => $a->id_activo,
                'fecha_baja' => $a->fecha_baja,
                'fk_id_motivo_baja' => $motivo[$a->motivo_baja] ?? null,
                'fk_id_estado_previo' => $estado[$a->estado_previo_baja] ?? null,
                'fk_id_usuario_baja' => $a->fk_id_usuario_baja,
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('activos_tecnologicos', function (Blueprint $t) {
            $t->dropForeign(['fk_id_modelo', 'fk_id_tipo_activo']);
            $t->dropForeign(['fk_id_tipo_activo']);
            $t->dropForeign(['fk_id_estado_activo']);
            $t->dropForeign(['fk_id_etapa_ciclo_vida']);
            $t->dropForeign(['fk_id_nivel_riesgo']);
            $t->dropIndex(['nombre_equipo']);
            $t->dropColumn(['fk_id_tipo_activo', 'fk_id_modelo', 'fk_id_estado_activo', 'fk_id_etapa_ciclo_vida', 'fk_id_nivel_riesgo']);
        });

        Schema::dropIfExists('bajas_activos');
        Schema::dropIfExists('modelos');
        DB::table('marcas')->delete();
    }
};
