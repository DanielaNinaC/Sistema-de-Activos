<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Services\ImportadorInventarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ImportarInventarioTest extends TestCase
{
    use RefreshDatabase;

    private string $archivo;

    protected function setUp(): void
    {
        parent::setUp();

        Categoria::create(['nombre_categoria' => 'Computadoras de escritorio', 'vida_util_meses' => 60, 'nivel_dependencia' => 'Media']);
        Categoria::create(['nombre_categoria' => 'Proyectores', 'vida_util_meses' => 84, 'nivel_dependencia' => 'Media']);
        $this->artisan('normalizacion:sincronizar')->assertSuccessful(); // crea el tipo de activo de cada categoría

        $this->archivo = tempnam(sys_get_temp_dir(), 'inv') . '.xlsx';
        $this->escribirLibro();
    }

    protected function tearDown(): void
    {
        @unlink($this->archivo);
        parent::tearDown();
    }

    private function escribirLibro(): void
    {
        $libro = new Spreadsheet();

        $pc = $libro->getActiveSheet()->setTitle('COMPUTADORAS');
        $pc->fromArray([
            ['INVENTARIO DE EQUIPOS DE SISTEMAS 2025'],
            [],
            ['SECCIÓN', 'AULA', 'CÓDIGO', 'MARCA', 'MODELO', 'PROCESADOR', 'RAM', 'SSD', 'S.O.', 'LECTOR', 'AÑO'],
            ['Primaria', 'A1', 'PC-001', 'HP', 'ProDesk', 'Core i5', '8GB', '256', 'Windows 10', 'Si', 2020],
            [null, 'A2', 'PC-002', 'Hp', 'ProDesk', 'Core i5', '4 GB', '', 'Windows 10', 'No', 2019],   // sección heredada
            [null, 'A3', 'PC-001', 'Dell', 'X', '', '', '', '', '', 2021],                              // código repetido
            [null, '', 'PC-004', 'Dell', 'X', '', '', '', '', '', 2021],                                // sin aula
            [null, 'A5', 'PC-005', 'Dell', 'X', '', 'mucho', '', '', '', 2021],                         // RAM inválida
            [null, 'A6', '', 'Dell', 'X', '', '', '', '', '', 2021],                                    // sin código
        ], null, 'A1');

        $proy = $libro->createSheet()->setTitle('PROYECTORES');
        $proy->fromArray([
            ['Nº', 'SECCIÓN', 'AULA', 'MARCA', 'MODELO', 'CÓDIGO', 'AÑO', 'CABLE'],
            [1, 'Maternal', 'P2', 'Epson', 'S17', '8852', 2015, 'HDMI'],
        ], null, 'A1');

        $libro->createSheet()->setTitle('NOTAS')->fromArray([['esto no es una hoja de inventario']], null, 'A1');

        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($libro))->save($this->archivo);
    }

    public function test_importa_por_encabezados_y_registra_las_filas_rechazadas(): void
    {
        $informe = app(ImportadorInventarioService::class)->importar($this->archivo);

        $this->assertSame(2, $informe['hojas']['COMPUTADORAS']['creadas']);
        $this->assertSame(4, $informe['hojas']['COMPUTADORAS']['rechazadas']);
        $this->assertSame(1, $informe['hojas']['PROYECTORES']['creadas']);

        $motivos = collect($informe['rechazadas'])->pluck('motivo')->implode(' | ');
        $this->assertStringContainsString('repetido', $motivos);
        $this->assertStringContainsString('sin sección o aula', $motivos);
        $this->assertStringContainsString('no es una capacidad válida', $motivos);
        $this->assertStringContainsString('sin código patrimonial', $motivos);

        $this->assertNotEmpty(collect($informe['advertencias'])->filter(fn ($a) => str_contains($a, 'NOTAS')));

        // La sección vacía de PC-002 se heredó de la fila anterior.
        $ubicacion = DB::table('activos_tecnologicos as a')->join('ubicaciones as u', 'u.id_ubicacion', '=', 'a.fk_id_ubicacion')
            ->where('a.codigo_patrimonial', 'PC-002')->first(['u.seccion', 'u.aula']);
        $this->assertSame('Primaria', $ubicacion->seccion);
        $this->assertSame('A2', $ubicacion->aula);

        // "8GB" → 8 y "Si" → verdadero.
        $ficha = DB::table('detalles_computadora as d')->join('activos_tecnologicos as a', 'a.id_activo', '=', 'd.fk_id_activo')
            ->where('a.codigo_patrimonial', 'PC-001')->first();
        $this->assertSame(8, (int) $ficha->ram_gb);
        $this->assertTrue((bool) $ficha->lector);
        $this->assertSame(256, (int) $ficha->disco_solido_gb);
    }

    public function test_simular_no_escribe_nada(): void
    {
        $informe = app(ImportadorInventarioService::class)->importar($this->archivo, true);

        $this->assertSame(2, $informe['hojas']['COMPUTADORAS']['creadas']);
        $this->assertSame(0, DB::table('activos_tecnologicos')->count());
    }

    public function test_es_idempotente_y_completa_las_fk_normalizadas(): void
    {
        $this->artisan('inventario:importar', ['archivo' => $this->archivo])->assertSuccessful();
        $this->artisan('inventario:importar', ['archivo' => $this->archivo])->assertSuccessful();

        $this->assertSame(3, DB::table('activos_tecnologicos')->count());
        $this->assertSame(0, DB::table('activos_tecnologicos')->whereNull('fk_id_tipo_activo')->count());
        $this->assertSame(0, DB::table('activos_tecnologicos')->whereNull('fk_id_modelo')->count());
        // "HP" y "Hp" quedan como una sola marca.
        $this->assertSame(1, DB::table('marcas')->whereRaw("LOWER(nombre) = 'hp'")->count());
    }
}
