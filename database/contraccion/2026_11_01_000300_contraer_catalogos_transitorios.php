<?php

use Database\Support\NormalizacionMigracion as N;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once database_path('support/NormalizacionMigracion.php');

/**
 * FASE 3 · CONTRAER (3/4). Columnas transitorias de los catálogos.
 *
 * Desde aquí los valores numéricos viven solo en las tablas versionadas
 * (valores_*, umbrales_riesgo, reglas_frecuencia) y los catálogos quedan
 * como vocabulario puro.
 *
 * Requiere haber corrido antes 000100 y 000200: las FK de texto que apuntaban
 * a `nombre`/`clave` ya no existen.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->verificar();

        N::soltarColumnas('niveles_riesgo', ['puntaje_minimo', 'tipo_alerta', 'prioridad_sugerida']);
        N::soltarColumnas('niveles_severidad', ['clave', 'etiqueta', 'valor']);
        N::soltarColumnas('niveles_msr', ['clave', 'etiqueta', 'valor']);
        N::soltarColumna('niveles_dependencia', 'valor');
        N::soltarColumnas('tratamientos_sugeridos', ['nivel_riesgo', 'estrategia']);

        foreach ([
            ['tratamientos_sugeridos', 'fk_id_version_criterio'],
            ['tratamientos_sugeridos', 'fk_id_nivel_riesgo'],
            ['tratamientos_sugeridos', 'fk_id_estrategia_tratamiento'],
            ['niveles_riesgo', 'fk_id_tipo_alerta'],
            ['niveles_riesgo', 'fk_id_prioridad_sugerida'],
        ] as [$tabla, $fk]) {
            Schema::table($tabla, fn (Blueprint $t) => $t->unsignedBigInteger($fk)->nullable(false)->change());
        }

        // Reemplazada por reglas_frecuencia (versionada).
        Schema::dropIfExists('niveles_frecuencia');
    }

    private function verificar(): void
    {
        $vigente = DB::table('versiones_criterios')->where('vigente', true)->value('id_version_criterio');
        N::abortarSi($vigente === null ? 1 : 0, 'falta una versión de criterios vigente.');

        // Cada nivel debe tener su valor/umbral en la versión vigente.
        foreach ([
            ['niveles_severidad', 'valores_severidad', 'fk_id_nivel_severidad', 'id_nivel_severidad'],
            ['niveles_msr', 'valores_msr', 'fk_id_nivel_msr', 'id_nivel_msr'],
            ['niveles_dependencia', 'valores_dependencia', 'fk_id_nivel_dependencia', 'id_nivel_dependencia'],
            ['niveles_riesgo', 'umbrales_riesgo', 'fk_id_nivel_riesgo', 'id_nivel_riesgo'],
        ] as [$catalogo, $valores, $fk, $pk]) {
            $sinValor = DB::table("{$catalogo} as c")
                ->leftJoin("{$valores} as v", fn ($j) => $j->on("v.{$fk}", '=', "c.{$pk}")->where('v.fk_id_version_criterio', $vigente))
                ->whereNull("v.{$fk}")
                ->count();
            N::abortarSi($sinValor, "nivel(es) de «{$catalogo}» sin valor en la versión vigente.");
        }

        // El valor guardado en el catálogo debe ser el mismo que el versionado.
        foreach ([['niveles_severidad', 'valores_severidad', 'fk_id_nivel_severidad', 'id_nivel_severidad'], ['niveles_msr', 'valores_msr', 'fk_id_nivel_msr', 'id_nivel_msr']] as [$catalogo, $valores, $fk, $pk]) {
            if (!N::tiene($catalogo, 'valor')) {
                continue;
            }
            $distintos = DB::table("{$catalogo} as c")
                ->join("{$valores} as v", fn ($j) => $j->on("v.{$fk}", '=', "c.{$pk}")->where('v.fk_id_version_criterio', $vigente))
                ->whereColumn('v.valor', '<>', 'c.valor')
                ->count();
            N::abortarSi($distintos, "valor(es) de «{$catalogo}» distintos de la versión vigente.");
        }

        if (N::tiene('niveles_riesgo', 'puntaje_minimo')) {
            $distintos = DB::table('niveles_riesgo as n')
                ->join('umbrales_riesgo as u', fn ($j) => $j->on('u.fk_id_nivel_riesgo', '=', 'n.id_nivel_riesgo')->where('u.fk_id_version_criterio', $vigente))
                ->whereColumn('u.puntaje_minimo', '<>', 'n.puntaje_minimo')
                ->count();
            N::abortarSi($distintos, 'umbral(es) de riesgo distintos entre niveles_riesgo y la versión vigente.');
        }

        N::abortarSi(DB::table('niveles_riesgo')->whereNull('fk_id_tipo_alerta')->count(), 'nivel(es) de riesgo sin tipo de alerta.');
        N::abortarSi(DB::table('niveles_riesgo')->whereNull('fk_id_prioridad_sugerida')->count(), 'nivel(es) de riesgo sin prioridad sugerida.');
        N::abortarSi(DB::table('tratamientos_sugeridos')->whereNull('fk_id_nivel_riesgo')->orWhereNull('fk_id_estrategia_tratamiento')->orWhereNull('fk_id_version_criterio')->count(), 'tratamiento(s) sugerido(s) sin FK completa.');

        if (Schema::hasTable('niveles_frecuencia')) {
            $antes = DB::table('niveles_frecuencia')->count();
            $reglas = DB::table('reglas_frecuencia')->where('fk_id_version_criterio', $vigente)->count();
            N::abortarSi($reglas < $antes ? $antes - $reglas : 0, 'nivel(es) de frecuencia sin su regla en reglas_frecuencia.');
        }
    }

    public function down(): void
    {
        foreach ([
            ['tratamientos_sugeridos', 'fk_id_version_criterio'], ['tratamientos_sugeridos', 'fk_id_nivel_riesgo'],
            ['tratamientos_sugeridos', 'fk_id_estrategia_tratamiento'], ['niveles_riesgo', 'fk_id_tipo_alerta'],
            ['niveles_riesgo', 'fk_id_prioridad_sugerida'],
        ] as [$tabla, $fk]) {
            Schema::table($tabla, fn (Blueprint $t) => $t->unsignedBigInteger($fk)->nullable()->change());
        }

        $vigente = DB::table('versiones_criterios')->where('vigente', true)->value('id_version_criterio');

        if (!N::tiene('niveles_riesgo', 'puntaje_minimo')) {
            Schema::table('niveles_riesgo', function (Blueprint $t) {
                $t->decimal('puntaje_minimo', 4, 2)->nullable();
            });
            DB::statement('UPDATE niveles_riesgo SET puntaje_minimo =
                (SELECT u.puntaje_minimo FROM umbrales_riesgo u
                  WHERE u.fk_id_nivel_riesgo = niveles_riesgo.id_nivel_riesgo
                    AND u.fk_id_version_criterio = ' . (int) $vigente . ')');
        }
        N::restaurarTexto('niveles_riesgo', 'tipo_alerta', 'fk_id_tipo_alerta', 'tipos_alerta', 'id_tipo_alerta');
        N::restaurarTexto('niveles_riesgo', 'prioridad_sugerida', 'fk_id_prioridad_sugerida', 'prioridades_incidente', 'id_prioridad_incidente');

        foreach ([['niveles_severidad', 'valores_severidad', 'fk_id_nivel_severidad', 'id_nivel_severidad'], ['niveles_msr', 'valores_msr', 'fk_id_nivel_msr', 'id_nivel_msr']] as [$catalogo, $valores, $fk, $pk]) {
            if (N::tiene($catalogo, 'clave')) {
                continue;
            }
            Schema::table($catalogo, function (Blueprint $t) {
                $t->string('clave', 50)->nullable();
                $t->string('etiqueta', 100)->nullable();
                $t->unsignedTinyInteger('valor')->nullable();
            });
            DB::statement("UPDATE {$catalogo} SET clave = codigo, etiqueta = nombre, valor =
                (SELECT v.valor FROM {$valores} v WHERE v.{$fk} = {$catalogo}.{$pk} AND v.fk_id_version_criterio = " . (int) $vigente . ')');
        }

        if (!N::tiene('niveles_dependencia', 'valor')) {
            Schema::table('niveles_dependencia', fn (Blueprint $t) => $t->unsignedTinyInteger('valor')->nullable());
            DB::statement('UPDATE niveles_dependencia SET valor =
                (SELECT v.valor FROM valores_dependencia v WHERE v.fk_id_nivel_dependencia = niveles_dependencia.id_nivel_dependencia
                  AND v.fk_id_version_criterio = ' . (int) $vigente . ')');
        }

        if (!N::tiene('tratamientos_sugeridos', 'nivel_riesgo')) {
            Schema::table('tratamientos_sugeridos', function (Blueprint $t) {
                $t->string('nivel_riesgo', 100)->nullable();
                $t->string('estrategia', 100)->nullable();
            });
            DB::statement('UPDATE tratamientos_sugeridos SET
                nivel_riesgo = (SELECT n.nombre FROM niveles_riesgo n WHERE n.id_nivel_riesgo = tratamientos_sugeridos.fk_id_nivel_riesgo),
                estrategia = (SELECT e.nombre FROM estrategias_tratamiento e WHERE e.id_estrategia_tratamiento = tratamientos_sugeridos.fk_id_estrategia_tratamiento)');
        }

        if (!Schema::hasTable('niveles_frecuencia')) {
            Schema::create('niveles_frecuencia', function (Blueprint $t) {
                $t->string('nombre', 100)->primary();
                $t->unsignedTinyInteger('valor');
                $t->decimal('desgaste_minimo', 5, 2)->nullable();
                $t->unsignedTinyInteger('incidentes_minimo')->nullable();
                $t->string('operador', 3)->nullable();
                $t->unsignedTinyInteger('orden');
            });
            foreach (DB::table('reglas_frecuencia')->where('fk_id_version_criterio', $vigente)->get() as $r) {
                DB::table('niveles_frecuencia')->insert([
                    'nombre' => $r->nombre, 'valor' => $r->valor, 'desgaste_minimo' => $r->desgaste_minimo,
                    'incidentes_minimo' => $r->incidentes_minimo, 'operador' => $r->operador, 'orden' => $r->orden,
                ]);
            }
        }
    }
};
