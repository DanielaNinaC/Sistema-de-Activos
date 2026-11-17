<?php

use Database\Support\NormalizacionMigracion as N;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once database_path('support/NormalizacionMigracion.php');

/**
 * FASE 3 · CONTRAER (1/4). Activos, ubicaciones y categorías.
 *
 * NO vive en database/migrations: `php artisan migrate` no la ejecuta. Se corre
 * a mano, SOLO después de la Fase 4 (código ya leyendo las fk_id_*):
 *
 *     php artisan migrate --path=database/contraccion
 *
 * Antes de borrar cualquier columna se verifica que su contenido ya vive en la
 * FK nueva; si falta algo, aborta sin tocar nada (regla "no borrar datos").
 */
return new class extends Migration
{
    private const TEXTOS_ACTIVO = [
        'marca', 'modelo', 'estado_operativo', 'etapa_ciclo_vida', 'nivel_riesgo',
        'motivo_baja', 'estado_previo_baja', 'fecha_baja', 'fk_id_usuario_baja',
    ];

    public function up(): void
    {
        $this->verificar();

        // Las bajas pasan a bajas_activos: sus 4 columnas en activos se eliminan.
        N::soltarColumnas('activos_tecnologicos', self::TEXTOS_ACTIVO);
        // La categoría se deriva de tipo → categoría (decisión D-2).
        N::soltarColumna('activos_tecnologicos', 'fk_id_categoria');

        foreach (['fk_id_tipo_activo', 'fk_id_estado_activo', 'fk_id_etapa_ciclo_vida'] as $fk) {
            Schema::table('activos_tecnologicos', fn (Blueprint $t) => $t->unsignedBigInteger($fk)->nullable(false)->change());
        }

        N::soltarColumnas('ubicaciones', ['seccion', 'aula']);
        N::soltarColumna('categorias', 'nivel_dependencia');

        // vida_util_meses nunca quedó vacía en la práctica: la que falte toma el parámetro del sistema.
        $porDefecto = DB::table('parametros_sistema')->where('clave', 'vida_util_defecto_meses')->value('valor');
        N::abortarSi(
            ($porDefecto === null && DB::table('categorias')->whereNull('vida_util_meses')->exists()) ? 1 : 0,
            'categoría(s) sin vida útil y sin parámetro vida_util_defecto_meses.'
        );
        if ($porDefecto !== null) {
            DB::table('categorias')->whereNull('vida_util_meses')->update(['vida_util_meses' => (int) $porDefecto]);
        }
        Schema::table('categorias', fn (Blueprint $t) => $t->integer('vida_util_meses')->nullable(false)->change());
    }

    private function verificar(): void
    {
        foreach ([
            ['fk_id_tipo_activo', null],
            ['fk_id_estado_activo', 'estado_operativo'],
            ['fk_id_etapa_ciclo_vida', 'etapa_ciclo_vida'],
            ['fk_id_nivel_riesgo', 'nivel_riesgo'],
            ['fk_id_modelo', 'marca'],
            ['fk_id_modelo', 'modelo'],
        ] as [$fk, $texto]) {
            N::verificarCompleto('activos_tecnologicos', $fk, $texto);
        }

        // La categoría que se va a derivar debe ser la misma que hoy tiene el activo.
        if (N::tiene('activos_tecnologicos', 'fk_id_categoria')) {
            $distintas = DB::table('activos_tecnologicos as a')
                ->join('tipos_activo as t', 't.id_tipo_activo', '=', 'a.fk_id_tipo_activo')
                ->whereColumn('t.fk_id_categoria', '<>', 'a.fk_id_categoria')
                ->count();
            N::abortarSi($distintas, 'activo(s) cuya categoría no coincide con la de su tipo.');
        }

        // Cada dato de baja debe existir ya en bajas_activos y coincidir.
        $sinBaja = DB::table('activos_tecnologicos as a')
            ->leftJoin('bajas_activos as b', 'b.fk_id_activo', '=', 'a.id_activo')
            ->whereNull('b.fk_id_activo')
            ->where(fn ($q) => $q->whereNotNull('a.fecha_baja')->orWhereNotNull('a.motivo_baja')
                ->orWhereNotNull('a.estado_previo_baja')->orWhereNotNull('a.fk_id_usuario_baja'))
            ->count();
        N::abortarSi($sinBaja, 'activo(s) con datos de baja que no están en bajas_activos.');

        $diferentes = DB::table('activos_tecnologicos as a')
            ->join('bajas_activos as b', 'b.fk_id_activo', '=', 'a.id_activo')
            ->whereNotNull('a.fecha_baja')
            ->where(fn ($q) => $q->whereNull('b.fecha_baja')->orWhereColumn('b.fecha_baja', '<>', 'a.fecha_baja'))
            ->count();
        N::abortarSi($diferentes, 'baja(s) cuya fecha difiere entre activos y bajas_activos.');

        N::verificarCompleto('ubicaciones', 'fk_id_seccion');
        N::abortarSi(DB::table('ubicaciones')->whereNull('nombre')->count(), 'ubicación(es) sin nombre normalizado.');
        N::verificarCompleto('categorias', 'fk_id_nivel_dependencia', 'nivel_dependencia');
    }

    public function down(): void
    {
        Schema::table('categorias', fn (Blueprint $t) => $t->integer('vida_util_meses')->nullable()->change());
        N::restaurarTexto('categorias', 'nivel_dependencia', 'fk_id_nivel_dependencia', 'niveles_dependencia', 'id_nivel_dependencia');

        N::restaurarTexto('ubicaciones', 'seccion', 'fk_id_seccion', 'secciones', 'id_seccion');
        if (!N::tiene('ubicaciones', 'aula')) {
            Schema::table('ubicaciones', fn (Blueprint $t) => $t->string('aula', 150)->nullable());
            DB::statement('UPDATE ubicaciones SET aula = nombre');
        }
        if (!N::tieneIndice('ubicaciones', 'ubicaciones_seccion_aula_unique')) {
            Schema::table('ubicaciones', fn (Blueprint $t) => $t->unique(['seccion', 'aula'], 'ubicaciones_seccion_aula_unique'));
        }

        foreach (['fk_id_tipo_activo', 'fk_id_estado_activo', 'fk_id_etapa_ciclo_vida'] as $fk) {
            Schema::table('activos_tecnologicos', fn (Blueprint $t) => $t->unsignedBigInteger($fk)->nullable()->change());
        }

        $t = 'activos_tecnologicos';
        N::restaurarTexto($t, 'estado_operativo', 'fk_id_estado_activo', 'estados_operativos', 'id_estado_activo');
        N::restaurarTexto($t, 'etapa_ciclo_vida', 'fk_id_etapa_ciclo_vida', 'etapas_ciclo_vida', 'id_etapa_ciclo_vida');
        N::restaurarTexto($t, 'nivel_riesgo', 'fk_id_nivel_riesgo', 'niveles_riesgo', 'id_nivel_riesgo');

        if (!N::tieneIndice($t, 'idx_activos_estado')) {
            Schema::table($t, fn (Blueprint $tabla) => $tabla->index('estado_operativo', 'idx_activos_estado'));
        }

        if (!N::tiene($t, 'marca')) {
            Schema::table($t, function (Blueprint $tabla) {
                $tabla->string('marca', 150)->nullable();
                $tabla->string('modelo', 150)->nullable();
            });
            // Los marcadores 'Sin marca' / '(sin modelo)' vuelven a ser NULL, como estaba el dato original.
            DB::statement("UPDATE {$t} SET
                marca = (SELECT CASE WHEN m.nombre = ? THEN NULL ELSE m.nombre END
                           FROM modelos mo JOIN marcas m ON m.id_marca = mo.fk_id_marca WHERE mo.id_modelo = {$t}.fk_id_modelo),
                modelo = (SELECT CASE WHEN mo.nombre = ? THEN NULL ELSE mo.nombre END
                           FROM modelos mo WHERE mo.id_modelo = {$t}.fk_id_modelo)
                WHERE fk_id_modelo IS NOT NULL", [N::SIN_MARCA, N::SIN_MODELO]);
        }

        if (!N::tiene($t, 'fecha_baja')) {
            Schema::table($t, function (Blueprint $tabla) {
                $tabla->date('fecha_baja')->nullable();
                $tabla->string('motivo_baja', 100)->nullable();
                $tabla->string('estado_previo_baja', 100)->nullable();
                $tabla->unsignedBigInteger('fk_id_usuario_baja')->nullable();
            });
            DB::statement("UPDATE {$t} SET
                fecha_baja = (SELECT b.fecha_baja FROM bajas_activos b WHERE b.fk_id_activo = {$t}.id_activo),
                fk_id_usuario_baja = (SELECT b.fk_id_usuario_baja FROM bajas_activos b WHERE b.fk_id_activo = {$t}.id_activo),
                motivo_baja = (SELECT m.nombre FROM bajas_activos b JOIN motivos_baja m ON m.id_motivo_baja = b.fk_id_motivo_baja WHERE b.fk_id_activo = {$t}.id_activo),
                estado_previo_baja = (SELECT e.nombre FROM bajas_activos b JOIN estados_operativos e ON e.id_estado_activo = b.fk_id_estado_previo WHERE b.fk_id_activo = {$t}.id_activo)
                WHERE id_activo IN (SELECT fk_id_activo FROM bajas_activos)");
        }

        if (!N::tiene($t, 'fk_id_categoria')) {
            Schema::table($t, fn (Blueprint $tabla) => $tabla->unsignedBigInteger('fk_id_categoria')->nullable());
            DB::statement("UPDATE {$t} SET fk_id_categoria =
                (SELECT ti.fk_id_categoria FROM tipos_activo ti WHERE ti.id_tipo_activo = {$t}.fk_id_tipo_activo)");
            Schema::table($t, fn (Blueprint $tabla) => $tabla->foreign('fk_id_categoria')->references('id_categoria')->on('categorias')->cascadeOnUpdate()->restrictOnDelete());
        }
    }
};
