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
use App\Services\MotorFmeaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlanTratamientoTest extends TestCase
{
    use RefreshDatabase;

    private function loginComoAdmin(): Usuario
    {
        $rol = Rol::create(['nombre_rol' => 'Administrador']); // id_rol=1, coincide con config('roles.admin')
        $admin = Usuario::create([
            'fk_id_rol' => $rol->id_rol,
            'nombre' => 'Admin',
            'apellido' => 'Test',
            'correo' => 'admin.test@example.com',
            'contrasena' => bcrypt('secret'),
            'estado' => 1,
        ]);

        // Los permisos por módulo (permiso_riesgos, permiso_mantenimiento…)
        // tienen DEFAULT en la base de datos; sin recargar, la instancia no los
        // trae y CheckModuleAccess los leería como null → 403.
        $admin->refresh();

        $this->actingAs($admin);

        return $admin;
    }

    /**
     * Activo cuya evaluación cae en 'Crítico' sin depender de incidentes
     * (mismo escenario que en MotorFmeaServiceTest::crearEscenarioCritico).
     *
     * @return array{0: ActivoTecnologico, 1: ModoFalla}
     */
    private function crearEscenarioCritico(): array
    {
        PesoAhp::create([
            'peso_severidad' => 0.648,
            'peso_frecuencia' => 0.230,
            'peso_msr' => 0.122,
            'fecha_actualizacion' => now(),
        ]);

        $categoria = Categoria::create([
            'nombre_categoria' => 'Servidores',
            'vida_util_meses' => 12,
            'nivel_dependencia' => 'Alta',
        ]);

        $modo = ModoFalla::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'nombre_modo_falla' => 'Caída del sistema operativo',
            'valor_severidad_base' => 9,
            'valor_msr_base' => 8,
        ]);

        $ubicacion = Ubicacion::create([
            'seccion' => 'Sistemas',
            'aula' => 'Sala de Servidores',
        ]);

        $activo = ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'SRV-200',
            'nombre_equipo' => 'Servidor Crítico',
            'fecha_adquisicion' => now()->subMonths(24)->format('Y-m-d'),
        ]);

        return [$activo, $modo];
    }

    public function test_completar_tratamiento_manualmente_no_dispara_reevaluacion_sin_mantenimiento(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();

        app(MotorFmeaService::class)->evaluarActivo($activo->id_activo);

        $this->assertSame(1, EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)->count());

        $tratamiento = PlanTratamientoRiesgo::firstOrFail();
        $this->assertSame('Pendiente', $tratamiento->estado_tratamiento);

        $response = $this->put(route('admin.riesgos.tratamiento.update', $tratamiento->id_tratamiento), [
            'estado_tratamiento' => 'Completado',
        ]);

        $response->assertRedirect(route('admin.riesgos.tratamiento'));

        $this->assertSame('Completado', $tratamiento->fresh()->estado_tratamiento);

        // El disparador de re-evaluación ya no vive en el Tratamiento: cambiar
        // su estado a mano (sin que el Mantenimiento vinculado haya
        // finalizado) no debe generar una nueva evaluación — no hubo ningún
        // hecho físico sobre el activo.
        $this->assertSame(1, EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
            ->where('fk_id_modo_falla', $modo->id_modo_falla)
            ->count());
    }

    public function test_finalizar_mantenimiento_completa_tratamiento_mitigar_y_dispara_reevaluacion(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();

        app(MotorFmeaService::class)->evaluarActivo($activo->id_activo);

        $tratamiento = PlanTratamientoRiesgo::firstOrFail();
        $this->assertSame('Mitigar', $tratamiento->estrategia);
        $this->assertSame('Pendiente', $tratamiento->estado_tratamiento);

        // La estrategia "Mitigar" ya programó automáticamente un Mantenimiento.
        $mantenimiento = Mantenimiento::where('id_evaluacion_origen', $tratamiento->fk_id_evaluacion)->firstOrFail();
        $this->assertSame('Programado', $mantenimiento->estado_mantenimiento);

        $response = $this->put(route('admin.mantenimiento.update', $mantenimiento->id_mantenimiento), [
            'estado_mantenimiento' => 'Finalizado',
        ]);

        $response->assertRedirect();
        $this->assertNotNull($mantenimiento->fresh()->fecha_ejecucion);

        // Disparador inverso: el Mantenimiento finalizado cierra el
        // Tratamiento que lo originó y dispara una nueva evaluación.
        $this->assertSame('Completado', $tratamiento->fresh()->estado_tratamiento);

        $this->assertSame(2, EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
            ->where('fk_id_modo_falla', $modo->id_modo_falla)
            ->count());

        $this->assertSame(1, EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
            ->where('fk_id_modo_falla', $modo->id_modo_falla)
            ->where('vigente', true)
            ->count());
    }

    public function test_tratamiento_manual_transferir_no_programa_mantenimiento(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();

        $evaluacion = EvaluacionRiesgo::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_modo_falla' => $modo->id_modo_falla,
            'valor_severidad' => 9, 'valor_frecuencia' => 6, 'valor_msr' => 8,
            'score_calculado' => 8.39, 'nivel_riesgo' => 'Crítico', 'vigente' => true, 'fecha_evaluacion' => now(),
        ]);

        $response = $this->post(route('admin.riesgos.tratamiento.store'), [
            'fk_id_evaluacion' => $evaluacion->id_evaluacion,
            'estrategia' => 'Transferir',
            'accion_preventiva' => 'Contratar seguro/soporte externo para el activo.',
            'estado_tratamiento' => 'Pendiente',
        ]);

        $response->assertRedirect(route('admin.riesgos.tratamiento'));

        $this->assertSame(0, Mantenimiento::where('id_evaluacion_origen', $evaluacion->id_evaluacion)->count());

        // El formulario manual debe congelar la MISMA sugerencia que
        // generaría el motor automático para este nivel de riesgo, calculada
        // en el servidor -aunque el usuario haya aplicado una estrategia
        // distinta a la sugerida (aquí: Transferir en vez de Mitigar)-.
        $tratamiento = PlanTratamientoRiesgo::where('fk_id_evaluacion', $evaluacion->id_evaluacion)->firstOrFail();
        $this->assertSame('Mitigar', $tratamiento->estrategia_sugerida);
        $this->assertNotEmpty($tratamiento->accion_sugerida);
        $this->assertSame('Transferir', $tratamiento->estrategia);
    }

    public function test_tratamiento_manual_mitigar_programa_mantenimiento_correctivo_si_hay_incidente_abierto(): void
    {
        $admin = $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();

        $evaluacion = EvaluacionRiesgo::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_modo_falla' => $modo->id_modo_falla,
            'valor_severidad' => 9, 'valor_frecuencia' => 6, 'valor_msr' => 8,
            'score_calculado' => 8.39, 'nivel_riesgo' => 'Crítico', 'vigente' => true, 'fecha_evaluacion' => now(),
        ]);

        Incidente::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_reporta' => $admin->id_usuario,
            'fecha_incidente' => now(),
            'descripcion_falla' => 'El servidor no responde.',
            'estado_incidente' => 'Reportado',
        ]);

        $response = $this->post(route('admin.riesgos.tratamiento.store'), [
            'fk_id_evaluacion' => $evaluacion->id_evaluacion,
            'estrategia' => 'Mitigar',
            'accion_preventiva' => 'Reiniciar y diagnosticar el servidor.',
            'estado_tratamiento' => 'Pendiente',
        ]);

        $response->assertRedirect(route('admin.riesgos.tratamiento'));

        $mantenimiento = Mantenimiento::where('id_evaluacion_origen', $evaluacion->id_evaluacion)->firstOrFail();
        $this->assertSame('Correctivo', $mantenimiento->tipo_mantenimiento);
        $this->assertSame('Programado', $mantenimiento->estado_mantenimiento);
        $this->assertSame($admin->id_usuario, $mantenimiento->fk_id_usuario_asignado);
    }

    public function test_evaluaciones_sin_tratamiento_solo_incluye_alto_critico_sin_tratamiento_asociado(): void
    {
        $this->loginComoAdmin();

        // Activo A: Crítico, sin tratamiento manual -> el motor ya le crea uno automático,
        // así que NO debe aparecer en el reporte (ya tiene un plan Pendiente).
        [$activoConTratamiento] = $this->crearEscenarioCritico();
        app(MotorFmeaService::class)->evaluarActivo($activoConTratamiento->id_activo);

        // Activo B: también Crítico, pero le borramos el plan automático para
        // simular una evaluación crítica realmente sin tratamiento.
        $categoria2 = Categoria::create([
            'nombre_categoria' => 'Servidores B',
            'vida_util_meses' => 12,
            'nivel_dependencia' => 'Alta',
        ]);
        $modo2 = ModoFalla::create([
            'fk_id_categoria' => $categoria2->id_categoria,
            'nombre_modo_falla' => 'Caída del sistema operativo B',
            'valor_severidad_base' => 9,
            'valor_msr_base' => 8,
        ]);
        $ubicacion2 = Ubicacion::create(['seccion' => 'Sistemas', 'aula' => 'Sala B']);
        $activoSinTratamiento = ActivoTecnologico::create([
            'fk_id_categoria' => $categoria2->id_categoria,
            'fk_id_ubicacion' => $ubicacion2->id_ubicacion,
            'codigo_patrimonial' => 'SRV-300',
            'nombre_equipo' => 'Servidor B',
            'fecha_adquisicion' => now()->subMonths(24)->format('Y-m-d'),
        ]);
        app(MotorFmeaService::class)->evaluarActivo($activoSinTratamiento->id_activo);
        PlanTratamientoRiesgo::whereHas('evaluacion', fn ($q) => $q->where('fk_id_activo', $activoSinTratamiento->id_activo))->delete();

        // Activo C: nivel Medio (no debe aparecer nunca, aunque no tenga tratamiento)
        $categoria3 = Categoria::create([
            'nombre_categoria' => 'Proyectores',
            'vida_util_meses' => 60,
            'nivel_dependencia' => 'Media',
        ]);
        $modo3 = ModoFalla::create([
            'fk_id_categoria' => $categoria3->id_categoria,
            'nombre_modo_falla' => 'Falla de lámpara',
            'valor_severidad_base' => 5,
            'valor_msr_base' => 6,
        ]);
        $ubicacion3 = Ubicacion::create(['seccion' => 'Aula', 'aula' => 'C-1']);
        $activoMedio = ActivoTecnologico::create([
            'fk_id_categoria' => $categoria3->id_categoria,
            'fk_id_ubicacion' => $ubicacion3->id_ubicacion,
            'codigo_patrimonial' => 'PRY-300',
            'nombre_equipo' => 'Proyector C',
            'fecha_adquisicion' => now()->subMonths(6)->format('Y-m-d'),
        ]);
        app(MotorFmeaService::class)->evaluarActivo($activoMedio->id_activo);

        $response = $this->get(route('admin.riesgos.tratamiento'));

        $response->assertOk();
        $evaluacionesSinTratamiento = $response->viewData('evaluacionesSinTratamiento');

        $this->assertCount(1, $evaluacionesSinTratamiento);
        $this->assertSame($activoSinTratamiento->id_activo, $evaluacionesSinTratamiento->first()->fk_id_activo);
    }

    public function test_estrategia_sugerida_y_accion_sugerida_son_inmutables_tras_creacion(): void
    {
        $this->loginComoAdmin();
        [$activo] = $this->crearEscenarioCritico();

        app(MotorFmeaService::class)->evaluarActivo($activo->id_activo);

        $tratamiento = PlanTratamientoRiesgo::firstOrFail();
        $this->assertSame('Mitigar', $tratamiento->estrategia_sugerida);
        $this->assertNotEmpty($tratamiento->accion_sugerida);
        $sugeridaOriginal = $tratamiento->estrategia_sugerida;
        $accionSugeridaOriginal = $tratamiento->accion_sugerida;

        // El endpoint de actualización solo acepta estado_tratamiento (y
        // observacion_cierre cuando aplica): cualquier intento de inyectar
        // estrategia_sugerida/accion_sugerida en el payload debe ser ignorado.
        $response = $this->put(route('admin.riesgos.tratamiento.update', $tratamiento->id_tratamiento), [
            'estado_tratamiento' => 'En Proceso',
            'estrategia_sugerida' => 'Aceptar',
            'accion_sugerida' => 'Texto adulterado',
        ]);

        $response->assertRedirect(route('admin.riesgos.tratamiento'));

        $tratamiento->refresh();
        $this->assertSame($sugeridaOriginal, $tratamiento->estrategia_sugerida);
        $this->assertSame($accionSugeridaOriginal, $tratamiento->accion_sugerida);
        $this->assertSame('En Proceso', $tratamiento->estado_tratamiento);
    }

    public function test_completar_tratamiento_sin_mantenimiento_exige_observacion_cierre(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();

        $evaluacion = EvaluacionRiesgo::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_modo_falla' => $modo->id_modo_falla,
            'valor_severidad' => 9, 'valor_frecuencia' => 6, 'valor_msr' => 8,
            'score_calculado' => 8.39, 'nivel_riesgo' => 'Crítico', 'vigente' => true, 'fecha_evaluacion' => now(),
        ]);

        $this->post(route('admin.riesgos.tratamiento.store'), [
            'fk_id_evaluacion' => $evaluacion->id_evaluacion,
            'estrategia' => 'Transferir',
            'accion_preventiva' => 'Contratar seguro/soporte externo para el activo.',
            'estado_tratamiento' => 'Pendiente',
        ]);
        $tratamiento = PlanTratamientoRiesgo::where('fk_id_evaluacion', $evaluacion->id_evaluacion)->firstOrFail();

        // Sin observacion_cierre: rechazado, el estado no cambia.
        $response = $this->put(route('admin.riesgos.tratamiento.update', $tratamiento->id_tratamiento), [
            'estado_tratamiento' => 'Completado',
        ]);
        $response->assertSessionHasErrors('observacion_cierre');
        $this->assertSame('Pendiente', $tratamiento->fresh()->estado_tratamiento);

        // Con observacion_cierre: se acepta y queda registrada.
        $response = $this->put(route('admin.riesgos.tratamiento.update', $tratamiento->id_tratamiento), [
            'estado_tratamiento' => 'Completado',
            'observacion_cierre' => 'Se contrató póliza de seguro externa para este activo.',
        ]);
        $response->assertRedirect(route('admin.riesgos.tratamiento'));
        $tratamiento->refresh();
        $this->assertSame('Completado', $tratamiento->estado_tratamiento);
        $this->assertSame('Se contrató póliza de seguro externa para este activo.', $tratamiento->observacion_cierre);
    }

    public function test_historial_tratamiento_resuelve_resultado_real_segun_estrategia(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();

        // Caso 1: Mitigar con Mantenimiento Finalizado -> resultado = descripcion_trabajo.
        $eval1 = EvaluacionRiesgo::create([
            'fk_id_activo' => $activo->id_activo, 'fk_id_modo_falla' => $modo->id_modo_falla,
            'valor_severidad' => 9, 'valor_frecuencia' => 6, 'valor_msr' => 8,
            'score_calculado' => 8.39, 'nivel_riesgo' => 'Crítico', 'vigente' => false,
            'fecha_evaluacion' => now()->subDays(30),
        ]);
        $tratamiento1 = PlanTratamientoRiesgo::create([
            'fk_id_evaluacion' => $eval1->id_evaluacion, 'estrategia' => 'Mitigar', 'estrategia_sugerida' => 'Mitigar',
            'accion_preventiva' => 'Diagnosticar y reparar la fuente de poder.', 'accion_sugerida' => 'Diagnosticar y reparar la fuente de poder.',
            'estado_tratamiento' => 'Completado', 'origen' => 'automatico',
        ]);
        Mantenimiento::create([
            'fk_id_activo' => $activo->id_activo, 'fk_id_usuario_asignado' => Usuario::first()->id_usuario,
            'fk_id_origen' => 2, 'id_evaluacion_origen' => $eval1->id_evaluacion,
            'tipo_mantenimiento' => 'Correctivo', 'fecha_programada' => now()->subDays(25),
            'descripcion_trabajo' => 'Se reemplazó la fuente de poder dañada.',
            'estado_mantenimiento' => 'Finalizado', 'fecha_ejecucion' => now()->subDays(20),
        ]);

        // Caso 2: Mitigar con Mantenimiento aún Programado -> "En ejecución".
        $eval2 = EvaluacionRiesgo::create([
            'fk_id_activo' => $activo->id_activo, 'fk_id_modo_falla' => $modo->id_modo_falla,
            'valor_severidad' => 9, 'valor_frecuencia' => 6, 'valor_msr' => 8,
            'score_calculado' => 8.39, 'nivel_riesgo' => 'Crítico', 'vigente' => true,
            'fecha_evaluacion' => now()->subDays(10),
        ]);
        PlanTratamientoRiesgo::create([
            'fk_id_evaluacion' => $eval2->id_evaluacion, 'estrategia' => 'Mitigar', 'estrategia_sugerida' => 'Mitigar',
            'accion_preventiva' => 'Reemplazar unidad nuevamente.', 'accion_sugerida' => 'Reemplazar unidad nuevamente.',
            'estado_tratamiento' => 'Pendiente', 'origen' => 'automatico',
        ]);
        Mantenimiento::create([
            'fk_id_activo' => $activo->id_activo, 'fk_id_usuario_asignado' => Usuario::first()->id_usuario,
            'fk_id_origen' => 2, 'id_evaluacion_origen' => $eval2->id_evaluacion,
            'tipo_mantenimiento' => 'Correctivo', 'fecha_programada' => now()->addDays(5),
            'descripcion_trabajo' => 'Reemplazar unidad nuevamente.', 'estado_mantenimiento' => 'Programado',
        ]);

        // Caso 3: Transferir, Completado con observación -> resultado = observacion_cierre.
        $eval3 = EvaluacionRiesgo::create([
            'fk_id_activo' => $activo->id_activo, 'fk_id_modo_falla' => $modo->id_modo_falla,
            'valor_severidad' => 9, 'valor_frecuencia' => 6, 'valor_msr' => 8,
            'score_calculado' => 8.39, 'nivel_riesgo' => 'Crítico', 'vigente' => false,
            'fecha_evaluacion' => now()->subDays(60),
        ]);
        PlanTratamientoRiesgo::create([
            'fk_id_evaluacion' => $eval3->id_evaluacion, 'estrategia' => 'Transferir',
            'accion_preventiva' => 'Contratar seguro externo.',
            'estado_tratamiento' => 'Completado', 'origen' => 'manual',
            'observacion_cierre' => 'Póliza contratada con la aseguradora XYZ.',
        ]);

        // Caso 4: Aceptar, todavía Pendiente (sin observación aún) -> "En ejecución".
        $eval4 = EvaluacionRiesgo::create([
            'fk_id_activo' => $activo->id_activo, 'fk_id_modo_falla' => $modo->id_modo_falla,
            'valor_severidad' => 9, 'valor_frecuencia' => 6, 'valor_msr' => 8,
            'score_calculado' => 8.39, 'nivel_riesgo' => 'Crítico', 'vigente' => false,
            'fecha_evaluacion' => now()->subDays(90),
        ]);
        PlanTratamientoRiesgo::create([
            'fk_id_evaluacion' => $eval4->id_evaluacion, 'estrategia' => 'Aceptar',
            'accion_preventiva' => 'Aceptar el riesgo, sin acción física.',
            'estado_tratamiento' => 'Pendiente', 'origen' => 'manual',
        ]);

        $response = $this->get(route('admin.informes.historial-tratamiento', ['fk_id_activo' => $activo->id_activo]));
        $response->assertOk();

        $filaCaso1 = collect($response->viewData('filas'))->first(fn ($f) => $f['resultado_real'] === 'Se reemplazó la fuente de poder dañada.');
        $this->assertNotNull($filaCaso1);

        $filaCaso2 = collect($response->viewData('filas'))->where('estrategia_aplicada', 'Mitigar')->firstWhere('resultado_real', 'En ejecución');
        $this->assertNotNull($filaCaso2);

        $filaCaso3 = collect($response->viewData('filas'))->firstWhere('resultado_real', 'Póliza contratada con la aseguradora XYZ.');
        $this->assertNotNull($filaCaso3);
        $this->assertSame('Transferir', $filaCaso3['estrategia_aplicada']);

        $filaCaso4 = collect($response->viewData('filas'))->where('estrategia_aplicada', 'Aceptar')->firstWhere('resultado_real', 'En ejecución');
        $this->assertNotNull($filaCaso4);

        $this->assertCount(4, $response->viewData('filas'));
    }

    public function test_tratamiento_index_carga_el_borrador_existente_para_revisar_sin_duplicar(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();

        app(MotorFmeaService::class)->evaluarActivo($activo->id_activo);
        $tratamiento = PlanTratamientoRiesgo::firstOrFail();

        // Abrir el formulario apuntando a la misma evaluación (como haría el
        // botón "Revisar sugerencia") debe traer los valores ya guardados.
        $response = $this->get(route('admin.riesgos.tratamiento', ['crear' => $tratamiento->fk_id_evaluacion]));
        $response->assertOk();
        $tratamientoParaRevisar = $response->viewData('tratamientoParaRevisar');
        $this->assertNotNull($tratamientoParaRevisar);
        $this->assertSame($tratamiento->id_tratamiento, $tratamientoParaRevisar->id_tratamiento);
        $this->assertSame('Mitigar', $tratamientoParaRevisar->estrategia_sugerida);
    }

    public function test_confirmar_sugerencia_actualiza_el_mismo_registro_sin_duplicar(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();

        app(MotorFmeaService::class)->evaluarActivo($activo->id_activo);
        $tratamiento = PlanTratamientoRiesgo::firstOrFail();
        $this->assertSame(1, PlanTratamientoRiesgo::count());

        // El usuario revisa el borrador y lo confirma tal cual (misma
        // fk_id_evaluacion que ya tenía un tratamiento): no debe crear un
        // segundo registro para la misma evaluación.
        $response = $this->post(route('admin.riesgos.tratamiento.store'), [
            'fk_id_evaluacion' => $tratamiento->fk_id_evaluacion,
            'estrategia' => 'Mitigar',
            'accion_preventiva' => 'Texto confirmado por el usuario tras revisar la sugerencia.',
            'estado_tratamiento' => 'Pendiente',
        ]);

        $response->assertRedirect(route('admin.riesgos.tratamiento'));
        $this->assertSame(1, PlanTratamientoRiesgo::count());

        $tratamiento->refresh();
        $this->assertSame('Texto confirmado por el usuario tras revisar la sugerencia.', $tratamiento->accion_preventiva);
        // La sugerencia original congelada no cambia con la confirmación.
        $this->assertSame('Mitigar', $tratamiento->estrategia_sugerida);
    }

    public function test_confirmar_sugerencia_sin_cambiar_nada_igual_marca_el_registro_como_revisado(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();

        app(MotorFmeaService::class)->evaluarActivo($activo->id_activo);
        $tratamiento = PlanTratamientoRiesgo::firstOrFail();
        $this->assertTrue($tratamiento->created_at->eq($tratamiento->updated_at));

        // Simula que la revisión ocurre minutos después (en la vida real
        // nunca coincide al segundo con la creación, a diferencia de un test
        // que corre todo de inmediato). Se actualiza por consulta directa
        // para no disparar el propio updateTimestamps() de Eloquent.
        DB::table('plan_tratamiento_riesgo')->where('id_tratamiento', $tratamiento->id_tratamiento)->update([
            'created_at' => now()->subMinutes(5), 'updated_at' => now()->subMinutes(5),
        ]);

        // El usuario acepta la sugerencia tal cual viene, sin editar ningún
        // campo: Eloquent no vería atributos "dirty" por sí solo, así que
        // esto depende de que el controlador fuerce el touch().
        $response = $this->post(route('admin.riesgos.tratamiento.store'), [
            'fk_id_evaluacion' => $tratamiento->fk_id_evaluacion,
            'estrategia' => $tratamiento->estrategia,
            'accion_preventiva' => $tratamiento->accion_preventiva,
            'estado_tratamiento' => $tratamiento->estado_tratamiento,
        ]);
        $response->assertRedirect(route('admin.riesgos.tratamiento'));

        $tratamiento->refresh();
        $this->assertTrue($tratamiento->updated_at->gt($tratamiento->created_at));
    }

    public function test_cambiar_estrategia_de_mitigar_al_confirmar_cancela_el_mantenimiento_programado(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();

        app(MotorFmeaService::class)->evaluarActivo($activo->id_activo);
        $tratamiento = PlanTratamientoRiesgo::firstOrFail();
        $mantenimiento = Mantenimiento::where('id_evaluacion_origen', $tratamiento->fk_id_evaluacion)->firstOrFail();
        $this->assertSame('Programado', $mantenimiento->estado_mantenimiento);

        // Al revisar el borrador, el usuario decide que en realidad el
        // riesgo se transfiere (no se mitiga físicamente el activo).
        $response = $this->post(route('admin.riesgos.tratamiento.store'), [
            'fk_id_evaluacion' => $tratamiento->fk_id_evaluacion,
            'estrategia' => 'Transferir',
            'accion_preventiva' => 'Se transfiere el riesgo mediante póliza de seguro.',
            'estado_tratamiento' => 'Completado',
            'observacion_cierre' => 'Póliza contratada.',
        ]);

        $response->assertRedirect(route('admin.riesgos.tratamiento'));
        $this->assertSame(1, PlanTratamientoRiesgo::count());
        $this->assertSame('Cancelado', $mantenimiento->fresh()->estado_mantenimiento);
    }

    public function test_intento_de_alterar_sugerida_al_confirmar_es_ignorado(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();

        app(MotorFmeaService::class)->evaluarActivo($activo->id_activo);
        $tratamiento = PlanTratamientoRiesgo::firstOrFail();
        $sugeridaOriginal = $tratamiento->estrategia_sugerida;
        $accionSugeridaOriginal = $tratamiento->accion_sugerida;

        // Simula un payload manipulado (ej. vía devtools) que intenta viajar
        // junto al de "Restablecer sugerencia"/confirmación normal.
        $response = $this->post(route('admin.riesgos.tratamiento.store'), [
            'fk_id_evaluacion' => $tratamiento->fk_id_evaluacion,
            'estrategia' => 'Mitigar',
            'estrategia_sugerida' => 'Aceptar',
            'accion_preventiva' => 'Confirmado por el usuario.',
            'accion_sugerida' => 'Texto adulterado',
            'estado_tratamiento' => 'Pendiente',
        ]);

        $response->assertRedirect(route('admin.riesgos.tratamiento'));
        $tratamiento->refresh();
        $this->assertSame($sugeridaOriginal, $tratamiento->estrategia_sugerida);
        $this->assertSame($accionSugeridaOriginal, $tratamiento->accion_sugerida);
    }

    public function test_observacion_cierre_no_se_exige_para_mitigar_completado(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();

        $evaluacion = EvaluacionRiesgo::create([
            'fk_id_activo' => $activo->id_activo, 'fk_id_modo_falla' => $modo->id_modo_falla,
            'valor_severidad' => 9, 'valor_frecuencia' => 6, 'valor_msr' => 8,
            'score_calculado' => 8.39, 'nivel_riesgo' => 'Crítico', 'vigente' => true, 'fecha_evaluacion' => now(),
        ]);

        // 'Mitigar' completado sin observacion_cierre: no debe rechazarse. El
        // "qué se hizo" para Mitigar vive en el Mantenimiento, no aquí.
        $response = $this->post(route('admin.riesgos.tratamiento.store'), [
            'fk_id_evaluacion' => $evaluacion->id_evaluacion,
            'estrategia' => 'Mitigar',
            'accion_preventiva' => 'Se reemplazó el componente dañado.',
            'estado_tratamiento' => 'Completado',
        ]);

        $response->assertSessionDoesntHaveErrors('observacion_cierre');
        $response->assertRedirect(route('admin.riesgos.tratamiento'));
        $this->assertSame('Completado', PlanTratamientoRiesgo::where('fk_id_evaluacion', $evaluacion->id_evaluacion)->firstOrFail()->estado_tratamiento);
    }

    public function test_indicador_seguimiento_enviado_por_formulario_es_ignorado(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();
        // El catálogo no tiene indicador definido para este modo de falla.
        $this->assertNull($modo->indicador_seguimiento);

        $evaluacion = EvaluacionRiesgo::create([
            'fk_id_activo' => $activo->id_activo, 'fk_id_modo_falla' => $modo->id_modo_falla,
            'valor_severidad' => 9, 'valor_frecuencia' => 6, 'valor_msr' => 8,
            'score_calculado' => 8.39, 'nivel_riesgo' => 'Crítico', 'vigente' => true, 'fecha_evaluacion' => now(),
        ]);

        // El formulario no tiene ningún input llamado 'indicador_seguimiento',
        // pero simulamos un payload manipulado que igual lo envíe.
        $response = $this->post(route('admin.riesgos.tratamiento.store'), [
            'fk_id_evaluacion' => $evaluacion->id_evaluacion,
            'estrategia' => 'Mitigar',
            'accion_preventiva' => 'Reemplazar el componente.',
            'estado_tratamiento' => 'Pendiente',
            'indicador_seguimiento' => 'Valor inventado por el usuario',
        ]);

        $response->assertRedirect(route('admin.riesgos.tratamiento'));
        // Se ignora por completo: como el catálogo no tiene indicador para
        // este modo, el resultado es null, nunca el valor enviado.
        $this->assertNull(
            PlanTratamientoRiesgo::where('fk_id_evaluacion', $evaluacion->id_evaluacion)->firstOrFail()->indicador_seguimiento
        );
    }

    public function test_indicador_seguimiento_se_copia_del_catalogo_al_generar_borrador_automatico(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();
        $modo->update(['indicador_seguimiento' => 'Tasa de disponibilidad mensual (%)']);

        app(MotorFmeaService::class)->evaluarActivo($activo->id_activo);

        $tratamiento = PlanTratamientoRiesgo::firstOrFail();
        $this->assertSame('Tasa de disponibilidad mensual (%)', $tratamiento->indicador_seguimiento);
    }

    public function test_indicador_seguimiento_queda_null_si_el_modo_de_falla_no_lo_tiene(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();
        $this->assertNull($modo->indicador_seguimiento);

        app(MotorFmeaService::class)->evaluarActivo($activo->id_activo);

        $tratamiento = PlanTratamientoRiesgo::firstOrFail();
        $this->assertNull($tratamiento->indicador_seguimiento);
    }

    public function test_indicador_seguimiento_se_copia_del_catalogo_en_tratamiento_manual_sin_borrador_previo(): void
    {
        $this->loginComoAdmin();
        [$activo, $modo] = $this->crearEscenarioCritico();
        $modo->update(['indicador_seguimiento' => 'N.º de reinicios por mes']);

        $evaluacion = EvaluacionRiesgo::create([
            'fk_id_activo' => $activo->id_activo, 'fk_id_modo_falla' => $modo->id_modo_falla,
            'valor_severidad' => 9, 'valor_frecuencia' => 6, 'valor_msr' => 8,
            'score_calculado' => 8.39, 'nivel_riesgo' => 'Crítico', 'vigente' => true, 'fecha_evaluacion' => now(),
        ]);

        // Nunca se creó un borrador automático para esta evaluación (se
        // insertó la fila directo), así que tratamientoStore toma la rama
        // "sin borrador previo" y debe copiar igual desde el catálogo.
        $response = $this->post(route('admin.riesgos.tratamiento.store'), [
            'fk_id_evaluacion' => $evaluacion->id_evaluacion,
            'estrategia' => 'Mitigar',
            'accion_preventiva' => 'Reemplazar el componente.',
            'estado_tratamiento' => 'Pendiente',
        ]);

        $response->assertRedirect(route('admin.riesgos.tratamiento'));
        $this->assertSame(
            'N.º de reinicios por mes',
            PlanTratamientoRiesgo::where('fk_id_evaluacion', $evaluacion->id_evaluacion)->firstOrFail()->indicador_seguimiento
        );
    }
}
