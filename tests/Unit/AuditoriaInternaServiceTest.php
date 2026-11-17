<?php

namespace Tests\Unit;

use App\Models\ActivoTecnologico;
use App\Models\AuditoriaInterna;
use App\Models\Categoria;
use App\Models\HallazgoAuditoria;
use App\Models\Incidente;
use App\Models\Rol;
use App\Models\Ubicacion;
use App\Models\Usuario;
use App\Services\AuditoriaInternaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AuditoriaInternaServiceTest extends TestCase
{
    use RefreshDatabase;

    private function crearAdmin(): Usuario
    {
        $rol = Rol::firstOrCreate(['id_rol' => (int) config('roles.admin')], ['nombre_rol' => 'Administrador']);
        $admin = Usuario::create([
            'fk_id_rol' => $rol->id_rol,
            'nombre' => 'Admin',
            'apellido' => 'Auditoria',
            'correo' => 'admin.auditoria-service@example.com',
            'contrasena' => bcrypt('secret'),
            'estado' => 1,
        ]);
        $admin->refresh();

        return $admin;
    }

    private function crearActivo(Categoria $categoria, Ubicacion $ubicacion, array $overrides = []): ActivoTecnologico
    {
        return ActivoTecnologico::create(array_merge([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'AUD-' . uniqid(),
            'nombre_equipo' => 'Equipo de Prueba',
            'fecha_adquisicion' => now()->subMonths(6)->format('Y-m-d'),
            'estado_operativo' => 'Operativo',
            'etapa_ciclo_vida' => 'Operación',
        ], $overrides));
    }

    private function crearAuditoria(Usuario $auditor, array $overrides = []): AuditoriaInterna
    {
        $servicio = app(AuditoriaInternaService::class);

        return AuditoriaInterna::create(array_merge([
            'codigo' => $servicio->generarCodigoAuditoria((int) now()->year),
            'gestion' => (int) now()->year,
            'fecha_inicio' => now()->startOfYear()->format('Y-m-d'),
            'fecha_fin' => now()->endOfYear()->format('Y-m-d'),
            'fecha_corte' => now()->toDateString(),
            'estado' => 'BORRADOR',
            'fk_id_auditor' => $auditor->id_usuario,
            'fk_id_responsable_ti' => $auditor->id_usuario,
            'fk_id_director' => $auditor->id_usuario,
        ], $overrides));
    }

    // =========================================================================
    // generarMuestra
    // =========================================================================

    public function test_generar_muestra_incluye_el_100_por_ciento_de_criticos_y_altos_y_respeta_el_minimo(): void
    {
        Config::set('auditoria.muestra.minimo', 5);
        Config::set('auditoria.muestra.porcentaje', 10);

        $auditor = $this->crearAdmin();
        $categoria = Categoria::create(['nombre_categoria' => 'Servidores', 'vida_util_meses' => 60, 'nivel_dependencia' => 'Alta']);
        $ubicacion = Ubicacion::create(['seccion' => 'CPD', 'aula' => null]);

        $critico1 = $this->crearActivo($categoria, $ubicacion, ['nivel_riesgo' => 'Crítico']);
        $critico2 = $this->crearActivo($categoria, $ubicacion, ['nivel_riesgo' => 'Crítico']);
        $alto1 = $this->crearActivo($categoria, $ubicacion, ['nivel_riesgo' => 'Alto']);
        // 5 activos de bajo riesgo para completar el mínimo con aleatorios.
        for ($i = 0; $i < 5; $i++) {
            $this->crearActivo($categoria, $ubicacion, ['nivel_riesgo' => 'Bajo']);
        }

        $auditoria = $this->crearAuditoria($auditor);
        $servicio = app(AuditoriaInternaService::class);
        $auditoria = $servicio->generarMuestra($auditoria);

        $idsEnMuestra = $auditoria->muestra->pluck('fk_id_activo')->all();

        $this->assertContains($critico1->id_activo, $idsEnMuestra);
        $this->assertContains($critico2->id_activo, $idsEnMuestra);
        $this->assertContains($alto1->id_activo, $idsEnMuestra);
        $this->assertSame(3, $auditoria->muestra->where('motivo_inclusion', 'RIESGO_ALTO_CRITICO')->count());
        $this->assertGreaterThanOrEqual(5, $auditoria->muestra->count());
    }

    public function test_generar_muestra_falla_si_la_auditoria_no_esta_en_borrador(): void
    {
        $auditor = $this->crearAdmin();
        $auditoria = $this->crearAuditoria($auditor, ['estado' => 'EN_REVISION']);

        $this->expectException(\RuntimeException::class);
        app(AuditoriaInternaService::class)->generarMuestra($auditoria);
    }

    // =========================================================================
    // calcularIndicadores — caso null cuando falta el dato
    // =========================================================================

    public function test_indicador_tmr_incidentes_es_null_sin_incidentes_cerrados(): void
    {
        $auditor = $this->crearAdmin();
        $auditoria = $this->crearAuditoria($auditor);

        $indicadores = app(AuditoriaInternaService::class)->calcularIndicadores($auditoria);

        $this->assertNull($indicadores['tmr_incidentes_dias']['valor']);
        $this->assertNull($indicadores['tmr_incidentes_dias']['cumple']);
    }

    public function test_indicador_hallazgos_anteriores_cerrados_no_aplica_sin_auditoria_previa(): void
    {
        $auditor = $this->crearAdmin();
        $auditoria = $this->crearAuditoria($auditor);

        $indicadores = app(AuditoriaInternaService::class)->calcularIndicadores($auditoria);

        $this->assertNull($indicadores['hallazgos_anteriores_cerrados']['valor']);
        $this->assertArrayHasKey('motivo', $indicadores['hallazgos_anteriores_cerrados']);
    }

    // =========================================================================
    // semaforoPorApartado / opinionGeneral
    // =========================================================================

    public function test_apartado_75_es_verde_cuando_el_numero_de_administradores_cumple_la_meta(): void
    {
        $auditor = $this->crearAdmin();
        $auditoria = $this->crearAuditoria($auditor);

        $semaforo = app(AuditoriaInternaService::class)->semaforoPorApartado($auditoria);

        $this->assertSame('VERDE', $semaforo['7.5']['color']);
    }

    public function test_apartado_sin_datos_calculables_es_amarillo_por_defecto(): void
    {
        $auditor = $this->crearAdmin();
        $auditoria = $this->crearAuditoria($auditor);

        $semaforo = app(AuditoriaInternaService::class)->semaforoPorApartado($auditoria);

        $this->assertSame('AMARILLO', $semaforo['7.4']['color']);
    }

    public function test_un_hallazgo_nc_mayor_fuerza_el_rojo_en_su_apartado_sin_importar_los_indicadores(): void
    {
        $auditor = $this->crearAdmin();
        $auditoria = $this->crearAuditoria($auditor);

        HallazgoAuditoria::create([
            'fk_id_auditoria' => $auditoria->id,
            'codigo' => 'H-TEST-01',
            'apartado' => '7.5',
            'condicion' => 'x', 'criterio' => 'x', 'causa' => 'x', 'efecto' => 'x', 'evidencia' => 'x', 'recomendacion' => 'x',
            'clasificacion' => 'NC_MAYOR',
            'responsable' => 'TI',
        ]);

        $semaforo = app(AuditoriaInternaService::class)->semaforoPorApartado($auditoria->fresh());

        // Sin el hallazgo, 7.5 sería VERDE (1 admin, meta <=3): el NC_MAYOR debe forzar ROJO igual.
        $this->assertSame('ROJO', $semaforo['7.5']['color']);
    }

    public function test_opinion_general_es_insatisfactorio_si_algun_apartado_esta_en_rojo(): void
    {
        $auditor = $this->crearAdmin();
        $auditoria = $this->crearAuditoria($auditor);

        HallazgoAuditoria::create([
            'fk_id_auditoria' => $auditoria->id,
            'codigo' => 'H-TEST-02',
            'apartado' => '7.5',
            'condicion' => 'x', 'criterio' => 'x', 'causa' => 'x', 'efecto' => 'x', 'evidencia' => 'x', 'recomendacion' => 'x',
            'clasificacion' => 'NC_MAYOR',
            'responsable' => 'TI',
        ]);

        $opinion = app(AuditoriaInternaService::class)->opinionGeneral($auditoria->fresh());

        $this->assertSame('INSATISFACTORIO', $opinion);
    }

    public function test_opinion_general_es_con_observaciones_sin_rojos_pero_con_amarillos(): void
    {
        $auditor = $this->crearAdmin();
        $auditoria = $this->crearAuditoria($auditor);

        $opinion = app(AuditoriaInternaService::class)->opinionGeneral($auditoria);

        // Sin datos de mantenimiento/incidentes/muestra, varios apartados quedan
        // en ámbar por indicadores no calculables, pero ninguno en rojo.
        $this->assertSame('CON_OBSERVACIONES', $opinion);
    }

    // =========================================================================
    // sugerirClasificacion
    // =========================================================================

    public function test_sugerir_clasificacion_segun_nivel_de_riesgo_de_los_activos_vinculados(): void
    {
        $auditor = $this->crearAdmin();
        $categoria = Categoria::create(['nombre_categoria' => 'Redes', 'vida_util_meses' => 48, 'nivel_dependencia' => 'Media']);
        $ubicacion = Ubicacion::create(['seccion' => 'CPD', 'aula' => null]);
        $activoCritico = $this->crearActivo($categoria, $ubicacion, ['nivel_riesgo' => 'Crítico']);
        $activoBajo = $this->crearActivo($categoria, $ubicacion, ['nivel_riesgo' => 'Bajo']);

        $auditoria = $this->crearAuditoria($auditor);

        $hallazgoConCritico = HallazgoAuditoria::create([
            'fk_id_auditoria' => $auditoria->id, 'codigo' => 'H-TEST-03', 'apartado' => '7.1',
            'condicion' => 'x', 'criterio' => 'x', 'causa' => 'x', 'efecto' => 'x', 'evidencia' => 'x', 'recomendacion' => 'x',
            'clasificacion' => 'OBSERVACION', 'responsable' => 'TI',
        ]);
        $hallazgoConCritico->activos()->attach($activoCritico->id_activo);

        $hallazgoConBajo = HallazgoAuditoria::create([
            'fk_id_auditoria' => $auditoria->id, 'codigo' => 'H-TEST-04', 'apartado' => '7.1',
            'condicion' => 'x', 'criterio' => 'x', 'causa' => 'x', 'efecto' => 'x', 'evidencia' => 'x', 'recomendacion' => 'x',
            'clasificacion' => 'OBSERVACION', 'responsable' => 'TI',
        ]);
        $hallazgoConBajo->activos()->attach($activoBajo->id_activo);

        $servicio = app(AuditoriaInternaService::class);

        $this->assertSame('NC_MAYOR', $servicio->sugerirClasificacion($hallazgoConCritico));
        $this->assertNull($servicio->sugerirClasificacion($hallazgoConBajo));
    }

    // =========================================================================
    // aprobar
    // =========================================================================

    public function test_aprobar_falla_si_faltan_objetivo_alcance_o_conclusiones(): void
    {
        $auditor = $this->crearAdmin();
        $auditoria = $this->crearAuditoria($auditor);

        $this->expectException(\RuntimeException::class);
        app(AuditoriaInternaService::class)->aprobar($auditoria);
    }

    public function test_aprobar_falla_si_la_muestra_no_esta_verificada_al_100_por_ciento(): void
    {
        $auditor = $this->crearAdmin();
        $categoria = Categoria::create(['nombre_categoria' => 'Impresoras', 'vida_util_meses' => 36, 'nivel_dependencia' => 'Baja']);
        $ubicacion = Ubicacion::create(['seccion' => 'Sec', 'aula' => null]);
        $this->crearActivo($categoria, $ubicacion, ['nivel_riesgo' => 'Bajo']);

        $auditoria = $this->crearAuditoria($auditor, [
            'objetivo' => 'x', 'alcance' => 'x', 'conclusiones' => 'x',
        ]);
        $servicio = app(AuditoriaInternaService::class);
        $servicio->generarMuestra($auditoria);

        $this->expectException(\RuntimeException::class);
        $servicio->aprobar($auditoria->fresh());
    }

    public function test_aprobar_falla_si_falta_asignar_responsable_de_ti_o_direccion(): void
    {
        $auditor = $this->crearAdmin();
        $auditoria = $this->crearAuditoria($auditor, [
            'objetivo' => 'x', 'alcance' => 'x', 'conclusiones' => 'x',
            'fk_id_responsable_ti' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        app(AuditoriaInternaService::class)->aprobar($auditoria);
    }

    public function test_aprobar_falla_si_un_hallazgo_no_tiene_responsable_o_fecha_de_compromiso(): void
    {
        $auditor = $this->crearAdmin();
        $auditoria = $this->crearAuditoria($auditor, [
            'objetivo' => 'x', 'alcance' => 'x', 'conclusiones' => 'x',
        ]);

        HallazgoAuditoria::create([
            'fk_id_auditoria' => $auditoria->id, 'codigo' => 'H-TEST-05', 'apartado' => '7.1',
            'condicion' => 'x', 'criterio' => 'x', 'causa' => 'x', 'efecto' => 'x', 'evidencia' => 'x', 'recomendacion' => 'x',
            'clasificacion' => 'OBSERVACION', 'responsable' => 'TI', 'fecha_compromiso' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        app(AuditoriaInternaService::class)->aprobar($auditoria->fresh());
    }

    public function test_aprobar_congela_snapshot_y_el_informe_no_cambia_aunque_los_datos_cambien_despues(): void
    {
        Config::set('auditoria.muestra.minimo', 1);

        $auditor = $this->crearAdmin();
        $categoria = Categoria::create(['nombre_categoria' => 'Impresoras', 'vida_util_meses' => 36, 'nivel_dependencia' => 'Baja']);
        $ubicacion = Ubicacion::create(['seccion' => 'Sec', 'aula' => null]);
        $activo = $this->crearActivo($categoria, $ubicacion, ['nivel_riesgo' => 'Bajo']);

        $auditoria = $this->crearAuditoria($auditor, [
            'objetivo' => 'x', 'alcance' => 'x', 'conclusiones' => 'x',
        ]);

        $servicio = app(AuditoriaInternaService::class);
        $servicio->generarMuestra($auditoria);
        $auditoria->muestra()->update(['encontrado' => true, 'ubicacion_coincide' => true, 'estado_coincide' => true]);

        $auditoria = $servicio->aprobar($auditoria->fresh());

        $this->assertSame('APROBADO', $auditoria->estado);
        $this->assertNotNull($auditoria->fecha_aprobacion);
        $this->assertArrayHasKey('indicadores', $auditoria->snapshot_indicadores);
        $this->assertArrayHasKey('opinion', $auditoria->snapshot_indicadores);
        $snapshotOriginal = $auditoria->snapshot_indicadores;

        // Cambia el sistema después de aprobar: se crea un administrador nuevo,
        // lo que alteraría el indicador admins_sistema si se recalculara en vivo.
        $categoria = Categoria::create(['nombre_categoria' => 'Cambio Posterior', 'vida_util_meses' => 12, 'nivel_dependencia' => 'Alta']);
        Usuario::create([
            'fk_id_rol' => (int) config('roles.admin'),
            'nombre' => 'Otro', 'apellido' => 'Admin', 'correo' => 'otro.admin@example.com',
            'contrasena' => bcrypt('secret'), 'estado' => 1,
        ]);

        $indicadoresEnVivoAhora = $servicio->calcularIndicadores($auditoria->fresh());

        // El indicador en vivo cambió (2 admins ahora), pero el snapshot guardado no.
        $this->assertNotEquals($snapshotOriginal['indicadores']['admins_sistema']['valor'], $indicadoresEnVivoAhora['admins_sistema']['valor']);
        $this->assertEquals($snapshotOriginal, $auditoria->fresh()->snapshot_indicadores);
    }

    // =========================================================================
    // Generación de texto por PLN
    // =========================================================================

    public function test_generar_textos_automaticos_produce_los_7_campos_no_vacios(): void
    {
        $auditor = $this->crearAdmin();
        $auditoria = $this->crearAuditoria($auditor);

        $textos = app(AuditoriaInternaService::class)->generarTextosAutomaticos($auditoria);

        foreach (['objetivo', 'alcance', 'antecedentes', 'metodologia', 'limitaciones', 'buenas_practicas', 'conclusiones'] as $campo) {
            $this->assertArrayHasKey($campo, $textos);
            $this->assertNotEmpty($textos[$campo]);
        }
    }

    public function test_texto_de_antecedentes_refleja_si_existe_o_no_una_auditoria_anterior(): void
    {
        $auditor = $this->crearAdmin();
        $servicio = app(AuditoriaInternaService::class);

        $sinAnterior = $this->crearAuditoria($auditor);
        $this->assertStringContainsString('primera auditoría', $servicio->generarTextoAntecedentes($sinAnterior));

        $anterior = $this->crearAuditoria($auditor, ['estado' => 'APROBADO', 'opinion_calculada' => 'SATISFACTORIO', 'fecha_aprobacion' => now()]);
        $conAnterior = $this->crearAuditoria($auditor, ['fk_id_auditoria_anterior' => $anterior->id]);
        $this->assertStringContainsString($anterior->codigo, $servicio->generarTextoAntecedentes($conAnterior));
    }

    public function test_texto_de_conclusiones_cambia_segun_los_hallazgos_registrados(): void
    {
        $auditor = $this->crearAdmin();
        $auditoria = $this->crearAuditoria($auditor);
        $servicio = app(AuditoriaInternaService::class);

        $sinHallazgos = $servicio->generarTextoConclusiones($auditoria);
        $this->assertStringContainsString('No se registran hallazgos', $sinHallazgos);

        HallazgoAuditoria::create([
            'fk_id_auditoria' => $auditoria->id, 'codigo' => 'H-PLN-01', 'apartado' => '7.5',
            'condicion' => 'x', 'criterio' => 'x', 'causa' => 'x', 'efecto' => 'x', 'evidencia' => 'x', 'recomendacion' => 'x',
            'clasificacion' => 'NC_MAYOR', 'responsable' => 'TI',
        ]);

        $conHallazgo = $servicio->generarTextoConclusiones($auditoria->fresh());
        $this->assertStringContainsString('1 No Conformidad Mayor', $conHallazgo);
        $this->assertStringContainsString('INSATISFACTORIO', $conHallazgo);
    }
}
