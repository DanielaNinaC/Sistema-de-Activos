<?php

namespace Tests\Feature;

use App\Models\ActivoTecnologico;
use App\Models\AuditoriaInterna;
use App\Models\Categoria;
use App\Models\HallazgoAuditoria;
use App\Models\Rol;
use App\Models\Ubicacion;
use App\Models\Usuario;
use App\Services\AuditoriaInternaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AuditoriaInternaExportTest extends TestCase
{
    use RefreshDatabase;

    private function crearAdmin(): Usuario
    {
        $rol = Rol::firstOrCreate(['id_rol' => (int) config('roles.admin')], ['nombre_rol' => 'Administrador']);
        $admin = Usuario::create([
            'fk_id_rol' => $rol->id_rol,
            'nombre' => 'Admin', 'apellido' => 'Export',
            'correo' => 'admin.export@example.com',
            'contrasena' => bcrypt('secret'), 'estado' => 1,
        ]);
        $admin->refresh();
        $this->actingAs($admin);

        return $admin;
    }

    public function test_pdf_y_excel_del_informe_general_se_generan_sin_error_en_borrador(): void
    {
        $admin = $this->crearAdmin();
        $categoria = Categoria::create(['nombre_categoria' => 'Servidores', 'vida_util_meses' => 60, 'nivel_dependencia' => 'Alta']);
        $ubicacion = Ubicacion::create(['seccion' => 'CPD', 'aula' => null]);
        ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria, 'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'EXP-1', 'nombre_equipo' => 'Servidor', 'estado_operativo' => 'Operativo',
            'etapa_ciclo_vida' => 'Operación', 'fecha_adquisicion' => now()->subMonths(6)->toDateString(), 'nivel_riesgo' => 'Crítico',
        ]);

        $servicio = app(AuditoriaInternaService::class);
        $auditoria = AuditoriaInterna::create([
            'codigo' => $servicio->generarCodigoAuditoria((int) now()->year),
            'gestion' => now()->year,
            'fecha_inicio' => now()->startOfYear()->toDateString(),
            'fecha_fin' => now()->endOfYear()->toDateString(),
            'fecha_corte' => now()->toDateString(),
            'estado' => 'BORRADOR',
            'fk_id_auditor' => $admin->id_usuario,
            'fk_id_responsable_ti' => $admin->id_usuario,
            'fk_id_director' => $admin->id_usuario,
        ]);
        $servicio->generarMuestra($auditoria);

        HallazgoAuditoria::create([
            'fk_id_auditoria' => $auditoria->id, 'codigo' => 'H-EXP-01', 'apartado' => '7.1',
            'condicion' => 'x', 'criterio' => 'x', 'causa' => 'x', 'efecto' => 'x', 'evidencia' => 'x', 'recomendacion' => 'x',
            'clasificacion' => 'NC_MENOR', 'responsable' => 'TI', 'fecha_compromiso' => now()->addDays(30)->toDateString(),
        ]);

        $this->get(route('admin.auditoria-interna.pdf', $auditoria->id))->assertOk();
        $this->get(route('admin.auditoria-interna.excel', $auditoria->id))->assertOk();
    }

    public function test_pdf_de_una_auditoria_aprobada_usa_el_snapshot_congelado(): void
    {
        Config::set('auditoria.muestra.minimo', 1);

        $admin = $this->crearAdmin();
        $categoria = Categoria::create(['nombre_categoria' => 'Impresoras', 'vida_util_meses' => 36, 'nivel_dependencia' => 'Baja']);
        $ubicacion = Ubicacion::create(['seccion' => 'Sec', 'aula' => null]);
        ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria, 'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'EXP-2', 'nombre_equipo' => 'Impresora', 'estado_operativo' => 'Operativo',
            'etapa_ciclo_vida' => 'Operación', 'fecha_adquisicion' => now()->subMonths(6)->toDateString(), 'nivel_riesgo' => 'Bajo',
        ]);

        $servicio = app(AuditoriaInternaService::class);
        $auditoria = AuditoriaInterna::create([
            'codigo' => $servicio->generarCodigoAuditoria((int) now()->year),
            'gestion' => now()->year,
            'fecha_inicio' => now()->startOfYear()->toDateString(),
            'fecha_fin' => now()->endOfYear()->toDateString(),
            'fecha_corte' => now()->toDateString(),
            'objetivo' => 'x', 'alcance' => 'x', 'conclusiones' => 'x',
            'estado' => 'BORRADOR',
            'fk_id_auditor' => $admin->id_usuario,
            'fk_id_responsable_ti' => $admin->id_usuario,
            'fk_id_director' => $admin->id_usuario,
        ]);
        $servicio->generarMuestra($auditoria);
        $auditoria->muestra()->update(['encontrado' => true, 'ubicacion_coincide' => true, 'estado_coincide' => true]);
        $auditoria = $servicio->aprobar($auditoria->fresh());

        $this->get(route('admin.auditoria-interna.pdf', $auditoria->id))->assertOk();
        $this->get(route('admin.auditoria-interna.excel', $auditoria->id))->assertOk();
    }
}
