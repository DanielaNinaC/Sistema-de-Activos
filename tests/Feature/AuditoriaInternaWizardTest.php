<?php

namespace Tests\Feature;

use App\Models\ActivoTecnologico;
use App\Models\AuditoriaInterna;
use App\Models\Categoria;
use App\Models\HallazgoAuditoria;
use App\Models\Rol;
use App\Models\Ubicacion;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AuditoriaInternaWizardTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(string $rolConfigKey, string $nombreRol, string $correo): Usuario
    {
        $rol = Rol::firstOrCreate(['id_rol' => (int) config("roles.{$rolConfigKey}")], ['nombre_rol' => $nombreRol]);
        $usuario = Usuario::create([
            'fk_id_rol' => $rol->id_rol,
            'nombre' => $nombreRol,
            'apellido' => 'Prueba',
            'correo' => $correo,
            'contrasena' => bcrypt('secret'),
            'estado' => 1,
        ]);
        $usuario->refresh();

        return $usuario;
    }

    private function crearActivo(Categoria $categoria, Ubicacion $ubicacion, array $overrides = []): ActivoTecnologico
    {
        return ActivoTecnologico::create(array_merge([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'WIZ-' . uniqid(),
            'nombre_equipo' => 'Equipo Wizard',
            'fecha_adquisicion' => now()->subMonths(6)->format('Y-m-d'),
            'estado_operativo' => 'Operativo',
            'etapa_ciclo_vida' => 'Operación',
        ], $overrides));
    }

    public function test_flujo_completo_borrador_muestra_hallazgos_y_aprobacion(): void
    {
        Config::set('auditoria.muestra.minimo', 1);

        $admin = $this->crearUsuario('admin', 'Administrador', 'admin.wizard@example.com');
        $categoria = Categoria::create(['nombre_categoria' => 'Servidores', 'vida_util_meses' => 60, 'nivel_dependencia' => 'Alta']);
        $ubicacion = Ubicacion::create(['seccion' => 'CPD', 'aula' => null]);
        $this->crearActivo($categoria, $ubicacion, ['nivel_riesgo' => 'Bajo']);

        $this->actingAs($admin);

        $respuestaStore = $this->post(route('admin.auditoria-interna.store'), [
            'gestion' => now()->year,
            'fecha_inicio' => now()->startOfYear()->toDateString(),
            'fecha_fin' => now()->endOfYear()->toDateString(),
            'fecha_corte' => now()->toDateString(),
            'objetivo' => 'Objetivo de prueba',
            'alcance' => 'Alcance de prueba',
        ]);
        $respuestaStore->assertRedirect();

        $auditoria = AuditoriaInterna::firstOrFail();
        $this->assertSame('BORRADOR', $auditoria->estado);

        $this->post(route('admin.auditoria-interna.muestra.generar', $auditoria->id))->assertRedirect();
        $auditoria->refresh();
        $this->assertGreaterThan(0, $auditoria->muestra->count());

        foreach ($auditoria->muestra as $item) {
            $this->put(route('admin.auditoria-interna.muestra.verificar', [$auditoria->id, $item->id]), [
                'encontrado' => 1, 'ubicacion_coincide' => 1, 'estado_coincide' => 1,
            ])->assertRedirect();
        }

        $this->post(route('admin.auditoria-interna.hallazgos.store', $auditoria->id), [
            'apartado' => '7.1',
            'condicion' => 'x', 'criterio' => 'x', 'causa' => 'x', 'efecto' => 'x', 'evidencia' => 'x', 'recomendacion' => 'x',
            'clasificacion' => 'OBSERVACION',
            'responsable' => 'TI',
            'fecha_compromiso' => now()->addDays(30)->toDateString(),
        ])->assertRedirect();

        $this->assertSame(1, HallazgoAuditoria::count());

        $this->post(route('admin.auditoria-interna.enviar-revision', $auditoria->id))->assertRedirect();
        $this->assertSame('EN_REVISION', $auditoria->fresh()->estado);

        $this->put(route('admin.auditoria-interna.update', $auditoria->id), [
            'conclusiones' => 'Conclusión de prueba',
            'fk_id_responsable_ti' => $admin->id_usuario,
            'fk_id_director' => $admin->id_usuario,
        ])->assertRedirect();

        $this->post(route('admin.auditoria-interna.aprobar', $auditoria->id))->assertRedirect();

        $auditoria->refresh();
        $this->assertSame('APROBADO', $auditoria->estado);
        $this->assertNotNull($auditoria->snapshot_indicadores);
    }

    public function test_soporte_no_puede_editar_datos_generales_pero_puede_comentar_un_hallazgo(): void
    {
        $admin = $this->crearUsuario('admin', 'Administrador', 'admin.wizard2@example.com');
        $soporte = $this->crearUsuario('soporte', 'Soporte', 'soporte.wizard@example.com');

        $auditoria = AuditoriaInterna::create([
            'codigo' => 'INF-AUD-TI-TEST-01',
            'gestion' => now()->year,
            'fecha_inicio' => now()->startOfYear()->toDateString(),
            'fecha_fin' => now()->endOfYear()->toDateString(),
            'fecha_corte' => now()->toDateString(),
            'estado' => 'BORRADOR',
            'fk_id_auditor' => $admin->id_usuario,
        ]);

        $hallazgo = HallazgoAuditoria::create([
            'fk_id_auditoria' => $auditoria->id, 'codigo' => 'H-TEST-W1', 'apartado' => '7.1',
            'condicion' => 'x', 'criterio' => 'x', 'causa' => 'x', 'efecto' => 'x', 'evidencia' => 'x', 'recomendacion' => 'x',
            'clasificacion' => 'OBSERVACION', 'responsable' => 'TI',
        ]);

        $this->actingAs($soporte);

        $this->put(route('admin.auditoria-interna.update', $auditoria->id), ['objetivo' => 'Intento no autorizado'])
            ->assertForbidden();

        $this->put(route('admin.auditoria-interna.hallazgos.update', $hallazgo->id), [
            'comentario_area' => 'Ya se corrigió en el sistema.',
            'estado' => 'EN_PROCESO',
        ])->assertRedirect();

        $hallazgo->refresh();
        $this->assertSame('Ya se corrigió en el sistema.', $hallazgo->comentario_area);
        $this->assertSame('EN_PROCESO', $hallazgo->estado);
    }

    public function test_director_solo_puede_ver_no_editar_ni_aprobar(): void
    {
        $admin = $this->crearUsuario('admin', 'Administrador', 'admin.wizard3@example.com');
        $director = $this->crearUsuario('director', 'Director', 'director.wizard@example.com');

        $auditoria = AuditoriaInterna::create([
            'codigo' => 'INF-AUD-TI-TEST-02',
            'gestion' => now()->year,
            'fecha_inicio' => now()->startOfYear()->toDateString(),
            'fecha_fin' => now()->endOfYear()->toDateString(),
            'fecha_corte' => now()->toDateString(),
            'estado' => 'BORRADOR',
            'fk_id_auditor' => $admin->id_usuario,
        ]);

        $this->actingAs($director);

        $this->get(route('admin.auditoria-interna.show', $auditoria->id))->assertOk();
        $this->post(route('admin.auditoria-interna.muestra.generar', $auditoria->id))->assertForbidden();
        $this->post(route('admin.auditoria-interna.aprobar', $auditoria->id))->assertForbidden();
        $this->get(route('admin.auditoria-interna.create'))->assertForbidden();
    }

    public function test_al_crear_una_auditoria_objetivo_alcance_y_antecedentes_se_generan_solos_por_pln(): void
    {
        $admin = $this->crearUsuario('admin', 'Administrador', 'admin.pln@example.com');
        $this->actingAs($admin);

        $this->post(route('admin.auditoria-interna.store'), [
            'gestion' => now()->year,
            'fecha_inicio' => now()->startOfYear()->toDateString(),
            'fecha_fin' => now()->endOfYear()->toDateString(),
            'fecha_corte' => now()->toDateString(),
        ])->assertRedirect();

        $auditoria = AuditoriaInterna::firstOrFail();
        $this->assertNotEmpty($auditoria->objetivo);
        $this->assertNotEmpty($auditoria->alcance);
        $this->assertNotEmpty($auditoria->antecedentes);
    }

    public function test_boton_generar_con_pln_regenera_los_7_campos_y_respeta_permisos(): void
    {
        $admin = $this->crearUsuario('admin', 'Administrador', 'admin.pln2@example.com');
        $director = $this->crearUsuario('director', 'Director', 'director.pln@example.com');

        $auditoria = AuditoriaInterna::create([
            'codigo' => 'INF-AUD-TI-TEST-PLN', 'gestion' => now()->year,
            'fecha_inicio' => now()->startOfYear()->toDateString(), 'fecha_fin' => now()->endOfYear()->toDateString(),
            'fecha_corte' => now()->toDateString(), 'estado' => 'BORRADOR', 'fk_id_auditor' => $admin->id_usuario,
        ]);

        $this->actingAs($director);
        $this->post(route('admin.auditoria-interna.generar-textos', $auditoria->id))->assertForbidden();

        $this->actingAs($admin);
        $this->post(route('admin.auditoria-interna.generar-textos', $auditoria->id))->assertRedirect();

        $auditoria->refresh();
        foreach (['objetivo', 'alcance', 'antecedentes', 'metodologia', 'limitaciones', 'buenas_practicas', 'conclusiones'] as $campo) {
            $this->assertNotEmpty($auditoria->{$campo});
        }
    }
}
