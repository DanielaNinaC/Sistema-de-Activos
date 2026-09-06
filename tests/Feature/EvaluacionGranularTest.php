<?php

namespace Tests\Feature;

use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\EvaluacionRiesgo;
use App\Models\ModoFalla;
use App\Models\PesoAhp;
use App\Models\Rol;
use App\Models\Ubicacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Selección granular de modos de falla al ejecutar el motor FMEA
 * (EvaluarActivoRequest + ProcesoRiesgoController::evaluarActivo).
 */
class EvaluacionGranularTest extends TestCase
{
    use RefreshDatabase;

    private function loginComoAdmin(): Usuario
    {
        $rol = Rol::firstOrCreate(['id_rol' => 1], ['nombre_rol' => 'Administrador']);
        $admin = Usuario::create([
            'fk_id_rol' => $rol->id_rol,
            'nombre' => 'Admin',
            'apellido' => 'Granular',
            'correo' => 'admin.granular@example.com',
            'contrasena' => bcrypt('secret'),
            'estado' => 1,
        ]);

        // Los permisos por módulo tienen DEFAULT en la base de datos y no
        // vienen en la instancia recién creada; sin recargar, CheckModuleAccess
        // los leería como null y devolvería 403.
        $admin->refresh();
        $this->actingAs($admin);

        return $admin;
    }

    private function crearPesos(): void
    {
        PesoAhp::create([
            'peso_severidad' => 0.648,
            'peso_frecuencia' => 0.230,
            'peso_msr' => 0.122,
            'fecha_actualizacion' => now(),
        ]);
    }

    /** @return array{0: ActivoTecnologico, 1: \Illuminate\Support\Collection} */
    private function crearActivoConModos(int $cantidad, string $codigo = 'GRA-001'): array
    {
        $categoria = Categoria::create([
            'nombre_categoria' => 'Categoría ' . $codigo,
            'vida_util_meses' => 60,
            'nivel_dependencia' => 'Media',
        ]);

        // Ojo: range(1, 0) devuelve [1, 0] en PHP, así que el caso "sin modos"
        // necesita una lista vacía explícita.
        $indices = $cantidad > 0 ? range(1, $cantidad) : [];

        $modos = collect($indices)->map(fn ($i) => ModoFalla::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'nombre_modo_falla' => "Modo {$i} de {$codigo}",
            'valor_severidad_base' => 5,
            'valor_msr_base' => 6,
        ]));

        $ubicacion = Ubicacion::create(['seccion' => 'Sec', 'aula' => $codigo]);

        $activo = ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => $codigo,
            'nombre_equipo' => 'Equipo ' . $codigo,
            'fecha_adquisicion' => now()->subMonths(6)->format('Y-m-d'),
        ]);

        return [$activo, $modos];
    }

    public function test_modo_todos_evalua_el_catalogo_completo(): void
    {
        $this->loginComoAdmin();
        $this->crearPesos();
        [$activo] = $this->crearActivoConModos(5);

        $response = $this->post(route('admin.riesgos.evaluar.activo'), [
            'fk_id_activo' => $activo->id_activo,
            'modo_evaluacion' => 'todos',
        ]);

        $response->assertRedirect(route('admin.riesgos.evaluacion'));
        $this->assertSame(5, EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)->where('vigente', true)->count());
    }

    public function test_modo_especificos_solo_evalua_los_seleccionados(): void
    {
        $this->loginComoAdmin();
        $this->crearPesos();
        [$activo, $modos] = $this->crearActivoConModos(5);

        $response = $this->post(route('admin.riesgos.evaluar.activo'), [
            'fk_id_activo' => $activo->id_activo,
            'modo_evaluacion' => 'especificos',
            'modos_falla_ids' => [$modos[1]->id_modo_falla],
        ]);

        $response->assertRedirect(route('admin.riesgos.evaluacion'));
        $this->assertSame(1, EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)->count());
        $this->assertDatabaseHas('evaluacion_riesgo', [
            'fk_id_activo' => $activo->id_activo,
            'fk_id_modo_falla' => $modos[1]->id_modo_falla,
            'vigente' => true,
        ]);
    }

    public function test_rechaza_un_modo_de_falla_de_otra_categoria(): void
    {
        $this->loginComoAdmin();
        $this->crearPesos();
        [$activo] = $this->crearActivoConModos(3, 'GRA-A');
        [, $modosAjenos] = $this->crearActivoConModos(2, 'GRA-B');

        $response = $this->post(route('admin.riesgos.evaluar.activo'), [
            'fk_id_activo' => $activo->id_activo,
            'modo_evaluacion' => 'especificos',
            'modos_falla_ids' => [$modosAjenos[0]->id_modo_falla],
        ]);

        $response->assertSessionHasErrors('modos_falla_ids');
        $this->assertSame(0, EvaluacionRiesgo::count());
    }

    public function test_rechaza_seleccion_especifica_sin_modos(): void
    {
        $this->loginComoAdmin();
        $this->crearPesos();
        [$activo] = $this->crearActivoConModos(3);

        $response = $this->post(route('admin.riesgos.evaluar.activo'), [
            'fk_id_activo' => $activo->id_activo,
            'modo_evaluacion' => 'especificos',
        ]);

        $response->assertSessionHasErrors('modos_falla_ids');
        $this->assertSame(0, EvaluacionRiesgo::count());
    }

    public function test_activo_sin_modos_en_su_categoria_avisa_y_no_evalua(): void
    {
        $this->loginComoAdmin();
        $this->crearPesos();
        [$activo] = $this->crearActivoConModos(0, 'GRA-SIN');

        $response = $this->post(route('admin.riesgos.evaluar.activo'), [
            'fk_id_activo' => $activo->id_activo,
            'modo_evaluacion' => 'todos',
        ]);

        $response->assertRedirect(route('admin.riesgos.evaluacion'));
        $response->assertSessionHas('warning');
        $this->assertSame(0, EvaluacionRiesgo::count());
    }

    public function test_sin_modo_evaluacion_se_comporta_como_todos(): void
    {
        $this->loginComoAdmin();
        $this->crearPesos();
        [$activo] = $this->crearActivoConModos(4);

        // Compatibilidad con el payload anterior a esta funcionalidad.
        $response = $this->post(route('admin.riesgos.evaluar.activo'), [
            'fk_id_activo' => $activo->id_activo,
        ]);

        $response->assertRedirect(route('admin.riesgos.evaluacion'));
        $this->assertSame(4, EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)->where('vigente', true)->count());
    }
}
