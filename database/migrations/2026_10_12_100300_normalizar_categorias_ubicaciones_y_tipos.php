<?php

use Database\Support\NormalizacionMigracion as N;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once database_path('support/NormalizacionMigracion.php');

/**
 * FASE 3 · EXPANDIR (4/8). Categorías, ubicaciones y tipos de activo.
 *
 *  - categorias: nivel de dependencia como FK y bandera es_red (reemplaza a
 *    config('auditoria.categorias_red')).
 *  - ubicaciones: la sección sale a su propio catálogo `secciones`; la
 *    ubicación queda con (fk_id_seccion, nombre) y un código corto. Las
 *    variantes de escritura ("P12" / "p12", espacios dobles) se unifican y las
 *    ubicaciones que resultan idénticas se fusionan reasignando sus activos.
 *  - tipos_activo: un tipo por categoría, con la ficha técnica que hoy deduce
 *    ActivoTecnologicoController::tipoDetalle() por subcadenas del nombre.
 *    Se replica esa misma regla para no cambiar comportamiento (R-4 del
 *    diseño); a partir de aquí la ficha es un dato editable, no un `str_contains`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (N::tiene('categorias', 'fk_id_nivel_dependencia')) {
            return;
        }

        Schema::table('categorias', function (Blueprint $t) {
            $t->unsignedBigInteger('fk_id_nivel_dependencia')->nullable();
            $t->boolean('es_red')->default(false);
        });

        Schema::table('ubicaciones', function (Blueprint $t) {
            $t->unsignedBigInteger('fk_id_seccion')->nullable();
            $t->string('codigo', 100)->nullable();
            $t->string('nombre', 150)->nullable();
        });

        $this->poblar();

        Schema::table('categorias', function (Blueprint $t) {
            $t->foreign('fk_id_nivel_dependencia')->references('id_nivel_dependencia')->on('niveles_dependencia')->cascadeOnUpdate()->restrictOnDelete();
        });

        Schema::table('ubicaciones', function (Blueprint $t) {
            $t->foreign('fk_id_seccion')->references('id_seccion')->on('secciones')->cascadeOnUpdate()->restrictOnDelete();
            $t->unique('codigo');
            $t->unique(['fk_id_seccion', 'nombre']);
        });
    }

    /** Idempotente. */
    public function poblar(): void
    {
        $this->poblarCategorias();
        $this->poblarUbicaciones();
        $this->poblarTipos();
    }

    private function poblarCategorias(): void
    {
        DB::statement('UPDATE categorias SET fk_id_nivel_dependencia =
            (SELECT n.id_nivel_dependencia FROM niveles_dependencia n WHERE n.nombre = categorias.nivel_dependencia)
            WHERE fk_id_nivel_dependencia IS NULL');

        $idsRed = array_filter((array) config('auditoria.categorias_red', []));
        if ($idsRed) {
            DB::table('categorias')->whereIn('id_categoria', $idsRed)->update(['es_red' => true]);
        }
    }

    private function poblarUbicaciones(): void
    {
        $ahora = now();
        $pendientes = DB::table('ubicaciones')->whereNull('fk_id_seccion')->get();
        if ($pendientes->isEmpty()) {
            return;
        }

        // 1. Secciones: grafía canónica por clave, conservando las que ya existen.
        $porSeccion = [];
        foreach ($pendientes as $u) {
            $texto = N::limpiar($u->seccion);
            $porSeccion[$texto] = ($porSeccion[$texto] ?? 0) + 1;
        }
        $canonicasSeccion = N::canonicos($porSeccion);

        $seccionId = []; // clave => id
        foreach (DB::table('secciones')->get() as $s) {
            $seccionId[N::clave($s->nombre)] = $s->id_seccion;
        }
        $orden = (int) DB::table('secciones')->max('orden');
        foreach ($canonicasSeccion as $clave => $nombre) {
            if (isset($seccionId[$clave])) {
                continue;
            }
            $seccionId[$clave] = DB::table('secciones')->insertGetId([
                'codigo' => N::codigoUnico('secciones', $nombre), 'nombre' => $nombre,
                'orden' => min(255, ++$orden), 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }

        // 2. Nombre de la ubicación: grafía canónica del aula dentro de cada sección.
        $aulas = [];
        foreach ($pendientes as $u) {
            $sid = $seccionId[N::clave($u->seccion)];
            $aula = N::limpiar($u->aula);
            $aulas[$sid][$aula === '' ? 'General' : $aula] = ($aulas[$sid][$aula === '' ? 'General' : $aula] ?? 0) + 1;
        }
        $canonicasAula = array_map(fn ($conteos) => N::canonicos($conteos), $aulas);

        // 3. Asignar y fusionar duplicados (misma sección y nombre canónico).
        $superviviente = []; // "sid|nombre" => id_ubicacion
        foreach (DB::table('ubicaciones')->whereNotNull('fk_id_seccion')->get() as $u) {
            $superviviente[$u->fk_id_seccion . '|' . N::clave($u->nombre)] = $u->id_ubicacion;
        }

        foreach ($pendientes->sortBy('id_ubicacion') as $u) {
            $sid = $seccionId[N::clave($u->seccion)];
            $aula = N::limpiar($u->aula) ?: 'General';
            $nombre = $canonicasAula[$sid][N::clave($aula)] ?? $aula;
            $llave = $sid . '|' . N::clave($nombre);

            if (isset($superviviente[$llave])) {
                // Duplicado: sus activos pasan a la ubicación superviviente y la fila sobrante se elimina.
                DB::table('activos_tecnologicos')->where('fk_id_ubicacion', $u->id_ubicacion)
                    ->update(['fk_id_ubicacion' => $superviviente[$llave]]);
                DB::table('ubicaciones')->where('id_ubicacion', $u->id_ubicacion)->delete();
                continue;
            }

            $nombreSeccion = DB::table('secciones')->where('id_seccion', $sid)->value('nombre');
            DB::table('ubicaciones')->where('id_ubicacion', $u->id_ubicacion)->update([
                'fk_id_seccion' => $sid,
                'nombre' => $nombre,
                'codigo' => N::codigoUnico('ubicaciones', $nombreSeccion . ' ' . $nombre),
            ]);
            $superviviente[$llave] = $u->id_ubicacion;
        }
    }

    private function poblarTipos(): void
    {
        $ahora = now();
        $ficha = DB::table('fichas_tecnicas')->pluck('id_ficha_tecnica', 'codigo');
        $yaTienen = DB::table('tipos_activo')->pluck('fk_id_categoria')->all();
        $orden = (int) DB::table('tipos_activo')->max('orden');

        foreach (DB::table('categorias')->orderBy('id_categoria')->get() as $c) {
            if (in_array($c->id_categoria, $yaTienen, true)) {
                continue;
            }

            $nombre = strtolower(trim($c->nombre_categoria));
            $codigoFicha = match (true) {
                $this->contiene($nombre, ['computadora', 'pc', 'laptop']) => 'COMPUTADORA',
                $this->contiene($nombre, ['red', 'servidor', 'switch', 'router', 'impresora', 'printer']) => 'EQUIPO_DE_RED',
                $this->contiene($nombre, ['proyector']) => 'PROYECTOR',
                default => 'SIN_FICHA_ESPECIFICA',
            };

            DB::table('tipos_activo')->insert([
                'codigo' => N::codigoUnico('tipos_activo', $c->nombre_categoria),
                'nombre' => N::limpiar($c->nombre_categoria),
                'fk_id_categoria' => $c->id_categoria,
                'fk_id_ficha_tecnica' => $ficha[$codigoFicha],
                'orden' => min(255, ++$orden), 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora,
            ]);
        }
    }

    private function contiene(string $texto, array $fragmentos): bool
    {
        foreach ($fragmentos as $f) {
            if (str_contains($texto, $f)) {
                return true;
            }
        }

        return false;
    }

    public function down(): void
    {
        Schema::table('ubicaciones', function (Blueprint $t) {
            $t->dropForeign(['fk_id_seccion']);
            $t->dropUnique(['codigo']);
            $t->dropUnique(['fk_id_seccion', 'nombre']);
            $t->dropColumn(['fk_id_seccion', 'codigo', 'nombre']);
        });

        Schema::table('categorias', function (Blueprint $t) {
            $t->dropForeign(['fk_id_nivel_dependencia']);
            $t->dropColumn(['fk_id_nivel_dependencia', 'es_red']);
        });

        // Los tipos y secciones creados se eliminan al revertir la migración de catálogos de dominio.
        DB::table('tipos_activo')->delete();
        DB::table('secciones')->delete();
    }
};
