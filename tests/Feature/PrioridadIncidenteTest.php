<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ProcesoActivoController;
use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\Incidente;
use App\Models\Rol;
use App\Models\Ubicacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrioridadIncidenteTest extends TestCase
{
    use RefreshDatabase;

    private function loginComoAdmin(): Usuario
    {
        $rol = Rol::firstOrCreate(['id_rol' => 1], ['nombre_rol' => 'Administrador']);
        $admin = Usuario::create([
            'fk_id_rol' => $rol->id_rol,
            'nombre' => 'Admin',
            'apellido' => 'Prioridad',
            'correo' => 'admin.prioridad@example.com',
            'contrasena' => bcrypt('secret'),
            'estado' => 1,
        ]);

        // Los permisos por módulo (permiso_incidentes, etc.) tienen DEFAULT en
        // la base de datos, pero la instancia recién creada no los trae en
        // memoria y CheckModuleAccess los leería como null → 403. Al recargar
        // se replica lo que ocurre en la app, donde el usuario autenticado
        // siempre viene de la base de datos.
        $admin->refresh();

        $this->actingAs($admin);

        return $admin;
    }

    private function crearActivo(string $codigo, ?string $criticidad = null): ActivoTecnologico
    {
        $categoria = Categoria::firstOrCreate(
            ['nombre_categoria' => 'PCs'],
            ['vida_util_meses' => 48, 'nivel_dependencia' => 'Media']
        );
        $ubicacion = Ubicacion::firstOrCreate(['seccion' => 'Lab', 'aula' => 'A-1']);

        return ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => $codigo,
            'nombre_equipo' => 'Equipo ' . $codigo,
            'fecha_adquisicion' => now()->subMonths(6)->format('Y-m-d'),
            'criticidad_institucional' => $criticidad,
        ]);
    }

    private function crearIncidente(Usuario $usuario, ActivoTecnologico $activo, string $prioridad): Incidente
    {
        return Incidente::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_reporta' => $usuario->id_usuario,
            'fecha_incidente' => now(),
            'descripcion_falla' => 'Falla de prueba',
            'estado_incidente' => 'Reportado',
            'prioridad' => $prioridad,
        ]);
    }

    public function test_seguimiento_ordena_por_prioridad_de_atencion(): void
    {
        $admin = $this->loginComoAdmin();

        // Se crean en orden inverso al esperado para que el orden por defecto
        // (por fecha/id) no produzca un falso positivo.
        $this->crearIncidente($admin, $this->crearActivo('PC-B'), 'Baja');
        $this->crearIncidente($admin, $this->crearActivo('PC-M'), 'Media');
        $this->crearIncidente($admin, $this->crearActivo('PC-A'), 'Alta');

        $response = $this->get(route('admin.incidentes.seguimiento'));
        $response->assertOk();

        $orden = $response->viewData('incidentes')->pluck('prioridad')->all();

        $this->assertSame(['Alta', 'Media', 'Baja'], $orden);
    }

    public function test_registrar_incidente_guarda_la_prioridad_elegida(): void
    {
        $admin = $this->loginComoAdmin();
        $activo = $this->crearActivo('PC-100');

        $response = $this->post(route('admin.incidentes.registrar.store'), [
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_reporta' => $admin->id_usuario,
            'fecha_incidente' => now()->format('Y-m-d H:i:s'),
            'descripcion_falla' => 'El equipo no enciende.',
            'estado_incidente' => 'Reportado',
            'prioridad' => 'Alta',
        ]);

        $response->assertRedirect(route('admin.incidentes.seguimiento'));
        $this->assertSame('Alta', Incidente::firstOrFail()->prioridad);
    }

    public function test_rechaza_una_prioridad_fuera_del_vocabulario(): void
    {
        $admin = $this->loginComoAdmin();
        $activo = $this->crearActivo('PC-101');

        $response = $this->post(route('admin.incidentes.registrar.store'), [
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_reporta' => $admin->id_usuario,
            'fecha_incidente' => now()->format('Y-m-d H:i:s'),
            'descripcion_falla' => 'Falla.',
            'estado_incidente' => 'Reportado',
            'prioridad' => 'Urgentísima',
        ]);

        $response->assertSessionHasErrors('prioridad');
        $this->assertSame(0, Incidente::count());
    }

    public function test_actualizar_sin_enviar_prioridad_conserva_la_actual(): void
    {
        $admin = $this->loginComoAdmin();
        $incidente = $this->crearIncidente($admin, $this->crearActivo('PC-102'), 'Alta');

        // El historial reutiliza el mismo endpoint pero su modal no incluye
        // el campo de prioridad; no debe borrarla.
        $response = $this->put(route('admin.incidentes.update', $incidente->id_incidente), [
            'estado_incidente' => 'Resuelto',
        ]);

        $response->assertRedirect();

        $fresco = $incidente->fresh();
        $this->assertSame('Resuelto', $fresco->estado_incidente);
        $this->assertSame('Alta', $fresco->prioridad);
    }

    public function test_la_criticidad_del_activo_mapea_a_la_prioridad_sugerida(): void
    {
        $mapa = ProcesoActivoController::MAPA_PRIORIDAD_SUGERIDA;

        // La criticidad tiene 4 niveles y la prioridad 3: 'Crítica' cae en 'Alta'.
        $this->assertSame('Alta', $mapa['Crítica']);
        $this->assertSame('Alta', $mapa['Alta']);
        $this->assertSame('Media', $mapa['Media']);
        $this->assertSame('Baja', $mapa['Baja']);

        foreach ($mapa as $prioridadSugerida) {
            $this->assertContains($prioridadSugerida, ProcesoActivoController::PRIORIDADES_INCIDENTE);
        }
    }
}
