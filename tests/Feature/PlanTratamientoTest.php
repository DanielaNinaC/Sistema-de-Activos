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
}
