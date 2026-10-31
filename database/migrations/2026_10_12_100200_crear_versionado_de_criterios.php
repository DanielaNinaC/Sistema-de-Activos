<?php

use Database\Support\NormalizacionMigracion as N;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once database_path('support/NormalizacionMigracion.php');

/**
 * FASE 3 · EXPANDIR (3/8). Versionado de los criterios del motor de riesgo
 * (ISO 31000 §6.3.4 definición de los criterios; §6.7 registro e informe).
 *
 * Un encabezado `versiones_criterios` agrupa escalas (severidad, MSR,
 * dependencia), reglas de frecuencia, umbrales de nivel, pesos AHP y
 * tratamientos sugeridos. Cada evaluación guarda la versión con la que se
 * calculó, de modo que un resultado antiguo se puede explicar aunque después
 * cambien los criterios.
 *
 * Datos existentes: se crea una versión por cada fila histórica de `pesos_ahp`
 * (vigente_desde = su fecha de actualización). Las escalas hoy solo existen en
 * una versión, así que todas las versiones históricas comparten esos valores;
 * es lo único que el sistema conserva y queda documentado aquí.
 *
 * Los pesos de la tesis (0.648 / 0.230 / 0.122 · 0.75 / 0.25) son los que el
 * motor aplicaba por defecto; si no hay ningún `pesos_ahp` vigente se siembran
 * como dato explícito, de modo que el cálculo no cambia (equivalencia = 0).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (N::tiene('versiones_criterios')) {
            return;
        }

        Schema::create('versiones_criterios', function (Blueprint $t) {
            $t->id('id_version_criterio');
            $t->string('nombre', 150);
            $t->boolean('vigente')->default(false);
            // Columna generada: 1 si es la vigente, NULL si no. Su UNIQUE garantiza una sola vigente.
            $t->boolean('vigente_unico')->nullable()->virtualAs('CASE WHEN vigente = 1 THEN 1 ELSE NULL END');
            $t->dateTime('vigente_desde');
            $t->dateTime('vigente_hasta')->nullable();
            $t->unsignedBigInteger('fk_id_usuario_aprueba')->nullable();
            $t->text('motivo_cambio')->nullable();
            $t->timestamps();

            $t->unique('vigente_unico');
            $t->foreign('fk_id_usuario_aprueba')->references('id_usuario')->on('usuarios')->cascadeOnUpdate()->nullOnDelete();
        });

        $valores = function (string $tabla, string $pk, string $fkNivel, string $nivelTabla, string $nivelPk, bool $descripcion) {
            Schema::create($tabla, function (Blueprint $t) use ($pk, $fkNivel, $nivelTabla, $nivelPk, $descripcion) {
                $t->id($pk);
                $t->unsignedBigInteger('fk_id_version_criterio');
                $t->unsignedBigInteger($fkNivel);
                $t->unsignedTinyInteger('valor');
                if ($descripcion) {
                    $t->text('descripcion');
                }
                $t->timestamps();

                $t->unique(['fk_id_version_criterio', $fkNivel]);
                $t->foreign('fk_id_version_criterio')->references('id_version_criterio')->on('versiones_criterios')->cascadeOnUpdate()->restrictOnDelete();
                $t->foreign($fkNivel)->references($nivelPk)->on($nivelTabla)->cascadeOnUpdate()->restrictOnDelete();
            });
        };

        $valores('valores_severidad', 'id_valor_severidad', 'fk_id_nivel_severidad', 'niveles_severidad', 'id_nivel_severidad', true);
        $valores('valores_msr', 'id_valor_msr', 'fk_id_nivel_msr', 'niveles_msr', 'id_nivel_msr', true);
        $valores('valores_dependencia', 'id_valor_dependencia', 'fk_id_nivel_dependencia', 'niveles_dependencia', 'id_nivel_dependencia', false);

        Schema::create('umbrales_riesgo', function (Blueprint $t) {
            $t->id('id_umbral_riesgo');
            $t->unsignedBigInteger('fk_id_version_criterio');
            $t->unsignedBigInteger('fk_id_nivel_riesgo');
            $t->decimal('puntaje_minimo', 4, 2);
            $t->timestamps();

            $t->unique(['fk_id_version_criterio', 'fk_id_nivel_riesgo']);
            $t->foreign('fk_id_version_criterio')->references('id_version_criterio')->on('versiones_criterios')->cascadeOnUpdate()->restrictOnDelete();
            $t->foreign('fk_id_nivel_riesgo')->references('id_nivel_riesgo')->on('niveles_riesgo')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::create('reglas_frecuencia', function (Blueprint $t) {
            $t->id('id_regla_frecuencia');
            $t->unsignedBigInteger('fk_id_version_criterio');
            $t->string('nombre', 100);
            $t->unsignedTinyInteger('valor');
            $t->decimal('desgaste_minimo', 5, 2)->nullable();
            $t->unsignedTinyInteger('incidentes_minimo')->nullable();
            $t->string('operador', 3)->nullable(); // 'AND' / 'OR'; NULL = regla por defecto
            $t->boolean('es_por_defecto')->default(false);
            $t->unsignedTinyInteger('orden');
            $t->timestamps();

            $t->unique(['fk_id_version_criterio', 'nombre']);
            $t->foreign('fk_id_version_criterio')->references('id_version_criterio')->on('versiones_criterios')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::table('pesos_ahp', function (Blueprint $t) {
            $t->unsignedBigInteger('fk_id_version_criterio')->nullable()->unique();
        });

        Schema::table('evaluacion_riesgo', function (Blueprint $t) {
            $t->unsignedBigInteger('fk_id_version_criterio')->nullable();
            $t->unsignedBigInteger('fk_id_nivel_riesgo')->nullable();
        });

        $this->poblar();

        Schema::table('pesos_ahp', function (Blueprint $t) {
            $t->foreign('fk_id_version_criterio')->references('id_version_criterio')->on('versiones_criterios')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::table('evaluacion_riesgo', function (Blueprint $t) {
            $t->foreign('fk_id_version_criterio')->references('id_version_criterio')->on('versiones_criterios')->cascadeOnUpdate()->restrictOnDelete();
            $t->foreign('fk_id_nivel_riesgo')->references('id_nivel_riesgo')->on('niveles_riesgo')->cascadeOnUpdate()->restrictOnDelete();
            $t->index('fk_id_nivel_riesgo');
        });

        Schema::table('tratamientos_sugeridos', function (Blueprint $t) {
            $t->foreign('fk_id_version_criterio')->references('id_version_criterio')->on('versiones_criterios')->cascadeOnUpdate()->restrictOnDelete();
            $t->unique(['fk_id_version_criterio', 'fk_id_nivel_riesgo']);
        });
    }

    /** Idempotente: puede re-ejecutarse tras cargar datos con el código anterior. */
    public function poblar(): void
    {
        $ahora = now();

        // 1. Asegurar que exista al menos un pesos_ahp vigente (equivale al valor por defecto que usaba el motor).
        if (!DB::table('pesos_ahp')->where('vigente', true)->exists()) {
            DB::table('pesos_ahp')->insert([
                'peso_severidad' => 0.648, 'peso_frecuencia' => 0.230, 'peso_msr' => 0.122,
                'peso_riesgo_tecnico' => 0.750, 'peso_dependencia' => 0.250,
                'vigente' => true, 'fecha_actualizacion' => $ahora, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }

        // 2. Una versión por fila de pesos_ahp que todavía no tenga.
        $filas = DB::table('pesos_ahp')->whereNull('fk_id_version_criterio')
            ->orderBy('fecha_actualizacion')->orderBy('id_config')->get();
        $existentes = DB::table('versiones_criterios')->count();

        foreach ($filas as $i => $peso) {
            $idVersion = DB::table('versiones_criterios')->insertGetId([
                'nombre' => 'Criterios v' . ($existentes + $i + 1),
                'vigente' => (bool) $peso->vigente,
                'vigente_desde' => $peso->fecha_actualizacion ?? $peso->created_at ?? $ahora,
                'vigente_hasta' => null,
                'fk_id_usuario_aprueba' => null,
                'motivo_cambio' => 'Carga inicial desde pesos_ahp durante la normalización de la base de datos',
                'created_at' => $ahora, 'updated_at' => $ahora,
            ]);

            DB::table('pesos_ahp')->where('id_config', $peso->id_config)->update(['fk_id_version_criterio' => $idVersion]);
            $this->copiarEscalas($idVersion, $ahora);
        }

        // 3. Cierre de vigencias: cada versión no vigente termina donde empieza la siguiente.
        $versiones = DB::table('versiones_criterios')->orderBy('vigente_desde')->orderBy('id_version_criterio')->get();
        foreach ($versiones as $i => $v) {
            $siguiente = $versiones[$i + 1] ?? null;
            DB::table('versiones_criterios')->where('id_version_criterio', $v->id_version_criterio)
                ->update(['vigente_hasta' => (!$v->vigente && $siguiente) ? $siguiente->vigente_desde : null]);
        }

        // 4. Tratamientos sugeridos pertenecen a la versión vigente.
        $vigente = DB::table('versiones_criterios')->where('vigente', true)->value('id_version_criterio');
        if ($vigente) {
            DB::table('tratamientos_sugeridos')->whereNull('fk_id_version_criterio')->update(['fk_id_version_criterio' => $vigente]);
        }

        // 5. Evaluaciones: versión que regía en su fecha (o la primera si es anterior) y nivel como FK.
        DB::statement("UPDATE evaluacion_riesgo SET fk_id_version_criterio = COALESCE(
            (SELECT v.id_version_criterio FROM versiones_criterios v
              WHERE v.vigente_desde <= evaluacion_riesgo.fecha_evaluacion
              ORDER BY v.vigente_desde DESC, v.id_version_criterio DESC LIMIT 1),
            (SELECT v.id_version_criterio FROM versiones_criterios v
              ORDER BY v.vigente_desde ASC, v.id_version_criterio ASC LIMIT 1))
            WHERE fk_id_version_criterio IS NULL");

        DB::statement('UPDATE evaluacion_riesgo SET fk_id_nivel_riesgo =
            (SELECT n.id_nivel_riesgo FROM niveles_riesgo n WHERE n.nombre = evaluacion_riesgo.nivel_riesgo)
            WHERE fk_id_nivel_riesgo IS NULL');
    }

    private function copiarEscalas(int $idVersion, $ahora): void
    {
        $marca = ['fk_id_version_criterio' => $idVersion, 'created_at' => $ahora, 'updated_at' => $ahora];

        foreach (DB::table('niveles_severidad')->get() as $n) {
            DB::table('valores_severidad')->insert($marca + [
                'fk_id_nivel_severidad' => $n->id_nivel_severidad, 'valor' => $n->valor, 'descripcion' => $n->descripcion ?? '',
            ]);
        }

        foreach (DB::table('niveles_msr')->get() as $n) {
            DB::table('valores_msr')->insert($marca + [
                'fk_id_nivel_msr' => $n->id_nivel_msr, 'valor' => $n->valor, 'descripcion' => $n->descripcion ?? '',
            ]);
        }

        foreach (DB::table('niveles_dependencia')->get() as $n) {
            DB::table('valores_dependencia')->insert($marca + [
                'fk_id_nivel_dependencia' => $n->id_nivel_dependencia, 'valor' => $n->valor,
            ]);
        }

        foreach (DB::table('niveles_riesgo')->get() as $n) {
            DB::table('umbrales_riesgo')->insert($marca + [
                'fk_id_nivel_riesgo' => $n->id_nivel_riesgo, 'puntaje_minimo' => $n->puntaje_minimo,
            ]);
        }

        foreach (DB::table('niveles_frecuencia')->orderBy('orden')->get() as $r) {
            DB::table('reglas_frecuencia')->insert($marca + [
                'nombre' => $r->nombre, 'valor' => $r->valor, 'desgaste_minimo' => $r->desgaste_minimo,
                'incidentes_minimo' => $r->incidentes_minimo, 'operador' => $r->operador,
                'es_por_defecto' => $r->operador === null, 'orden' => $r->orden,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('tratamientos_sugeridos', function (Blueprint $t) {
            $t->dropForeign(['fk_id_version_criterio']);
            $t->dropUnique(['fk_id_version_criterio', 'fk_id_nivel_riesgo']);
        });

        Schema::table('evaluacion_riesgo', function (Blueprint $t) {
            $t->dropForeign(['fk_id_version_criterio']);
            $t->dropForeign(['fk_id_nivel_riesgo']);
            $t->dropIndex(['fk_id_nivel_riesgo']);
            $t->dropColumn(['fk_id_version_criterio', 'fk_id_nivel_riesgo']);
        });

        Schema::table('pesos_ahp', function (Blueprint $t) {
            $t->dropForeign(['fk_id_version_criterio']);
            $t->dropUnique(['fk_id_version_criterio']);
            $t->dropColumn('fk_id_version_criterio');
        });

        foreach (['reglas_frecuencia', 'umbrales_riesgo', 'valores_dependencia', 'valores_msr', 'valores_severidad', 'versiones_criterios'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
