<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvaluarActivoRequest;
use App\Models\ActivoTecnologico;
use App\Models\Alerta;
use App\Models\Categoria;
use App\Models\EvaluacionRiesgo;
use App\Models\Mantenimiento;
use App\Models\ModoFalla;
use App\Models\PesoAhp;
use App\Models\PlanTratamientoRiesgo;
use App\Services\MotorFmeaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

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
        'baja' => 'Baja (7) — Detección solo por inspección o reporte manual directo (rondas de soporte, reporte del usuario), sin logs ni alertas',
        'moderada' => 'Moderada (6) — Registra logs pero requiere revisión manual',
        'alta' => 'Alta (4) — Alertas automáticas antes del colapso',
        'muy_alta' => 'Muy Alta (2) — Mitigación autónoma o aviso instantáneo',
    ];

    /**
     * Pantalla intermedia que agrupa los accesos de configuración del módulo
     * de Evaluación de Riesgos: catálogo de Modos de Falla y pesos AHP
     * (Score). No implementa lógica propia — solo navega a las pantallas que
     * ya existen; se limita a mostrar un resumen del estado de cada una.
     */
    public function configuracionIndex()
    {
        $totalModos = ModoFalla::count();
        $categoriasSinModos = Categoria::whereDoesntHave('modosFalla')->count();
        $pesosVigentes = PesoAhp::where('vigente', true)->first();

        return view('riesgos.configuracion', compact('totalModos', 'categoriasSinModos', 'pesosVigentes'));
    }

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
            'nivel_msr' => 'required|in:muy_baja,baja,moderada,alta,muy_alta',
            'justificacion_severidad' => 'nullable|string',
            'justificacion_msr' => 'nullable|string',
            'indicador_seguimiento' => 'nullable|string|max:255',
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
            'nivel_msr' => 'required|in:muy_baja,baja,moderada,alta,muy_alta',
            'justificacion_severidad' => 'nullable|string',
            'justificacion_msr' => 'nullable|string',
            'indicador_seguimiento' => 'nullable|string|max:255',
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

    public function evaluarActivo(EvaluarActivoRequest $request, MotorFmeaService $service)
    {
        // idsModosFalla() devuelve null cuando se pidió "todos", que es lo que
        // el motor interpreta como el catálogo completo de la categoría.
        $resultado = $service->evaluarActivo(
            (int) $request->input('fk_id_activo'),
            $request->idsModosFalla()
        );

        if ($resultado['sin_modos_falla']) {
            if ($request->input('retorno') === 'ficha' || $request->has('from_ficha')) {
                return redirect()->to(route('admin.activos.show', $resultado['activo']->id_activo) . '#riesgo')
                    ->with('warning', "El activo '{$resultado['activo']->nombre_equipo}' no tiene modos de falla definidos para su categoría. No se generaron evaluaciones ni alertas.")
                    ->with('resultadoMotor', $resultado);
            }

            return redirect()->route('admin.riesgos.evaluacion')
                ->with('warning', "El activo '{$resultado['activo']->nombre_equipo}' no tiene modos de falla definidos para su categoría. No se generaron evaluaciones ni alertas.")
                ->with('resultadoMotor', $resultado);
        }

        $mensaje = "Evaluación automatizada FMEA ejecutada con éxito para '{$resultado['activo']->nombre_equipo}'. Max Score: {$resultado['max_score']} (Nivel: {$resultado['criticidad_global']}).";

        // En una evaluación parcial se aclara qué pasó con los modos no
        // seleccionados: conservan su evaluación vigente, no quedan pendientes.
        if ($resultado['evaluacion_parcial']) {
            $evaluados = count($resultado['evaluaciones']);
            $restantes = $resultado['modos_conservados'];

            $verbo = $evaluados === 1 ? 'Se evaluó' : 'Se evaluaron';
            $frase = $restantes === 1
                ? 'el restante conserva su última evaluación vigente'
                : "los {$restantes} restantes conservan su última evaluación vigente";

            $mensaje .= " {$verbo} {$evaluados} de {$resultado['modos_categoria']} modos de falla; {$frase}.";
        }

        if ($request->input('retorno') === 'ficha' || $request->has('from_ficha')) {
            return redirect()->to(route('admin.activos.show', $resultado['activo']->id_activo) . '#riesgo')
                ->with('success', $mensaje)
                ->with('resultadoMotor', $resultado);
        }

        return redirect()->route('admin.riesgos.evaluacion')
            ->with('success', $mensaje)
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

        // Actualizar nivel_riesgo del activo según la regla de evaluaciones vigentes:
        // si tiene 4 o más vigentes se promedian; de lo contrario, el último nivel registrado.
        $service->actualizarNivelRiesgoActivo($activo);

        Alerta::create([
            'fk_id_evaluacion' => $evaluacion->id_evaluacion,
            'tipo_alerta' => $info['tipo_alerta'],
            'fecha_generacion' => now(),
            'notificacion_enviada' => true,
        ]);

        if ($request->input('retorno') === 'ficha' || $request->has('from_ficha')) {
            return redirect()->to(route('admin.activos.show', $data['fk_id_activo']) . '#riesgo')
                ->with('success', 'Evaluación de riesgo FMEA registrada con éxito para este activo.');
        }

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

    /**
     * Estrategias que nunca generan un Mantenimiento (ver
     * MotorFmeaService::crearMantenimientoSiMitiga). Al completarlas, la
     * única constancia de "qué se hizo" es 'observacion_cierre': por eso se
     * exige no vacía en ese momento. Para 'Mitigar', "qué se hizo" ya vive en
     * descripcion_trabajo del Mantenimiento vinculado.
     */
    private const ESTRATEGIAS_SIN_MANTENIMIENTO = ['Transferir', 'Evitar', 'Aceptar'];

    public function tratamientoIndex(Request $request)
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
        // Misma fuente que usa el motor automático (MotorFmeaService), para
        // que el formulario manual sugiera lo mismo que sugeriría el motor.
        $mapaSugerido = MotorFmeaService::MAPA_TRATAMIENTO_SUGERIDO;

        // Antes de renderizar: si el formulario apunta a una evaluación
        // específica (?crear=ID, desde "Crear tratamiento" o "Revisar
        // sugerencia"), se busca si YA existe un PlanTratamientoRiesgo para
        // ella. Si existe, se carga para revisión (nunca se duplica); si no,
        // el formulario nace en blanco (p. ej. nivel Medio/Bajo sin borrador).
        $idEvaluacionForm = $request->query('crear');
        $tratamientoParaRevisar = $idEvaluacionForm
            ? PlanTratamientoRiesgo::with('evaluacion.activo')->where('fk_id_evaluacion', $idEvaluacionForm)->first()
            : null;

        return view('riesgos.plan-tratamiento', compact(
            'tratamientos', 'evaluaciones', 'evaluacionesSinTratamiento', 'estrategias', 'estados', 'mapaSugerido',
            'tratamientoParaRevisar'
        ));
    }

    public function tratamientoStore(Request $request, MotorFmeaService $service)
    {
        $data = $request->validate([
            'fk_id_evaluacion' => 'required|integer|exists:evaluacion_riesgo,id_evaluacion',
            'estrategia' => 'required|string|in:' . implode(',', self::ESTRATEGIAS_TRATAMIENTO),
            'accion_preventiva' => 'required|string',
            'estado_tratamiento' => 'required|string|in:' . implode(',', self::ESTADOS_TRATAMIENTO),
            'observacion_cierre' => [
                Rule::requiredIf(fn () => $request->input('estado_tratamiento') === 'Completado'
                    && in_array($request->input('estrategia'), self::ESTRATEGIAS_SIN_MANTENIMIENTO, true)),
                'nullable',
                'string',
            ],
            'fecha_compromiso' => 'nullable|date',
            // 'indicador_seguimiento' NO es un campo de este formulario: vive
            // en el catálogo de modos de falla y se copia solo (ver rama
            // "sin borrador previo" abajo y MotorFmeaService::evaluarActivo()).
            // Al no estar en esta lista, Laravel lo descarta si alguien lo
            // envía igual (ej. payload manipulado).
        ], [
            'observacion_cierre.required' => 'Debes registrar qué se hizo realmente antes de cerrar un tratamiento sin mantenimiento asociado.',
        ]);

        // Este formulario sirve tanto para crear un tratamiento nuevo como
        // para revisar/confirmar el borrador que el motor ya generó para esta
        // evaluación (ver tratamientoIndex): NUNCA se duplica. Se busca por
        // fk_id_evaluacion, la misma clave que hace 1 evaluación = 1
        // tratamiento (ver FASE 0 de la ronda anterior).
        $tratamientoExistente = PlanTratamientoRiesgo::where('fk_id_evaluacion', $data['fk_id_evaluacion'])->first();

        if ($tratamientoExistente) {
            $estrategiaAnterior = $tratamientoExistente->estrategia;

            $tratamientoExistente->update([
                'estrategia' => $data['estrategia'],
                'accion_preventiva' => $data['accion_preventiva'],
                'estado_tratamiento' => $data['estado_tratamiento'],
                'observacion_cierre' => $data['observacion_cierre'] ?? $tratamientoExistente->observacion_cierre,
                'fecha_compromiso' => $data['fecha_compromiso'] ?? $tratamientoExistente->fecha_compromiso,
            ]);
            // Si el usuario confirma la sugerencia tal cual (sin cambiar
            // ningún valor), Eloquent no detecta atributos "dirty" y no
            // toca updated_at por sí solo. Se fuerza explícitamente: sin
            // agregar ninguna columna, 'updated_at > created_at' es la señal
            // de "ya fue revisado por un usuario" que usa la vista.
            $tratamientoExistente->touch();
            // 'estrategia_sugerida'/'accion_sugerida'/'indicador_seguimiento'
            // y 'origen' no se tocan: siguen reflejando qué generó el motor
            // originalmente, copiado del catálogo una sola vez al crearse.
            $tratamiento = $tratamientoExistente->fresh();

            $this->reconciliarMantenimientoPorCambioEstrategia($tratamiento, $estrategiaAnterior, $service);

            $mensaje = 'Sugerencia de tratamiento confirmada con éxito';
        } else {
            // Sin borrador previo (p. ej. nivel Medio/Bajo que el usuario
            // decide tratar igual): se congela la sugerencia si el nivel
            // tuviera una definida, y se crea desde cero.
            $evaluacion = EvaluacionRiesgo::with('modoFalla')->find($data['fk_id_evaluacion']);
            $sugerencia = MotorFmeaService::MAPA_TRATAMIENTO_SUGERIDO[$evaluacion->nivel_riesgo] ?? null;
            if ($sugerencia) {
                $data['estrategia_sugerida'] = $sugerencia['estrategia'];
                $data['accion_sugerida'] = $sugerencia['accion_preventiva'];
                // Del catálogo por modo de falla, no del formulario (ver
                // MotorFmeaService::evaluarActivo() para el mismo criterio).
                $data['indicador_seguimiento'] = $evaluacion->modoFalla->indicador_seguimiento ?? null;
            }

            $data['origen'] = 'manual';
            $tratamiento = PlanTratamientoRiesgo::create($data);

            // Cierre del ciclo: solo "Mitigar" implica intervención física
            // sobre el activo, así que solo esa estrategia programa un Mantenimiento.
            $service->crearMantenimientoSiMitiga($tratamiento);

            $mensaje = 'Plan de tratamiento creado con éxito';
        }

        return redirect()->route('admin.riesgos.tratamiento')->with('success', $mensaje);
    }

    /**
     * Si al confirmar/ajustar un tratamiento existente la estrategia cambió
     * de o hacia 'Mitigar', el Mantenimiento vinculado deja de corresponder:
     * se programa uno nuevo si ahora es Mitigar, o se cancela el que había
     * quedado agendado si dejó de serlo (nunca se borra el registro: queda
     * como 'Cancelado' para trazabilidad).
     */
    private function reconciliarMantenimientoPorCambioEstrategia(PlanTratamientoRiesgo $tratamiento, ?string $estrategiaAnterior, MotorFmeaService $service): void
    {
        if ($estrategiaAnterior === $tratamiento->estrategia) {
            return;
        }

        if ($tratamiento->estrategia === 'Mitigar') {
            $service->crearMantenimientoSiMitiga($tratamiento);

            return;
        }

        Mantenimiento::where('id_evaluacion_origen', $tratamiento->fk_id_evaluacion)
            ->whereIn('estado_mantenimiento', ['Programado', 'Pendiente', 'En ejecución'])
            ->update(['estado_mantenimiento' => 'Cancelado']);
    }

    public function tratamientoUpdate(Request $request, $id)
    {
        $tratamiento = PlanTratamientoRiesgo::findOrFail($id);

        // 'estrategia_sugerida'/'accion_sugerida' son de solo lectura tras la
        // creación: no forman parte de $data, así que ningún update puede
        // tocarlas, sea cual sea el payload recibido. 'estrategia' tampoco es
        // editable aquí por el mismo motivo (ver ronda anterior).
        $data = $request->validate([
            'estado_tratamiento' => 'required|string|in:' . implode(',', self::ESTADOS_TRATAMIENTO),
            'observacion_cierre' => [
                Rule::requiredIf(fn () => $request->input('estado_tratamiento') === 'Completado'
                    && in_array($tratamiento->estrategia, self::ESTRATEGIAS_SIN_MANTENIMIENTO, true)),
                'nullable',
                'string',
            ],
        ], [
            'observacion_cierre.required' => 'Debes registrar qué se hizo realmente antes de cerrar un tratamiento sin mantenimiento asociado.',
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

        // Ya no se consultan las evaluaciones vigentes: alimentaban el
        // formulario de alta manual de alertas, que se retiró porque las
        // alertas las genera el motor FMEA.
        return view('riesgos.alertas', compact('alertas'));
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
