<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivoTecnologico;
use App\Models\Alerta;
use App\Models\Categoria;
use App\Models\EvaluacionRiesgo;
use App\Models\ModoFalla;
use App\Models\PesoAhp;
use App\Models\PlanTratamientoRiesgo;
use App\Services\MotorFmeaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProcesoRiesgoController extends Controller
{
    /**
     * Texto descriptivo (Tabla 20 de la tesis) para cada nivel de Severidad.
     */
    private const OPCIONES_SEVERIDAD = [
        'muy_alta' => 'Muy Alta (10) — Pérdida total de datos académicos críticos / incumplimiento legal grave / interrupción total de actividades >24h',
        'alta' => 'Alta (8) — Degradación severa de servicios / afectación a integridad de información de auditoría',
        'moderada' => 'Moderada (5) — Activo funciona con limitaciones / impacto en productividad administrativa / interrupción parcial de una clase',
        'baja' => 'Baja (3) — Inconveniente menor, proceso alterno manual',
        'insignificante' => 'Insignificante (1) — No existe impacto perceptible',
    ];

    /**
     * Texto descriptivo (Tabla 22 de la tesis) para cada nivel de MSR.
     */
    private const OPCIONES_MSR = [
        'muy_baja' => 'Muy Baja (9) — No existen alertas, se detecta solo cuando el servicio colapsa totalmente',
        'moderada' => 'Moderada (6) — Registra logs pero requiere revisión manual',
        'alta' => 'Alta (4) — Alertas automáticas antes del colapso',
        'muy_alta' => 'Muy Alta (2) — Mitigación autónoma o aviso instantáneo',
    ];

    public function modosIndex(Request $request)
    {
        $query = ModoFalla::with('categoria');

        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre_modo_falla', 'like', "%{$buscar}%")
                  ->orWhereHas('categoria', fn($c) => $c->where('nombre_categoria', 'like', "%{$buscar}%"));
            });
        }

        $modos = $query->orderBy('id_modo_falla')->get();
        $categorias = Categoria::orderBy('nombre_categoria')->get();
        $opcionesSeveridad = self::OPCIONES_SEVERIDAD;
        $opcionesMsr = self::OPCIONES_MSR;

        return view('riesgos.modos-falla', compact('modos', 'categorias', 'opcionesSeveridad', 'opcionesMsr'));
    }

    public function modosStore(Request $request)
    {
        $data = $request->validate([
            'fk_id_categoria' => 'required|integer|exists:categorias,id_categoria',
            'nombre_modo_falla' => 'required|string|max:255',
            'nivel_severidad' => 'required|in:muy_alta,alta,moderada,baja,insignificante',
            'nivel_msr' => 'required|in:muy_baja,moderada,alta,muy_alta',
            'justificacion_severidad' => 'nullable|string',
            'justificacion_msr' => 'nullable|string',
        ]);

        $data['valor_severidad_base'] = MotorFmeaService::MAPA_SEVERIDAD[$data['nivel_severidad']];
        $data['valor_msr_base'] = MotorFmeaService::MAPA_MSR[$data['nivel_msr']];

        ModoFalla::create($data);

        return redirect()->route('admin.riesgos.modos')->with('success', 'Modo de falla creado con éxito');
    }

    public function modosUpdate(Request $request, $id)
    {
        $modo = ModoFalla::findOrFail($id);

        $data = $request->validate([
            'fk_id_categoria' => 'required|integer|exists:categorias,id_categoria',
            'nombre_modo_falla' => 'required|string|max:255',
            'nivel_severidad' => 'required|in:muy_alta,alta,moderada,baja,insignificante',
            'nivel_msr' => 'required|in:muy_baja,moderada,alta,muy_alta',
            'justificacion_severidad' => 'nullable|string',
            'justificacion_msr' => 'nullable|string',
        ]);

        $data['valor_severidad_base'] = MotorFmeaService::MAPA_SEVERIDAD[$data['nivel_severidad']];
        $data['valor_msr_base'] = MotorFmeaService::MAPA_MSR[$data['nivel_msr']];

        $modo->update($data);

        return redirect()->route('admin.riesgos.modos')->with('success', 'Modo de falla actualizado con éxito');
    }

    public function evaluacionIndex(MotorFmeaService $service)
    {
        $activos = ActivoTecnologico::with(['categoria.modosFalla', 'incidentes', 'mantenimientos'])->orderBy('id_activo')->get();

        // Calcular antiguedad y desgaste de cada activo para mostrar en la interfaz
        // (considera el reseteo por último mantenimiento ejecutado)
        foreach ($activos as $act) {
            $act->antiguedad_meses = $service->calcularAntiguedadMesesActivo($act);
            $vidaUtil = max(1, $act->categoria->vida_util_meses ?? 60);
            $act->porcentaje_desgaste = min(100, round(($act->antiguedad_meses / $vidaUtil) * 100, 1));
            $act->sin_modos_falla = !$act->categoria || $act->categoria->modosFalla->isEmpty();
        }

        $modos = ModoFalla::with('categoria')->orderBy('id_modo_falla')->get();
        $evaluaciones = EvaluacionRiesgo::with(['activo', 'modoFalla'])->where('vigente', true)->orderByDesc('fecha_evaluacion')->paginate(15);
        $ultimoPeso = PesoAhp::where('vigente', true)->first();

        return view('riesgos.evaluacion-fmea', compact('activos', 'modos', 'evaluaciones', 'ultimoPeso'));
    }

    public function evaluarActivo(Request $request, MotorFmeaService $service)
    {
        $request->validate([
            'fk_id_activo' => 'required|integer|exists:activos_tecnologicos,id_activo',
        ]);

        $resultado = $service->evaluarActivo($request->input('fk_id_activo'));

        if ($resultado['sin_modos_falla']) {
            return redirect()->route('admin.riesgos.evaluacion')
                ->with('warning', "El activo '{$resultado['activo']->nombre_equipo}' no tiene modos de falla definidos para su categoría. No se generaron evaluaciones ni alertas.")
                ->with('resultadoMotor', $resultado);
        }

        return redirect()->route('admin.riesgos.evaluacion')
            ->with('success', "Evaluación automatizada FMEA ejecutada con éxito para '{$resultado['activo']->nombre_equipo}'. Max Score: {$resultado['max_score']} (Nivel: {$resultado['criticidad_global']}).")
            ->with('resultadoMotor', $resultado);
    }

    public function evaluarTodos(MotorFmeaService $service)
    {
        $resumen = $service->evaluarTodos();

        return redirect()->route('admin.riesgos.evaluacion')
            ->with('success', "Se han evaluado automáticamente {$resumen['evaluados']} activos tecnológicos del inventario bajo los estándares ISO 31000 / FMEA-MSR.")
            ->with('resumenLote', $resumen);
    }

    public function evaluacionStore(Request $request, MotorFmeaService $service)
    {
        $data = $request->validate([
            'fk_id_activo' => 'required|integer|exists:activos_tecnologicos,id_activo',
            'fk_id_modo_falla' => 'required|integer|exists:modos_falla,id_modo_falla',
            'valor_severidad' => 'required|integer|min:1|max:10',
            'valor_frecuencia' => 'required|integer|min:1|max:10',
            'valor_msr' => 'required|integer|min:1|max:10',
            'fecha_evaluacion' => 'required|date',
        ]);

        $activo = ActivoTecnologico::with('categoria')->findOrFail($data['fk_id_activo']);
        $nivelDependencia = $activo->categoria->nivel_dependencia ?? 'Media';
        $D = MotorFmeaService::MAPA_DEPENDENCIA[$nivelDependencia] ?? 5;

        $score = $service->calcularScore(
            $data['valor_severidad'],
            $data['valor_frecuencia'],
            $data['valor_msr'],
            $D
        );

        $info = $service->obtenerNivelYAlerta($score);

        // Conservar el mismo invariante de "una sola evaluación vigente por
        // (activo, modo_falla)" que usa el motor automatizado.
        EvaluacionRiesgo::where('fk_id_activo', $data['fk_id_activo'])
            ->where('fk_id_modo_falla', $data['fk_id_modo_falla'])
            ->where('vigente', true)
            ->update(['vigente' => false]);

        $data['score_calculado'] = $score;
        $data['nivel_riesgo'] = $info['nivel'];
        $data['vigente'] = true;

        $evaluacion = EvaluacionRiesgo::create($data);

        // Actualizar criticidad del activo según las evaluaciones vigentes
        $maxScoreVigente = EvaluacionRiesgo::where('fk_id_activo', $data['fk_id_activo'])
            ->where('vigente', true)
            ->max('score_calculado');
        $infoGlobal = $service->obtenerNivelYAlerta((float) $maxScoreVigente);
        $activo->update(['criticidad_institucional' => $infoGlobal['criticidad']]);

        Alerta::create([
            'fk_id_evaluacion' => $evaluacion->id_evaluacion,
            'tipo_alerta' => $info['tipo_alerta'],
            'fecha_generacion' => now(),
            'notificacion_enviada' => true,
        ]);

        return redirect()->route('admin.riesgos.evaluacion')->with('success', 'Evaluación manual registrada con éxito');
    }

    public function pesosIndex()
    {
        $pesos = PesoAhp::orderByDesc('id_config')->get();

        return view('riesgos.score', compact('pesos'));
    }

    public function riesgoIndex(Request $request)
    {
        $query = EvaluacionRiesgo::with([
            'activo:id_activo,nombre_equipo,codigo_patrimonial',
            'modoFalla:id_modo_falla,nombre_modo_falla'
        ])->where('vigente', true);

        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nivel_riesgo', 'like', "%{$buscar}%")
                  ->orWhere('score_calculado', 'like', "%{$buscar}%")
                  ->orWhereHas('activo', fn($a) => $a->where('nombre_equipo', 'like', "%{$buscar}%")->orWhere('codigo_patrimonial', 'like', "%{$buscar}%"))
                  ->orWhereHas('modoFalla', fn($m) => $m->where('nombre_modo_falla', 'like', "%{$buscar}%"));
            });
        }

        $evaluaciones = $query->orderByDesc('fecha_evaluacion')->paginate(15)->withQueryString();
        $totalEvaluaciones = EvaluacionRiesgo::where('vigente', true)->count();
        $totalAlertas = Alerta::count();
        $totalCritico = EvaluacionRiesgo::where('vigente', true)->where('nivel_riesgo', 'Crítico')->count();
        $totalAlto = EvaluacionRiesgo::where('vigente', true)->where('nivel_riesgo', 'Alto')->count();

        $alertas = Alerta::with('evaluacion.activo:id_activo,nombre_equipo,codigo_patrimonial')
            ->orderByDesc('fecha_generacion')
            ->take(10)
            ->get();

        // Activos cuya categoría no tiene modos de falla definidos: nunca
        // podrán tener una evaluación, se listan explícitamente en vez de
        // omitirse en silencio del historial.
        $activosSinModos = ActivoTecnologico::with('categoria')
            ->whereHas('categoria', fn($c) => $c->whereDoesntHave('modosFalla'))
            ->get();

        return view('riesgos.riesgo', compact('evaluaciones', 'alertas', 'totalEvaluaciones', 'totalAlertas', 'totalCritico', 'totalAlto', 'activosSinModos'));
    }

    public function pesosStore(Request $request)
    {
        $data = $request->validate([
            'peso_severidad' => 'required|numeric|min:0|max:1',
            'peso_frecuencia' => 'required|numeric|min:0|max:1',
            'peso_msr' => 'required|numeric|min:0|max:1',
            'peso_riesgo_tecnico' => 'required|numeric|min:0|max:1',
            'peso_dependencia' => 'required|numeric|min:0|max:1',
            'fecha_actualizacion' => 'required|date',
        ]);

        // Nivel 2: Severidad / Frecuencia / MSR
        $sumaNivel2 = round($data['peso_severidad'] + $data['peso_frecuencia'] + $data['peso_msr'], 3);
        if (abs($sumaNivel2 - 1.0) > 0.05) {
            return redirect()->back()->withErrors([
                'peso_severidad' => 'La suma de Severidad + Frecuencia + MSR debe ser igual a 1.000 (100%). La suma actual ingresada es ' . number_format($sumaNivel2, 3)
            ])->withInput();
        }

        // Nivel 1: Riesgo Técnico / Dependencia
        $sumaNivel1 = round($data['peso_riesgo_tecnico'] + $data['peso_dependencia'], 3);
        if (abs($sumaNivel1 - 1.0) > 0.05) {
            return redirect()->back()->withErrors([
                'peso_riesgo_tecnico' => 'La suma de Riesgo Técnico + Dependencia debe ser igual a 1.000 (100%). La suma actual ingresada es ' . number_format($sumaNivel1, 3)
            ])->withInput();
        }

        $data['fecha_actualizacion'] = \Carbon\Carbon::parse($data['fecha_actualizacion'])->format('Y-m-d H:i:s');
        $data['vigente'] = true;

        PesoAhp::where('vigente', true)->update(['vigente' => false]);
        PesoAhp::create($data);

        return redirect()->route('admin.riesgos.score')->with('success', 'Pesos AHP actualizados con éxito');
    }

    /**
     * Vocabulario canónico de estrategia de tratamiento (ISO 31000, marco
     * teórico 2.7.3 de la tesis). No usar los términos de la Tabla 23 ni los
     * del diccionario de datos como valores reales del sistema.
     */
    private const ESTRATEGIAS_TRATAMIENTO = ['Mitigar', 'Transferir', 'Evitar', 'Aceptar'];

    /**
     * Estados únicos de un plan de tratamiento.
     */
    private const ESTADOS_TRATAMIENTO = ['Pendiente', 'En Proceso', 'Completado'];

    public function tratamientoIndex()
    {
        $tratamientos = PlanTratamientoRiesgo::with(['evaluacion.activo:id_activo,nombre_equipo'])->orderByDesc('id_tratamiento')->paginate(15);
        $evaluaciones = EvaluacionRiesgo::with('activo:id_activo,nombre_equipo')
            ->where('vigente', true)
            ->select('id_evaluacion', 'fk_id_activo', 'nivel_riesgo', 'score_calculado', 'fecha_evaluacion')
            ->orderByDesc('fecha_evaluacion')
            ->get();
        $evaluacionesSinTratamiento = $this->evaluacionesSinTratamiento();
        $estrategias = self::ESTRATEGIAS_TRATAMIENTO;
        $estados = self::ESTADOS_TRATAMIENTO;

        return view('riesgos.plan-tratamiento', compact('tratamientos', 'evaluaciones', 'evaluacionesSinTratamiento', 'estrategias', 'estados'));
    }

    public function tratamientoStore(Request $request, MotorFmeaService $service)
    {
        $data = $request->validate([
            'fk_id_evaluacion' => 'required|integer|exists:evaluacion_riesgo,id_evaluacion',
            'estrategia' => 'required|string|in:' . implode(',', self::ESTRATEGIAS_TRATAMIENTO),
            'accion_preventiva' => 'required|string',
            'estado_tratamiento' => 'required|string|in:' . implode(',', self::ESTADOS_TRATAMIENTO),
            'fecha_compromiso' => 'nullable|date',
        ]);

        $data['origen'] = 'manual';

        $tratamiento = PlanTratamientoRiesgo::create($data);

        // Cierre del ciclo: solo "Mitigar" implica intervención física sobre
        // el activo, así que solo esa estrategia programa un Mantenimiento.
        $service->crearMantenimientoSiMitiga($tratamiento);

        return redirect()->route('admin.riesgos.tratamiento')->with('success', 'Plan de tratamiento creado con éxito');
    }

    public function tratamientoUpdate(Request $request, $id)
    {
        $tratamiento = PlanTratamientoRiesgo::findOrFail($id);

        $data = $request->validate([
            'estado_tratamiento' => 'required|string|in:' . implode(',', self::ESTADOS_TRATAMIENTO),
        ]);

        $tratamiento->update($data);

        // El disparador de re-evaluación ya no vive aquí: el hecho físico
        // que cambia el score es la ejecución del Mantenimiento (ver
        // ProcesoActivoController::mantenimientoUpdate()), no el cambio de
        // estado del tratamiento en sí. Un tratamiento "Mitigar" se marca
        // Completado automáticamente cuando su Mantenimiento vinculado
        // finaliza; Transferir/Evitar/Aceptar se cierran aquí sin más efecto.

        return redirect()->route('admin.riesgos.tratamiento')->with('success', 'Plan de tratamiento actualizado con éxito');
    }

    /**
     * Evaluaciones vigentes en Alto/Crítico que no tienen ningún plan de
     * tratamiento asociado — reporte de auditoría para el ciclo de gestión
     * de riesgos ISO 31000. Usa EvaluacionRiesgo::sinTratamiento(), la misma
     * fuente de verdad que consume el módulo de Informes.
     */
    private function evaluacionesSinTratamiento()
    {
        return EvaluacionRiesgo::sinTratamiento()
            ->with('activo:id_activo,nombre_equipo,codigo_patrimonial')
            ->orderByDesc('score_calculado')
            ->get();
    }

    public function alertasIndex(Request $request)
    {
        $query = Alerta::with('evaluacion.activo');

        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('tipo_alerta', 'like', "%{$buscar}%")
                  ->orWhereHas('evaluacion.activo', fn($a) => $a->where('nombre_equipo', 'like', "%{$buscar}%")->orWhere('codigo_patrimonial', 'like', "%{$buscar}%"));
            });
        }

        $alertas = $query->orderByDesc('fecha_generacion')->paginate(15)->withQueryString();
        $evaluaciones = EvaluacionRiesgo::with('activo:id_activo,nombre_equipo,codigo_patrimonial')
            ->where('vigente', true)
            ->select('id_evaluacion', 'fk_id_activo', 'nivel_riesgo', 'score_calculado')
            ->orderByDesc('score_calculado')
            ->get();

        return view('riesgos.alertas', compact('alertas', 'evaluaciones'));
    }

    public function alertasStore(Request $request)
    {
        $data = $request->validate([
            'fk_id_evaluacion' => 'required|integer|exists:evaluacion_riesgo,id_evaluacion',
            'tipo_alerta' => 'required|string|max:255',
            'fecha_generacion' => 'required|date',
            'notificacion_enviada' => 'nullable|boolean',
        ]);

        $data['notificacion_enviada'] = (bool) ($data['notificacion_enviada'] ?? false);

        Alerta::create($data);

        return redirect()->route('admin.riesgos.alertas')->with('success', 'Alerta registrada con éxito');
    }
}
