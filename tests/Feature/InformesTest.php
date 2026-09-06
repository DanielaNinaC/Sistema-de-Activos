<?php

namespace Tests\Feature;

use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\EvaluacionRiesgo;
use App\Models\ModoFalla;
use App\Models\PesoAhp;
use App\Models\PlanTratamientoRiesgo;
use App\Models\Rol;
use App\Models\Ubicacion;
use App\Models\Usuario;
use App\Services\ExportService;
use App\Services\MotorFmeaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformesTest extends TestCase
{
    use RefreshDatabase;

    private function loginComoAdmin(): Usuario
    {
        $rol = Rol::create(['nombre_rol' => 'Administrador']); // id_rol=1, coincide con config('roles.admin')
        $admin = Usuario::create([
            'fk_id_rol' => $rol->id_rol,
            'nombre' => 'Admin',
            'apellido' => 'Informes',
            'correo' => 'admin.informes@example.com',
            'contrasena' => bcrypt('secret'),
            'estado' => 1,
        ]);

        // Los permisos por módulo (permiso_informes, etc.) tienen DEFAULT en la
        // base de datos; sin recargar, la instancia no los trae y
        // CheckModuleAccess los leería como null → 403.
        $admin->refresh();

        $this->actingAs($admin);

        return $admin;
    }

    private function crearPesosVigentes(): void
    {
        PesoAhp::create([
            'peso_severidad' => 0.648,
            'peso_frecuencia' => 0.230,
            'peso_msr' => 0.122,
            'fecha_actualizacion' => now(),
        ]);
    }

    /** 7.1 */
    public function test_vida_util_remanente_devuelve_meses_restantes_negativo_si_supera_la_vida_util(): void
    {
        $categoria = Categoria::create([
            'nombre_categoria' => 'Impresoras',
            'vida_util_meses' => 12,
            'nivel_dependencia' => 'Media',
        ]);
        $ubicacion = Ubicacion::create(['seccion' => 'A', 'aula' => 'B']);

        $activo = ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'IMP-900',
            'nombre_equipo' => 'Impresora Vieja',
            'fecha_adquisicion' => now()->subMonths(20)->format('Y-m-d'),
        ]);

        $resultado = app(MotorFmeaService::class)->calcularVidaUtilRemanente($activo);

        $this->assertSame(20, $resultado['antiguedad_meses']);
        $this->assertSame(12, $resultado['vida_util_meses']);
        $this->assertSame(-8, $resultado['meses_restantes']);
        $this->assertEquals(100.0, $resultado['porcentaje_consumido']);
    }

    /** 7.2 */
    public function test_modos_falla_frecuentes_ordena_por_veces_criticas_descendente(): void
    {
        $this->loginComoAdmin();
        $this->crearPesosVigentes();

        $categoria = Categoria::create([
            'nombre_categoria' => 'Servidores',
            'vida_util_meses' => 60,
            'nivel_dependencia' => 'Alta',
        ]);
        $ubicacion = Ubicacion::create(['seccion' => 'S', 'aula' => 'A']);

        $modoFrecuente = ModoFalla::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'nombre_modo_falla' => 'Modo Crítico Frecuente',
            'valor_severidad_base' => 9,
            'valor_msr_base' => 8,
        ]);
        $modoRaro = ModoFalla::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'nombre_modo_falla' => 'Modo Bajo Riesgo',
            'valor_severidad_base' => 1,
            'valor_msr_base' => 9,
        ]);

        $activo1 = ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'SRV-901',
            'nombre_equipo' => 'Servidor 1',
            'fecha_adquisicion' => now()->subMonths(6)->format('Y-m-d'),
        ]);
        $activo2 = ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'SRV-902',
            'nombre_equipo' => 'Servidor 2',
            'fecha_adquisicion' => now()->subMonths(6)->format('Y-m-d'),
        ]);

        // Modo frecuente: 2 evaluaciones Crítico
        EvaluacionRiesgo::create([
            'fk_id_activo' => $activo1->id_activo, 'fk_id_modo_falla' => $modoFrecuente->id_modo_falla,
            'valor_severidad' => 9, 'valor_frecuencia' => 9, 'valor_msr' => 8,
            'score_calculado' => 8.9, 'nivel_riesgo' => 'Crítico', 'vigente' => true, 'fecha_evaluacion' => now(),
        ]);
        EvaluacionRiesgo::create([
            'fk_id_activo' => $activo2->id_activo, 'fk_id_modo_falla' => $modoFrecuente->id_modo_falla,
            'valor_severidad' => 9, 'valor_frecuencia' => 9, 'valor_msr' => 8,
            'score_calculado' => 8.5, 'nivel_riesgo' => 'Crítico', 'vigente' => true, 'fecha_evaluacion' => now(),
        ]);
        // Modo raro: 1 evaluación Bajo (nunca crítico)
        EvaluacionRiesgo::create([
            'fk_id_activo' => $activo1->id_activo, 'fk_id_modo_falla' => $modoRaro->id_modo_falla,
            'valor_severidad' => 1, 'valor_frecuencia' => 2, 'valor_msr' => 9,
            'score_calculado' => 1.5, 'nivel_riesgo' => 'Bajo', 'vigente' => true, 'fecha_evaluacion' => now(),
        ]);

        $response = $this->get(route('admin.informes.modos-falla'));
        $response->assertOk();

        $porEvaluacion = $response->viewData('porEvaluacion');

        $this->assertSame('Modo Crítico Frecuente', $porEvaluacion->first()->modo_falla);
        $this->assertSame(2, (int) $porEvaluacion->first()->veces_criticas);
        $this->assertSame(0, (int) $porEvaluacion->last()->veces_criticas);
    }

    /** 7.3 */
    public function test_informe_sin_tratamiento_coincide_con_el_scope_del_modulo_tratamiento(): void
    {
        $this->loginComoAdmin();
        $this->crearPesosVigentes();

        $categoria = Categoria::create([
            'nombre_categoria' => 'Servidores C',
            'vida_util_meses' => 12,
            'nivel_dependencia' => 'Alta',
        ]);
        ModoFalla::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'nombre_modo_falla' => 'Caída del sistema',
            'valor_severidad_base' => 9,
            'valor_msr_base' => 8,
        ]);
        $ubicacion = Ubicacion::create(['seccion' => 'S', 'aula' => 'C']);
        $activo = ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'SRV-903',
            'nombre_equipo' => 'Servidor Sin Tratamiento',
            'fecha_adquisicion' => now()->subMonths(24)->format('Y-m-d'),
        ]);

        app(MotorFmeaService::class)->evaluarActivo($activo->id_activo);

        // El motor ya crea automáticamente un plan Pendiente; lo eliminamos
        // para simular una evaluación crítica realmente sin tratamiento.
        PlanTratamientoRiesgo::whereHas('evaluacion', fn ($q) => $q->where('fk_id_activo', $activo->id_activo))->delete();

        $idsEsperados = EvaluacionRiesgo::sinTratamiento()->pluck('id_evaluacion')->sort()->values();
        $this->assertNotEmpty($idsEsperados);

        $response = $this->get(route('admin.informes.sin-tratamiento'));
        $response->assertOk();

        $idsInforme = $response->viewData('evaluaciones')->pluck('id_evaluacion')->sort()->values();

        $this->assertEquals($idsEsperados, $idsInforme);
    }

    /** 7.4 */
    public function test_auditoria_consolidado_con_periodo_sin_datos_no_rompe(): void
    {
        $this->loginComoAdmin();

        $response = $this->get(route('admin.informes.auditoria', [
            'fecha_inicio' => '2000-01-01',
            'fecha_fin' => '2000-01-31',
        ]));

        $response->assertOk();

        $resumen = $response->viewData('resumen');

        $this->assertSame(0, $resumen['total_activos']);
        $this->assertSame(0, $resumen['total_incidentes']);
        $this->assertSame(0, $resumen['total_mantenimientos']);
        $this->assertSame(0.0, $resumen['promedio_vida_util_consumida']);
        $this->assertSame(0, $resumen['tratamientos_pendientes']);
        $this->assertSame(0, $resumen['tratamientos_vencidos']);
    }

    /** 7.5 */
    public function test_exportar_pdf_y_excel_generan_respuestas_descargables_sin_error(): void
    {
        $service = app(ExportService::class);

        $pdfResponse = $service->exportarPdf('informes.pdf.sin-tratamiento', ['evaluaciones' => collect()], 'prueba-informe');
        $this->assertSame(200, $pdfResponse->getStatusCode());
        $this->assertStringContainsString('application/pdf', $pdfResponse->headers->get('Content-Type'));

        $excelResponse = $service->exportarExcel(
            [['Fila 1 Col A', 'Fila 1 Col B'], ['Fila 2 Col A', 'Fila 2 Col B']],
            ['Columna A', 'Columna B'],
            'prueba-informe'
        );
        $this->assertSame(200, $excelResponse->getStatusCode());
    }
}
