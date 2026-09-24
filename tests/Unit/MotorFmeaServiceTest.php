<?php

namespace Tests\Unit;

use App\Models\ActivoTecnologico;
use App\Models\Alerta;
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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MotorFmeaServiceTest extends TestCase
{
    use RefreshDatabase;

    private MotorFmeaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MotorFmeaService();
    }

    #[DataProvider('nivelesProvider')]
    public function test_obtiene_el_nivel_y_alerta_correctos_segun_el_score(float $score, string $nivelEsperado, string $alertaEsperada): void
    {
        $resultado = $this->service->obtenerNivelYAlerta($score);

        $this->assertSame($nivelEsperado, $resultado['nivel']);
        $this->assertSame($alertaEsperada, $resultado['tipo_alerta']);
    }

    public static function nivelesProvider(): array
    {
        return [
            'limite inferior bajo' => [0.0, 'Bajo', 'Verde'],
            'justo antes del umbral medio' => [2.99, 'Bajo', 'Verde'],
            'umbral medio exacto' => [3.0, 'Medio', 'Amarilla'],
            'justo antes del umbral alto' => [5.99, 'Medio', 'Amarilla'],
            'umbral alto exacto' => [6.0, 'Alto', 'Naranja'],
            'justo antes del umbral critico' => [7.99, 'Alto', 'Naranja'],
            'umbral critico exacto' => [8.0, 'Crítico', 'Roja'],
            'score maximo' => [10.0, 'Crítico', 'Roja'],
        ];
    }

    public function test_calcula_antiguedad_en_meses_a_partir_de_un_anio(): void
    {
        // El motor interpreta un año como el 1 de enero de ese año
        $anio = (int) now()->format('Y') - 2;
        $esperado = (int) \Carbon\Carbon::createFromDate($anio, 1, 1)->diffInMonths(now());

        $meses = $this->service->calcularAntiguedadMeses((string) $anio);

        $this->assertSame($esperado, $meses);
    }

    public function test_calcula_antiguedad_en_meses_a_partir_de_una_fecha_completa(): void
    {
        $fecha = now()->subMonths(10)->format('Y-m-d');

        $meses = $this->service->calcularAntiguedadMeses($fecha);

        $this->assertSame(10, $meses);
    }

    public function test_antiguedad_por_defecto_es_doce_meses_si_no_hay_fecha(): void
    {
        $this->assertSame(12, $this->service->calcularAntiguedadMeses(null));
    }

    public function test_antiguedad_por_defecto_es_doce_meses_si_la_fecha_es_invalida(): void
    {
        $this->assertSame(12, $this->service->calcularAntiguedadMeses('fecha-no-valida'));
    }

    public function test_antiguedad_minima_es_un_mes(): void
    {
        $hoy = now()->format('Y-m-d');

        $this->assertSame(1, $this->service->calcularAntiguedadMeses($hoy));
    }

    public function test_evaluar_activo_calcula_score_y_clasifica_riesgo_critico(): void
    {
        PesoAhp::create([
            'peso_severidad' => 0.648,
            'peso_frecuencia' => 0.230,
            'peso_msr' => 0.122,
            'fecha_actualizacion' => now(),
        ]);

        $categoria = Categoria::create([
            'nombre_categoria' => 'Computadoras',
            'vida_util_meses' => 12,
            'nivel_dependencia' => 'Alta',
        ]);

        $modo = ModoFalla::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'nombre_modo_falla' => 'Falla de disco duro',
            'valor_severidad_base' => 9,
            'valor_msr_base' => 8,
        ]);

        $ubicacion = Ubicacion::create([
            'seccion' => 'Laboratorio',
            'aula' => 'A-1',
        ]);

        $activo = ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'PAT-001',
            'nombre_equipo' => 'PC Laboratorio 1',
            // Adquirido hace 24 meses frente a una vida útil de 12: 100% de desgaste
            'fecha_adquisicion' => now()->subMonths(24)->format('Y-m-d'),
        ]);

        $rol = Rol::create(['nombre_rol' => 'Soporte']);
        $usuario = Usuario::create([
            'fk_id_rol' => $rol->id_rol,
            'nombre' => 'Tester',
            'apellido' => 'QA',
            'correo' => 'tester@example.com',
            'contrasena' => 'secret',
            'estado' => 1,
        ]);

        // 3 incidentes con texto que concuerda con el modo de falla -> Frecuencia alta (9)
        Incidente::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_reporta' => $usuario->id_usuario,
            'fecha_incidente' => now(),
            'descripcion_falla' => 'Se detectó una falla de disco duro grave',
            'estado_incidente' => 'Reportado',
        ]);
        Incidente::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_reporta' => $usuario->id_usuario,
            'fecha_incidente' => now(),
            'descripcion_falla' => 'Otra falla de disco duro',
            'estado_incidente' => 'Reportado',
        ]);
        Incidente::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_reporta' => $usuario->id_usuario,
            'fecha_incidente' => now(),
            'descripcion_falla' => 'Tercera falla de disco duro',
            'estado_incidente' => 'Reportado',
        ]);

        $resultado = $this->service->evaluarActivo($activo->id_activo);

        // S=9, F=9 (desgaste 100% >=80 e incidentes >2), MSR=8, D=9 (Alta)
        // SubscoreTecnico = 9*0.648 + 9*0.230 + 8*0.122 = 8.878
        // Score_final = 0.75*8.878 + 0.25*9 = 8.9085 -> 8.91
        $this->assertEquals(100, $resultado['porcentaje_desgaste']);
        $this->assertFalse($resultado['sin_modos_falla']);
        $this->assertSame(8.91, $resultado['max_score']);
        $this->assertSame('Crítico', $resultado['criticidad_global']);
        $this->assertSame('Roja', $resultado['alerta_global']);

        $this->assertSame('Crítico', $activo->fresh()->criticidad_institucional);

        $this->assertDatabaseHas('evaluacion_riesgo', [
            'fk_id_activo' => $activo->id_activo,
            'fk_id_modo_falla' => $modo->id_modo_falla,
            'nivel_riesgo' => 'Crítico',
            'vigente' => true,
        ]);

        $this->assertDatabaseHas('alertas', [
            'tipo_alerta' => 'Roja',
        ]);
    }

    /**
     * Crea categoría (con dependencia Alta), modo de falla, ubicación y activo
     * listos para evaluar, más los pesos AHP por defecto vigentes.
     *
     * @return array{0: ActivoTecnologico, 1: ModoFalla, 2: Categoria}
     */
    private function crearEscenarioBasico(): array
    {
        PesoAhp::create([
            'peso_severidad' => 0.648,
            'peso_frecuencia' => 0.230,
            'peso_msr' => 0.122,
            'fecha_actualizacion' => now(),
        ]);

        $categoria = Categoria::create([
            'nombre_categoria' => 'Proyectores',
            'vida_util_meses' => 60,
            'nivel_dependencia' => 'Media',
        ]);

        $modo = ModoFalla::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'nombre_modo_falla' => 'Falla de lámpara',
            'valor_severidad_base' => 5,
            'valor_msr_base' => 6,
        ]);

        $ubicacion = Ubicacion::create([
            'seccion' => 'Aula',
            'aula' => 'B-2',
        ]);

        $activo = ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'PAT-100',
            'nombre_equipo' => 'Proyector Aula B-2',
            'fecha_adquisicion' => now()->subMonths(6)->format('Y-m-d'),
        ]);

        return [$activo, $modo, $categoria];
    }

    public function test_dos_evaluaciones_con_el_mismo_nivel_de_riesgo_generan_una_sola_alerta(): void
    {
        [$activo] = $this->crearEscenarioBasico();

        $this->service->evaluarActivo($activo->id_activo);
        $this->service->evaluarActivo($activo->id_activo);

        $this->assertSame(2, EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)->count());
        $this->assertSame(1, Alerta::count());
    }

    public function test_activo_sin_modos_falla_no_genera_evaluaciones_ni_alertas(): void
    {
        $categoria = Categoria::create([
            'nombre_categoria' => 'Tablets',
            'vida_util_meses' => 36,
            'nivel_dependencia' => 'Media',
        ]);

        $ubicacion = Ubicacion::create([
            'seccion' => 'Biblioteca',
            'aula' => 'C-1',
        ]);

        $activo = ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'PAT-200',
            'nombre_equipo' => 'Tablet Biblioteca 1',
            'fecha_adquisicion' => now()->subMonths(3)->format('Y-m-d'),
        ]);

        $resultado = $this->service->evaluarActivo($activo->id_activo);

        $this->assertTrue($resultado['sin_modos_falla']);
        $this->assertSame([], $resultado['evaluaciones']);
        $this->assertSame(0, EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)->count());
        $this->assertSame(0, Alerta::count());
    }

    public function test_solo_la_evaluacion_mas_reciente_queda_vigente(): void
    {
        [$activo, $modo] = $this->crearEscenarioBasico();

        $this->service->evaluarActivo($activo->id_activo);
        $primera = EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
            ->where('fk_id_modo_falla', $modo->id_modo_falla)
            ->firstOrFail();

        $this->service->evaluarActivo($activo->id_activo);
        $segunda = EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
            ->where('fk_id_modo_falla', $modo->id_modo_falla)
            ->orderByDesc('id_evaluacion')
            ->firstOrFail();

        $this->assertNotSame($primera->id_evaluacion, $segunda->id_evaluacion);
        $this->assertFalse($primera->fresh()->vigente);
        $this->assertTrue($segunda->fresh()->vigente);
        $this->assertSame(
            1,
            EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
                ->where('fk_id_modo_falla', $modo->id_modo_falla)
                ->where('vigente', true)
                ->count()
        );
    }

    /**
     * Escenario que produce nivel de riesgo 'Crítico' sin depender de
     * incidentes: S=9, F=6 (desgaste 100%), MSR=8, D=9 (Alta) -> Score 8.39.
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
            'codigo_patrimonial' => 'SRV-100',
            'nombre_equipo' => 'Servidor Crítico',
            'fecha_adquisicion' => now()->subMonths(24)->format('Y-m-d'),
        ]);

        return [$activo, $modo];
    }

    public function test_evaluar_activo_alto_o_critico_crea_plan_tratamiento_automatico(): void
    {
        [$activo] = $this->crearEscenarioCritico();

        $resultado = $this->service->evaluarActivo($activo->id_activo);

        $this->assertSame('Crítico', $resultado['evaluaciones'][0]['nivel']);
        $this->assertSame(1, PlanTratamientoRiesgo::count());

        $plan = PlanTratamientoRiesgo::firstOrFail();
        $sugerencia = MotorFmeaService::MAPA_TRATAMIENTO_SUGERIDO['Crítico'];

        $this->assertSame($sugerencia['estrategia'], $plan->estrategia);
        $this->assertSame($sugerencia['accion_preventiva'], $plan->accion_preventiva);
        $this->assertSame('Pendiente', $plan->estado_tratamiento);
        $this->assertSame('automatico', $plan->origen);
        $this->assertSame(
            now()->addDays($sugerencia['dias_plazo'])->toDateString(),
            \Carbon\Carbon::parse($plan->fecha_compromiso)->toDateString()
        );
    }

    public function test_evaluar_activo_dos_veces_no_duplica_plan_tratamiento_activo(): void
    {
        [$activo] = $this->crearEscenarioCritico();

        $this->service->evaluarActivo($activo->id_activo);
        $this->service->evaluarActivo($activo->id_activo);

        $this->assertSame(2, EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)->count());
        $this->assertSame(1, PlanTratamientoRiesgo::count());
    }

    public function test_desgaste_se_calcula_desde_ultimo_mantenimiento_finalizado(): void
    {
        [$activo, $modo] = $this->crearEscenarioCritico();

        // origen_mantenimiento ya trae 'Preventivo' (id 1) sembrado por su propia migración.
        $rol = Rol::create(['nombre_rol' => 'Soporte']);
        $usuario = Usuario::create([
            'fk_id_rol' => $rol->id_rol,
            'nombre' => 'Tester',
            'apellido' => 'QA',
            'correo' => 'tester2@example.com',
            'contrasena' => 'secret',
            'estado' => 1,
        ]);

        // El activo se adquirió hace 24 meses (100% desgaste sin mantenimiento),
        // pero se le hizo un mantenimiento finalizado hace apenas 1 mes.
        Mantenimiento::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_asignado' => $usuario->id_usuario,
            'fk_id_origen' => 1,
            'tipo_mantenimiento' => 'Preventivo',
            'fecha_programada' => now()->subMonth()->format('Y-m-d'),
            'fecha_ejecucion' => now()->subMonth()->format('Y-m-d'),
            'estado_mantenimiento' => 'Finalizado',
        ]);

        $resultado = $this->service->evaluarActivo($activo->id_activo);

        // Desgaste desde el mantenimiento (1/12 meses ~8%), no desde adquisición (24/12 = 100%)
        $this->assertLessThanOrEqual(2, $resultado['antiguedad_meses']);
        $this->assertLessThan(40, $resultado['porcentaje_desgaste']);
        // Sin incidentes tras el mantenimiento y desgaste bajo -> Frecuencia Baja
        $this->assertSame(2, $resultado['evaluaciones'][0]['frecuencia']);
    }

    private function crearUsuarioAutenticado(string $correo = 'mitigar@example.com'): Usuario
    {
        $rol = Rol::create(['nombre_rol' => 'Soporte ' . $correo]);
        $usuario = Usuario::create([
            'fk_id_rol' => $rol->id_rol,
            'nombre' => 'Tester',
            'apellido' => 'Mitigar',
            'correo' => $correo,
            'contrasena' => 'secret',
            'estado' => 1,
        ]);

        $this->actingAs($usuario);

        return $usuario;
    }

    public function test_tratamiento_mitigar_automatico_programa_mantenimiento_preventivo(): void
    {
        [$activo] = $this->crearEscenarioCritico();
        $usuario = $this->crearUsuarioAutenticado();

        $this->service->evaluarActivo($activo->id_activo);

        $tratamiento = PlanTratamientoRiesgo::firstOrFail();
        $this->assertSame('Mitigar', $tratamiento->estrategia);

        $mantenimiento = Mantenimiento::where('id_evaluacion_origen', $tratamiento->fk_id_evaluacion)->first();
        $this->assertNotNull($mantenimiento);
        $this->assertSame($activo->id_activo, $mantenimiento->fk_id_activo);
        $this->assertSame($usuario->id_usuario, $mantenimiento->fk_id_usuario_asignado);
        $this->assertSame('Programado', $mantenimiento->estado_mantenimiento);
        $this->assertSame($tratamiento->accion_preventiva, $mantenimiento->descripcion_trabajo);
        // Sin incidentes reportados para el activo -> Preventivo, no Correctivo.
        $this->assertSame('Preventivo', $mantenimiento->tipo_mantenimiento);
    }

    public function test_tratamiento_mitigar_automatico_programa_mantenimiento_correctivo_con_incidente_abierto(): void
    {
        [$activo, $modo] = $this->crearEscenarioCritico();
        $usuario = $this->crearUsuarioAutenticado('mitigar-correctivo@example.com');

        Incidente::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_usuario_reporta' => $usuario->id_usuario,
            'fecha_incidente' => now(),
            'descripcion_falla' => 'El servidor se apaga solo.',
            'estado_incidente' => 'Reportado',
        ]);

        $this->service->evaluarActivo($activo->id_activo);

        $tratamiento = PlanTratamientoRiesgo::firstOrFail();
        $mantenimiento = Mantenimiento::where('id_evaluacion_origen', $tratamiento->fk_id_evaluacion)->firstOrFail();

        $this->assertSame('Correctivo', $mantenimiento->tipo_mantenimiento);
    }

    public function test_crear_mantenimiento_si_mitiga_no_duplica_si_ya_hay_uno_activo(): void
    {
        [$activo] = $this->crearEscenarioCritico();
        $this->crearUsuarioAutenticado('mitigar-idempotente@example.com');

        $this->service->evaluarActivo($activo->id_activo);
        $tratamiento = PlanTratamientoRiesgo::with('evaluacion')->firstOrFail();

        $this->assertSame(1, Mantenimiento::count());

        // Llamada directa repetida sobre el mismo tratamiento: no debe duplicar.
        $this->service->crearMantenimientoSiMitiga($tratamiento);

        $this->assertSame(1, Mantenimiento::count());
    }

    /**
     * Escenario con varios modos de falla en la misma categoría, para probar
     * la selección granular.
     *
     * @return array{0: ActivoTecnologico, 1: \Illuminate\Support\Collection}
     */
    private function crearEscenarioConCincoModos(): array
    {
        PesoAhp::create([
            'peso_severidad' => 0.648,
            'peso_frecuencia' => 0.230,
            'peso_msr' => 0.122,
            'fecha_actualizacion' => now(),
        ]);

        $categoria = Categoria::create([
            'nombre_categoria' => 'Multimodo',
            'vida_util_meses' => 60,
            'nivel_dependencia' => 'Media',
        ]);

        $modos = collect(range(1, 5))->map(function ($i) use ($categoria) {
            return ModoFalla::create([
                'fk_id_categoria' => $categoria->id_categoria,
                'nombre_modo_falla' => "Modo {$i}",
                'valor_severidad_base' => 5,
                'valor_msr_base' => 6,
            ]);
        });

        $ubicacion = Ubicacion::create(['seccion' => 'Lab', 'aula' => 'M-1']);

        $activo = ActivoTecnologico::create([
            'fk_id_categoria' => $categoria->id_categoria,
            'fk_id_ubicacion' => $ubicacion->id_ubicacion,
            'codigo_patrimonial' => 'MUL-001',
            'nombre_equipo' => 'Equipo Multimodo',
            'fecha_adquisicion' => now()->subMonths(6)->format('Y-m-d'),
        ]);

        return [$activo, $modos];
    }

    public function test_evaluar_todos_los_modos_genera_una_evaluacion_vigente_por_modo(): void
    {
        [$activo, $modos] = $this->crearEscenarioConCincoModos();

        $resultado = $this->service->evaluarActivo($activo->id_activo);

        $this->assertCount(5, $resultado['evaluaciones']);
        $this->assertFalse($resultado['evaluacion_parcial']);
        $this->assertSame(0, $resultado['modos_conservados']);

        $this->assertSame(5, EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)->count());
        $this->assertSame(5, EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)->where('vigente', true)->count());

        foreach ($modos as $modo) {
            $this->assertDatabaseHas('evaluacion_riesgo', [
                'fk_id_activo' => $activo->id_activo,
                'fk_id_modo_falla' => $modo->id_modo_falla,
                'vigente' => true,
            ]);
        }
    }

    public function test_evaluar_un_solo_modo_deja_intactas_las_evaluaciones_de_los_demas(): void
    {
        [$activo, $modos] = $this->crearEscenarioConCincoModos();

        // Punto de partida: los 5 modos ya tienen evaluación vigente.
        $this->service->evaluarActivo($activo->id_activo);

        $vigentesAntes = EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
            ->where('vigente', true)
            ->pluck('id_evaluacion', 'fk_id_modo_falla');

        $modoElegido = $modos[1];

        $resultado = $this->service->evaluarActivo($activo->id_activo, [$modoElegido->id_modo_falla]);

        $this->assertCount(1, $resultado['evaluaciones']);
        $this->assertTrue($resultado['evaluacion_parcial']);
        $this->assertSame(5, $resultado['modos_categoria']);
        $this->assertSame(4, $resultado['modos_conservados']);

        // Sólo el par evaluado generó un registro nuevo: 5 + 1 = 6.
        $this->assertSame(6, EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)->count());
        $this->assertSame(5, EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)->where('vigente', true)->count());

        foreach ($modos as $modo) {
            $vigente = EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
                ->where('fk_id_modo_falla', $modo->id_modo_falla)
                ->where('vigente', true)
                ->firstOrFail();

            if ($modo->id_modo_falla === $modoElegido->id_modo_falla) {
                // El evaluado sí cambió de registro vigente.
                $this->assertNotSame($vigentesAntes[$modo->id_modo_falla], $vigente->id_evaluacion);
            } else {
                // Los no seleccionados conservan exactamente el mismo registro.
                $this->assertSame($vigentesAntes[$modo->id_modo_falla], $vigente->id_evaluacion);
                $this->assertSame(
                    1,
                    EvaluacionRiesgo::where('fk_id_activo', $activo->id_activo)
                        ->where('fk_id_modo_falla', $modo->id_modo_falla)
                        ->count(),
                    'No debe crearse un registro nuevo para un modo no seleccionado.'
                );
            }
        }
    }

    public function test_evaluacion_parcial_no_genera_alertas_de_los_modos_no_seleccionados(): void
    {
        [$activo, $modos] = $this->crearEscenarioConCincoModos();

        $this->service->evaluarActivo($activo->id_activo);
        $alertasAntes = Alerta::count();

        // Reevaluar un solo modo: su nivel no cambia, así que por idempotencia
        // no debe generarse ninguna alerta nueva, ni suya ni de los otros.
        $this->service->evaluarActivo($activo->id_activo, [$modos[0]->id_modo_falla]);

        $this->assertSame($alertasAntes, Alerta::count());
    }

    public function test_tratamiento_transferir_no_programa_mantenimiento(): void
    {
        [$activo, $modo] = $this->crearEscenarioCritico();
        $this->crearUsuarioAutenticado('transferir@example.com');

        $evaluacion = EvaluacionRiesgo::create([
            'fk_id_activo' => $activo->id_activo,
            'fk_id_modo_falla' => $modo->id_modo_falla,
            'valor_severidad' => 9, 'valor_frecuencia' => 6, 'valor_msr' => 8,
            'score_calculado' => 8.39, 'nivel_riesgo' => 'Crítico', 'vigente' => true, 'fecha_evaluacion' => now(),
        ]);

        $tratamiento = PlanTratamientoRiesgo::create([
            'fk_id_evaluacion' => $evaluacion->id_evaluacion,
            'estrategia' => 'Transferir',
            'accion_preventiva' => 'Contratar soporte externo.',
            'estado_tratamiento' => 'Pendiente',
            'origen' => 'manual',
        ]);

        $this->service->crearMantenimientoSiMitiga($tratamiento);

        $this->assertSame(0, Mantenimiento::count());
    }
}
