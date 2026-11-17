<?php

namespace Tests\Feature;

use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\EvaluacionRiesgo;
use App\Models\Incidente;
use App\Models\Log;
use App\Models\Mantenimiento;
use App\Models\ModoFalla;
use App\Models\Rol;
use App\Models\Ubicacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AuditoriaReportesTest extends TestCase
{
    use RefreshDatabase;

    private function loginComoAdmin(): Usuario
    {
        $rol = Rol::create(['nombre_rol' => 'Administrador']);
        $admin = Usuario::create([
            'fk_id_rol' => $rol->id_rol,
            'nombre' => 'Admin',
            'apellido' => 'Auditoria',
            'correo' => 'admin.auditoria@example.com',
            'contrasena' => bcrypt('secret'),
            'estado' => 1,
        ]);
        $admin->refresh();
        $this->actingAs($admin);

        return $admin;
    }

    private function crearActivo(Categoria $categoria, Ubicacion $ubicacion, array $overrides = []): ActivoTecnologico
    {
        return ActivoTecnologico::create(array_merge([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'RA-' . uniqid(),
            'nombre_equipo' => 'Equipo de Prueba',
            'fecha_adquisicion' => now()->subYear()->format('Y-m-d'),
            'estado_operativo' => 'Operativo',
            'etapa_ciclo_vida' => 'Operación',
        ], $overrides));
    }

    // =========================================================================
    // RA-01 — Inventario General
    // =========================================================================

    public function test_ra01_genera_pantalla_pdf_y_excel_y_registra_en_logs(): void
    {
        $this->loginComoAdmin();

        $categoria = Categoria::create(['nombre_categoria' => 'Laptops', 'vida_util_meses' => 12, 'nivel_dependencia' => 'Alta']);
        $ubicacion = Ubicacion::create(['seccion' => 'Secundaria', 'aula' => 'A1']);
        $this->crearActivo($categoria, $ubicacion, ['fecha_adquisicion' => now()->subMonths(20)->format('Y-m-d')]);

        $pantalla = $this->get(route('admin.informes.auditoria-ti.ra01'));
        $pantalla->assertOk();
        $pantalla->assertSee('Inventario General');

        $controlador = app(\App\Http\Controllers\Admin\AuditoriaReportesController::class);
        $reflexion = new \ReflectionMethod($controlador, 'obtenerInventarioRA01');
        $reflexion->setAccessible(true);
        $datos = $reflexion->invoke($controlador, [
            'gestion' => (int) now()->year,
            'fecha_corte' => now()->toDateString(),
            'fk_id_categoria' => null,
            'fk_id_ubicacion' => null,
        ]);

        $this->assertSame(1, $datos['total']);
        $this->assertSame(1, $datos['alertas']['vida_util_vencida']);
        $this->assertSame(0, $datos['alertas']['sin_ubicacion']);

        $this->get(route('admin.informes.auditoria-ti.ra01.pdf'))->assertOk();
        $this->get(route('admin.informes.auditoria-ti.ra01.excel'))->assertOk();

        $this->assertSame(3, Log::where('tabla_afectada', 'auditoria_ti')->where('accion_realizada', 'like', '%RA-01%')->count());
    }

    // =========================================================================
    // RA-02 — Plan y Ejecución de Mantenimiento
    // =========================================================================

    public function test_ra02_calcula_cumplimiento_y_porcentaje_preventivo_correctamente(): void
    {
        $admin = $this->loginComoAdmin();

        $categoria = Categoria::create(['nombre_categoria' => 'Servidores', 'vida_util_meses' => 60, 'nivel_dependencia' => 'Alta']);
        $ubicacion = Ubicacion::create(['seccion' => 'CPD', 'aula' => null]);
        $activo = $this->crearActivo($categoria, $ubicacion);

        $gestion = (int) now()->year;

        // 2 programados, 1 ejecutado (Preventivo), 1 no ejecutado (Correctivo) y vencido.
        Mantenimiento::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_asignado' => $admin->id_usuario,
            'tipo_mantenimiento' => 'Preventivo',
            'fecha_programada' => now()->startOfYear()->addMonth()->format('Y-m-d'),
            'fecha_ejecucion' => now()->startOfYear()->addMonth()->format('Y-m-d'),
            'estado_mantenimiento' => 'Finalizado',
        ]);
        Mantenimiento::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_asignado' => $admin->id_usuario,
            'tipo_mantenimiento' => 'Correctivo',
            'fecha_programada' => now()->subDay()->format('Y-m-d'),
            'fecha_ejecucion' => null,
            'estado_mantenimiento' => 'Pendiente',
        ]);

        $respuesta = $this->get(route('admin.informes.auditoria-ti.ra02', ['gestion' => $gestion, 'fecha_corte' => now()->toDateString()]));
        $respuesta->assertOk();

        $controlador = app(\App\Http\Controllers\Admin\AuditoriaReportesController::class);
        $reflexion = new \ReflectionMethod($controlador, 'obtenerMantenimientoRA02');
        $reflexion->setAccessible(true);
        $datos = $reflexion->invoke($controlador, [
            'gestion' => $gestion,
            'fecha_corte' => now()->toDateString(),
            'fk_id_categoria' => null,
            'fk_id_ubicacion' => null,
        ]);

        $this->assertSame(2, $datos['programados']);
        $this->assertSame(1, $datos['ejecutados']);
        $this->assertEquals(50.0, $datos['cumplimiento_pct']);
        $this->assertEquals(50.0, $datos['preventivo_pct']);
        $this->assertCount(1, $datos['vencidos']);

        $this->get(route('admin.informes.auditoria-ti.ra02.pdf', ['gestion' => $gestion]))->assertOk();
        $this->get(route('admin.informes.auditoria-ti.ra02.excel', ['gestion' => $gestion]))->assertOk();
    }

    // =========================================================================
    // RA-03 — Registro de Incidentes / TMR
    // =========================================================================

    public function test_ra03_calcula_tmr_e_incidentes_abiertos_a_la_fecha_de_corte(): void
    {
        $admin = $this->loginComoAdmin();

        $categoria = Categoria::create(['nombre_categoria' => 'Redes', 'vida_util_meses' => 48, 'nivel_dependencia' => 'Media']);
        $ubicacion = Ubicacion::create(['seccion' => 'Primaria', 'aula' => null]);
        $activo = $this->crearActivo($categoria, $ubicacion);

        $gestion = (int) now()->year;
        $apertura = now()->startOfYear()->addDays(10);

        // Incidente cerrado 4 días después de abierto (TMR = 4).
        Incidente::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_reporta' => $admin->id_usuario,
            'fecha_incidente' => $apertura,
            'descripcion_falla' => 'Falla de conexión',
            'estado_incidente' => 'Resuelto',
            'fecha_cierre' => $apertura->copy()->addDays(4),
        ]);

        // Incidente abierto, sin cerrar, dentro del período.
        Incidente::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_reporta' => $admin->id_usuario,
            'fecha_incidente' => now()->startOfYear()->addDays(20),
            'descripcion_falla' => 'Equipo no enciende',
            'estado_incidente' => 'Reportado',
            'fecha_cierre' => null,
        ]);

        $controlador = app(\App\Http\Controllers\Admin\AuditoriaReportesController::class);
        $reflexion = new \ReflectionMethod($controlador, 'obtenerIncidentesRA03');
        $reflexion->setAccessible(true);
        $datos = $reflexion->invoke($controlador, [
            'gestion' => $gestion,
            'fecha_corte' => now()->endOfYear()->toDateString(),
            'fk_id_categoria' => null,
            'fk_id_ubicacion' => null,
        ]);

        $this->assertSame(2, $datos['total']);
        $this->assertEquals(4.0, $datos['tmr_dias']);
        $this->assertSame(1, $datos['abiertos_a_corte']);

        $this->get(route('admin.informes.auditoria-ti.ra03', ['gestion' => $gestion]))->assertOk();
        $this->get(route('admin.informes.auditoria-ti.ra03.pdf', ['gestion' => $gestion]))->assertOk();
        $this->get(route('admin.informes.auditoria-ti.ra03.excel', ['gestion' => $gestion]))->assertOk();
    }

    // =========================================================================
    // RA-04 — Clasificación por Criticidad
    // =========================================================================

    public function test_ra04_cruza_dependencia_de_categoria_con_nivel_de_riesgo_del_activo(): void
    {
        $this->loginComoAdmin();

        $categoria = Categoria::create(['nombre_categoria' => 'Servidores Críticos', 'vida_util_meses' => 60, 'nivel_dependencia' => 'Alta']);
        $ubicacion = Ubicacion::create(['seccion' => 'CPD', 'aula' => null]);
        $this->crearActivo($categoria, $ubicacion, ['nivel_riesgo' => 'Crítico']);
        $this->crearActivo($categoria, $ubicacion, ['nivel_riesgo' => null]);

        $respuesta = $this->get(route('admin.informes.auditoria-ti.ra04'));
        $respuesta->assertOk();
        $respuesta->assertSee('Alta');
        $respuesta->assertSee('Crítico');
        $respuesta->assertSee('Sin evaluar');

        $this->get(route('admin.informes.auditoria-ti.ra04.pdf'))->assertOk();
        $this->get(route('admin.informes.auditoria-ti.ra04.excel'))->assertOk();
    }

    // =========================================================================
    // RA-05 — Matriz de Roles y Permisos
    // =========================================================================

    public function test_ra05_muestra_matriz_de_permisos_y_usuarios_administradores(): void
    {
        $this->loginComoAdmin();

        $respuesta = $this->get(route('admin.informes.auditoria-ti.ra05'));
        $respuesta->assertOk();
        $respuesta->assertSee('Administrador');
        $respuesta->assertSee('admin.auditoria@example.com');

        $this->get(route('admin.informes.auditoria-ti.ra05.pdf'))->assertOk();
        $this->get(route('admin.informes.auditoria-ti.ra05.excel'))->assertOk();
    }

    // =========================================================================
    // RA-06 — Hoja de Verificación Física (CPD)
    // =========================================================================

    public function test_ra06_requiere_ubicacion_para_exportar_pdf_y_lista_sus_activos(): void
    {
        $this->loginComoAdmin();

        $categoria = Categoria::create(['nombre_categoria' => 'Switches', 'vida_util_meses' => 60, 'nivel_dependencia' => 'Alta']);
        $ubicacion = Ubicacion::create(['seccion' => 'CPD', 'aula' => 'Principal']);
        $this->crearActivo($categoria, $ubicacion);

        $this->get(route('admin.informes.auditoria-ti.ra06'))->assertOk();

        $conFiltro = $this->get(route('admin.informes.auditoria-ti.ra06', ['fk_id_ubicacion' => $ubicacion->id_ubicacion]));
        $conFiltro->assertOk();
        $conFiltro->assertSee('Encontrado');

        $this->get(route('admin.informes.auditoria-ti.ra06.pdf', ['fk_id_ubicacion' => $ubicacion->id_ubicacion]))->assertOk();
        $this->get(route('admin.informes.auditoria-ti.ra06.pdf'))->assertSessionHasErrors('fk_id_ubicacion');
    }

    // =========================================================================
    // RA-07 — Activos de Red por Ubicación
    // =========================================================================

    public function test_ra07_avisa_si_no_hay_categorias_de_red_configuradas_y_lista_activos_si_las_hay(): void
    {
        $this->loginComoAdmin();

        $sinConfigurar = $this->get(route('admin.informes.auditoria-ti.ra07'));
        $sinConfigurar->assertOk();
        $sinConfigurar->assertSee('No se ha configurado');

        $categoria = Categoria::create(['nombre_categoria' => 'Equipos de Red', 'vida_util_meses' => 60, 'nivel_dependencia' => 'Alta']);
        $ubicacion = Ubicacion::create(['seccion' => 'CPD', 'aula' => null]);
        $this->crearActivo($categoria, $ubicacion);

        Config::set('auditoria.categorias_red', [$categoria->id_categoria]);

        $configurado = $this->get(route('admin.informes.auditoria-ti.ra07'));
        $configurado->assertOk();
        $configurado->assertDontSee('No se ha configurado');

        $this->get(route('admin.informes.auditoria-ti.ra07.pdf'))->assertOk();
        $this->get(route('admin.informes.auditoria-ti.ra07.excel'))->assertOk();
    }
}
