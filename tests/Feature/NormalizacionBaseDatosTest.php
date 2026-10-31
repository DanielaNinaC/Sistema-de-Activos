<?php

namespace Tests\Feature;

use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\Ubicacion;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Normalización a 3FN (Fase 3): población idempotente de las fk_id_* a partir
 * de los textos antiguos y migraciones de contracción con verificación previa.
 *
 * No usa RefreshDatabase (transacción envolvente) ni DatabaseMigrations (que
 * hace rollback de TODAS las migraciones al terminar): la contracción altera
 * el esquema, así que cada prueba migra una base en memoria propia.
 */
class NormalizacionBaseDatosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', ['--force' => true]);
    }

    private function crearActivosAntiguos(): void
    {
        $categoria = Categoria::create(['nombre_categoria' => 'Computadoras', 'vida_util_meses' => 60, 'nivel_dependencia' => 'Media']);
        $ubicacion = Ubicacion::create(['seccion' => 'Primaria', 'aula' => 'A1']);

        foreach ([
            ['PC-1', 'HP', 'ProDesk 400', 'Operativo'],
            ['PC-2', 'hp ', 'PRODESK 400', 'Operativo'],
            ['PC-3', null, null, 'De Baja'],
        ] as [$codigo, $marca, $modelo, $estado]) {
            ActivoTecnologico::create([
                'fk_id_categoria' => $categoria->id_categoria,
                'fk_id_ubicacion' => $ubicacion->id_ubicacion,
                'codigo_patrimonial' => $codigo,
                'nombre_equipo' => $codigo,
                'marca' => $marca,
                'modelo' => $modelo,
                'estado_operativo' => $estado,
                'fecha_adquisicion' => '2024-01-01',
            ]);
        }
    }

    public function test_sincronizar_completa_las_fk_de_datos_con_texto_antiguo(): void
    {
        $this->crearActivosAntiguos();

        $this->artisan('normalizacion:sincronizar')->assertSuccessful();

        $this->assertSame(0, DB::table('activos_tecnologicos')->whereNull('fk_id_tipo_activo')->count());
        $this->assertSame(0, DB::table('activos_tecnologicos')->whereNull('fk_id_estado_activo')->count());
        $this->assertSame(0, DB::table('activos_tecnologicos')->whereNull('fk_id_etapa_ciclo_vida')->count());

        // Las variantes "HP" / "hp " y "ProDesk 400" / "PRODESK 400" son una sola marca y un solo modelo.
        $this->assertSame(1, DB::table('marcas')->where('nombre', 'HP')->count());
        $this->assertSame(1, DB::table('modelos')->where('nombre', 'ProDesk 400')->count());
        $this->assertSame(
            1,
            DB::table('activos_tecnologicos')->whereNotNull('fk_id_modelo')->distinct()->count('fk_id_modelo')
        );

        // La ubicación queda con su sección en el catálogo.
        $this->assertNotNull(DB::table('ubicaciones')->value('fk_id_seccion'));

        // La baja sin fecha ni motivo conserva su NULL: no se inventa información.
        $baja = DB::table('bajas_activos')->first();
        $this->assertNotNull($baja);
        $this->assertNull($baja->fecha_baja);
        $this->assertNull($baja->fk_id_motivo_baja);
    }

    public function test_sincronizar_es_idempotente(): void
    {
        $this->crearActivosAntiguos();

        $this->artisan('normalizacion:sincronizar')->assertSuccessful();
        $primera = [DB::table('marcas')->count(), DB::table('modelos')->count(), DB::table('bajas_activos')->count()];

        $this->artisan('normalizacion:sincronizar')->assertSuccessful();
        $segunda = [DB::table('marcas')->count(), DB::table('modelos')->count(), DB::table('bajas_activos')->count()];

        $this->assertSame($primera, $segunda);
    }

    public function test_contraccion_aborta_sin_tocar_nada_si_falta_una_fk(): void
    {
        $this->crearActivosAntiguos();
        $this->artisan('normalizacion:sincronizar')->assertSuccessful();

        DB::table('activos_tecnologicos')->where('codigo_patrimonial', 'PC-1')->update(['fk_id_estado_activo' => null]);

        try {
            Artisan::call('migrate', ['--path' => 'database/contraccion', '--force' => true]);
            $this->fail('La contracción debió abortar.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Contracción abortada', $e->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('activos_tecnologicos', 'estado_operativo'));
        $this->assertTrue(Schema::hasColumn('activos_tecnologicos', 'marca'));
    }

    public function test_contraccion_elimina_el_texto_y_su_rollback_lo_restaura(): void
    {
        $this->crearActivosAntiguos();
        $this->artisan('normalizacion:sincronizar')->assertSuccessful();

        try {
            Artisan::call('migrate', ['--path' => 'database/contraccion', '--force' => true]);

            foreach (['marca', 'modelo', 'estado_operativo', 'etapa_ciclo_vida', 'fk_id_categoria'] as $columna) {
                $this->assertFalse(Schema::hasColumn('activos_tecnologicos', $columna), "{$columna} debió eliminarse");
            }
            $this->assertTrue(Schema::hasTable('estados_activo'));
            $this->assertFalse(Schema::hasTable('niveles_frecuencia'));
        } finally {
            Artisan::call('migrate:rollback', ['--path' => 'database/contraccion', '--force' => true]);
        }

        $this->assertTrue(Schema::hasColumn('activos_tecnologicos', 'marca'));
        $this->assertTrue(Schema::hasTable('estados_operativos'));
        $this->assertSame('HP', DB::table('activos_tecnologicos')->where('codigo_patrimonial', 'PC-1')->value('marca'));
        $this->assertSame('Operativo', DB::table('activos_tecnologicos')->where('codigo_patrimonial', 'PC-2')->value('estado_operativo'));
        $this->assertNull(DB::table('activos_tecnologicos')->where('codigo_patrimonial', 'PC-3')->value('marca'));
        $this->assertGreaterThan(0, (int) DB::table('activos_tecnologicos')->where('codigo_patrimonial', 'PC-1')->value('fk_id_categoria'));
    }
}
