<?php

namespace Tests\Feature;

use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\EvaluacionRiesgo;
use App\Models\Incidente;
use App\Models\Mantenimiento;
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

    /**
     * generarIntroduccionAuditoria()/generarConclusionAuditoria() son
     * privados a propósito (no son una API pública del controlador): se
     * invocan por reflexión para poder afirmar el texto exacto que producen,
     * ya que desde la Fase de corrección solo se usan en la ruta PDF, cuya
     * respuesta es un binario que no expone viewData().
     */
    private function invocarMetodoPrivado(object $objeto, string $metodo, array $argumentos)
    {
        $reflexion = new \ReflectionMethod($objeto, $metodo);
        $reflexion->setAccessible(true);

        return $reflexion->invokeArgs($objeto, $argumentos);
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

    /**
     * PLN (NLG por plantilla) — sin activos registrados, la introducción y la
     * conclusión deben decirlo tal cual, sin inventar cifras ni asumir causas.
     * Solo se generan para el PDF (ver auditoriaConsolidadoPdf()), así que se
     * invocan por reflexión en vez de leerlas de la vista en pantalla.
     */
    public function test_introduccion_y_conclusion_del_informe_general_sin_datos(): void
    {
        $this->loginComoAdmin();
        $controlador = app(\App\Http\Controllers\Admin\InformesController::class);

        $resumen = $this->invocarMetodoPrivado($controlador, 'calcularResumenAuditoria', ['2000-01-01', '2000-01-31']);
        $introduccion = $this->invocarMetodoPrivado($controlador, 'generarIntroduccionAuditoria', [$resumen]);
        $conclusion = $this->invocarMetodoPrivado($controlador, 'generarConclusionAuditoria', [$resumen]);

        $this->assertSame(0, $resumen['total_activos']);
        $this->assertStringContainsString('un total de 0 activos tecnológicos', $introduccion);
        $this->assertStringContainsString('01/01/2000', $introduccion);
        $this->assertStringContainsString('31/01/2000', $introduccion);

        $this->assertSame('No hay activos tecnológicos registrados en el sistema a la fecha de generación de este informe.', $conclusion);

        // Y no deben aparecer en la vista de pantalla (solo van en el PDF).
        $response = $this->get(route('admin.informes.auditoria', ['fecha_inicio' => '2000-01-01', 'fecha_fin' => '2000-01-31']));
        $response->assertOk();
        $response->assertDontSee('Introducción');
        $response->assertDontSee('Conclusión');
    }

    /**
     * PLN (NLG por plantilla) — con datos reales, la conclusión debe reflejar
     * exactamente los mismos números que calcularResumenAuditoria(), sin
     * modificarlos: activos sin evaluar, distribución de riesgo, tratamientos
     * vencidos, incidentes y mantenimientos del período.
     */
    public function test_conclusion_del_informe_general_refleja_los_indicadores_reales(): void
    {
        $admin = $this->loginComoAdmin();
        $this->crearPesosVigentes();

        $categoria = Categoria::create([
            'nombre_categoria' => 'Servidores PLN',
            'vida_util_meses' => 60,
            'nivel_dependencia' => 'Alta',
        ]);
        $modo = ModoFalla::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'nombre_modo_falla' => 'Falla de prueba PLN',
            'valor_severidad_base' => 9,
            'valor_msr_base' => 8,
        ]);
        $ubicacion = Ubicacion::create(['seccion' => 'PLN', 'aula' => 'A']);

        // Activo A: evaluado y en nivel Crítico.
        $activoEvaluado = ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'PLN-001',
            'nombre_equipo' => 'Servidor PLN Evaluado',
            'fecha_adquisicion' => now()->subMonths(24)->format('Y-m-d'),
        ]);
        $evaluacion = EvaluacionRiesgo::create([
            'fk_id_activo' => $activoEvaluado->id_activo,
            'fk_id_modo_falla' => $modo->id_modo_falla,
            'valor_severidad' => 9, 'valor_frecuencia' => 6, 'valor_msr' => 8,
            'score_calculado' => 8.39, 'nivel_riesgo' => 'Crítico', 'vigente' => true, 'fecha_evaluacion' => now(),
        ]);
        PlanTratamientoRiesgo::create([
            'fk_id_evaluacion' => $evaluacion->id_evaluacion,
            'estrategia' => 'Mitigar',
            'accion_preventiva' => 'Acción de prueba.',
            'estado_tratamiento' => 'Pendiente',
            'origen' => 'manual',
            'fecha_compromiso' => now()->subDays(3)->toDateString(), // vencido
        ]);

        // Activo B: sin ninguna evaluación registrada.
        $activoSinEvaluar = ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'PLN-002',
            'nombre_equipo' => 'Servidor PLN Sin Evaluar',
            'fecha_adquisicion' => now()->subMonths(6)->format('Y-m-d'),
        ]);

        $fechaInicio = now()->startOfMonth()->toDateString();
        $fechaFin = now()->toDateString();

        Incidente::create([
            'fk_id_activo' => $activoEvaluado->id_activo,
            'fk_id_usuario_reporta' => $admin->id_usuario,
            'fecha_incidente' => now(),
            'descripcion_falla' => 'Incidente de prueba PLN.',
            'estado_incidente' => 'Reportado',
        ]);

        Mantenimiento::create([
            'fk_id_activo' => $activoEvaluado->id_activo,
            'fk_id_usuario_asignado' => $admin->id_usuario,
            'fk_id_origen' => 1,
            'tipo_mantenimiento' => 'Preventivo',
            'fecha_programada' => now()->toDateString(),
            'fecha_ejecucion' => now()->toDateString(),
            'descripcion_trabajo' => 'Mantenimiento de prueba PLN.',
            'estado_mantenimiento' => 'Finalizado',
        ]);

        $response = $this->get(route('admin.informes.auditoria', [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ]));
        $response->assertOk();
        // La Introducción/Conclusión no se muestran en pantalla, solo en el PDF.
        $response->assertDontSee('Conclusión');

        $resumen = $response->viewData('resumen');
        $controlador = app(\App\Http\Controllers\Admin\InformesController::class);
        $conclusion = $this->invocarMetodoPrivado($controlador, 'generarConclusionAuditoria', [$resumen]);

        // La conclusión no debe fabricar números: debe citar los mismos que $resumen.
        $this->assertStringContainsString(
            "Del total de {$resumen['total_activos']} activos tecnológicos registrados, 1 (",
            $conclusion
        );
        $this->assertStringContainsString('no cuentan con una evaluación de riesgo vigente.', $conclusion);
        $this->assertStringContainsString(
            "{$resumen['distribucion_riesgo']['Crítico']} se encuentran en nivel Crítico",
            $conclusion
        );
        $this->assertStringContainsString('del cual 1 presenta la fecha de compromiso vencida', $conclusion);
        $this->assertStringContainsString('se registró 1 incidente.', $conclusion);
        $this->assertStringContainsString('Se ejecutó 1 mantenimiento en el período analizado (1 Preventivo).', $conclusion);
        $this->assertStringContainsString(
            'vida útil consumida del inventario es de ' . number_format($resumen['promedio_vida_util_consumida'], 1) . '%.',
            $conclusion
        );

        // Nada de predicciones ni recomendaciones: solo el cierre descriptivo fijo.
        $this->assertStringContainsString(
            'Los resultados presentados corresponden a los datos registrados en el sistema para el período indicado.',
            $conclusion
        );
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
