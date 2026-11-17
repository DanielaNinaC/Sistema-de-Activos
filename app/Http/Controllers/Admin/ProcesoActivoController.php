<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivoTecnologico;
use App\Models\EvaluacionRiesgo;
use App\Models\Incidente;
use App\Models\Mantenimiento;
use App\Models\MantenimientoActividad;
use App\Models\ModoFalla;
use App\Models\OrigenMantenimiento;
use App\Models\PlanTratamientoRiesgo;
use App\Models\Usuario;
use App\Services\MotorFmeaService;
use App\Support\Catalogo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Los vocabularios de incidentes y mantenimientos (estados, prioridades, tipos)
 * y la prioridad sugerida por nivel de riesgo viven en catálogos de la base de
 * datos (ver App\Support\Catalogo); aquí solo se consultan y se validan.
 */
class ProcesoActivoController extends Controller
{
    public function incidentesIndex()
    {
        $activos = ActivoTecnologico::with(['categoria', 'ubicacion'])->orderBy('nombre_equipo')->get();
        $usuarios = Usuario::with('rol')->orderBy('nombre')->get();
        $modos = ModoFalla::orderBy('nombre_modo_falla')->get();
        $prioridades = Catalogo::nombres('prioridades_incidente');
        $prioridadPorCriticidad = Catalogo::prioridadSugeridaPorNivel();

        return view('incidentes.registrar', compact('activos', 'usuarios', 'modos', 'prioridades', 'prioridadPorCriticidad'));
    }

    public function incidentesStore(Request $request)
    {
        if ($request->filled('usuario_reporta_nombre') && !$request->filled('fk_id_usuario_reporta')) {
            $nombreInput = trim($request->input('usuario_reporta_nombre'));
            $usuario = Usuario::whereRaw("CONCAT(nombre, ' ', COALESCE(apellido, '')) LIKE ?", ["%{$nombreInput}%"])
                ->orWhere('nombre', 'like', "%{$nombreInput}%")
                ->first();

            if ($usuario) {
                $request->merge(['fk_id_usuario_reporta' => $usuario->id_usuario]);
            } else {
                $request->merge(['fk_id_usuario_reporta' => auth()->id() ?? (Usuario::first()->id_usuario ?? null)]);
            }
        }

        $data = $request->validate([
            'fk_id_activo' => 'required|integer|exists:activos_tecnologicos,id_activo',
            'fk_id_usuario_reporta' => 'required|integer|exists:usuarios,id_usuario',
            'fecha_incidente' => [
                'required',
                'date',
                'before_or_equal:now',
                // Un incidente no puede ser anterior a la adquisición del activo.
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $adquisicion = ActivoTecnologico::where('id_activo', $request->input('fk_id_activo'))->value('fecha_adquisicion');

                    if ($adquisicion && \Carbon\Carbon::parse($value)->startOfDay()->lt(\Carbon\Carbon::parse($adquisicion)->startOfDay())) {
                        $fail('La fecha del incidente no puede ser anterior a la fecha de adquisición del activo (' . \Carbon\Carbon::parse($adquisicion)->format('d/m/Y') . ').');
                    }
                },
            ],
            'descripcion_falla' => 'required|string',
            'fk_id_modo_falla' => 'nullable|integer|exists:modos_falla,id_modo_falla',
            'estado_incidente' => ['required', 'string', Rule::in(Catalogo::nombres('estados_incidente'))],
            'prioridad' => ['required', 'string', Rule::in(Catalogo::nombres('prioridades_incidente'))],
        ]);

        Incidente::create([
            'fk_id_activo' => $data['fk_id_activo'],
            'fk_id_usuario_reporta' => $data['fk_id_usuario_reporta'],
            'fecha_incidente' => $data['fecha_incidente'],
            'descripcion_falla' => $data['descripcion_falla'],
            'fk_id_modo_falla' => $data['fk_id_modo_falla'] ?? null,
            'estado_incidente' => $data['estado_incidente'],
            'prioridad' => $data['prioridad'],
        ]);

        if ($request->input('retorno') === 'ficha' || $request->has('from_ficha')) {
            return redirect()->to(route('admin.activos.show', $data['fk_id_activo']) . '#incidentes')
                ->with('success', 'Incidente reportado y registrado con éxito para este activo.');
        }

        return redirect()->route('admin.incidentes.seguimiento')->with('success', 'Incidente registrado con éxito. Ahora puede realizar su seguimiento.');
    }

    public function incidentesSeguimiento(Request $request)
    {
        $query = Incidente::with(['activo.categoria', 'activo.ubicacion', 'usuarioReporta.rol'])
            ->whereNotIn('estado_incidente', Catalogo::estadosIncidenteCerrados());

        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('estado_incidente', 'like', "%{$buscar}%")
                  ->orWhere('descripcion_falla', 'like', "%{$buscar}%")
                  ->orWhereHas('activo', fn($a) => $a->where('nombre_equipo', 'like', "%{$buscar}%")->orWhere('codigo_patrimonial', 'like', "%{$buscar}%"))
                  ->orWhereHas('usuarioReporta', fn($u) => $u->where('nombre', 'like', "%{$buscar}%"));
            });
        }

        // Priorización: primero lo más urgente y, dentro de cada prioridad,
        // lo más reciente.
        $incidentes = $this->ordenarPorPrioridad($query)
            ->orderByDesc('fecha_incidente')
            ->paginate(15)
            ->withQueryString();

        $prioridades = Catalogo::nombres('prioridades_incidente');

        return view('incidentes.seguimiento', compact('incidentes', 'prioridades'));
    }

    /**
     * Historial agrupado por activo: cada fila es un activo que ha tenido al
     * menos un incidente, con el resumen de su historial. El detalle de cada
     * activo (incidentesHistorialActivo) lista sus incidentes uno por uno.
     */
    public function incidentesHistorial(Request $request)
    {
        $query = ActivoTecnologico::query()
            ->with(['categoria', 'ubicacion'])
            ->whereHas('incidentes')
            ->withCount([
                'incidentes',
                'incidentes as incidentes_abiertos_count' => function ($q) {
                    $q->whereNotIn('estado_incidente', Catalogo::estadosIncidenteCerrados());
                },
            ])
            ->withMax('incidentes', 'fecha_incidente');

        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre_equipo', 'like', "%{$buscar}%")
                  ->orWhere('codigo_patrimonial', 'like', "%{$buscar}%")
                  ->orWhereHas('categoria', fn($c) => $c->where('nombre_categoria', 'like', "%{$buscar}%"))
                  ->orWhereHas('ubicacion', fn($u) => $u->where('seccion', 'like', "%{$buscar}%")->orWhere('aula', 'like', "%{$buscar}%"));
            });
        }

        // Primero los activos con actividad más reciente.
        $activos = $query->orderByDesc('incidentes_max_fecha_incidente')
            ->paginate(15)
            ->withQueryString();

        return view('incidentes.historial', compact('activos'));
    }

    /**
     * Todos los incidentes registrados para un activo concreto.
     */
    public function incidentesHistorialActivo($id)
    {
        $activo = ActivoTecnologico::with(['categoria', 'ubicacion'])->findOrFail($id);

        $incidentes = Incidente::with(['usuarioReporta.rol', 'modoFalla'])
            ->where('fk_id_activo', $activo->id_activo)
            ->orderByDesc('fecha_incidente')
            ->paginate(15);

        $totalAbiertos = Incidente::where('fk_id_activo', $activo->id_activo)
            ->whereNotIn('estado_incidente', Catalogo::estadosIncidenteCerrados())
            ->count();

        $prioridades = Catalogo::nombres('prioridades_incidente');

        return view('incidentes.historial-activo', compact('activo', 'incidentes', 'totalAbiertos', 'prioridades'));
    }

    public function incidentesShow($id)
    {
        $incidente = Incidente::with(['activo.categoria', 'activo.ubicacion', 'usuarioReporta.rol'])->findOrFail($id);

        return view('incidentes.show', compact('incidente'));
    }

    /**
     * Ordena por prioridad de atención. Se construye un CASE a partir del peso
     * de cada prioridad (catálogo prioridades_incidente) en vez de ordenar por
     * la columna directamente (alfabéticamente daría Alta, Baja, Media) y sin
     * usar FIELD(), que es exclusivo de MySQL y rompería las pruebas en SQLite.
     */
    private function ordenarPorPrioridad($query)
    {
        $pesos = Catalogo::pesoPrioridad();

        $casos = '';
        foreach ($pesos as $peso) {
            $casos .= ' WHEN ? THEN ' . (int) $peso;
        }

        return $query->orderByRaw(
            "CASE prioridad{$casos} ELSE 99 END",
            array_keys($pesos)
        );
    }

    public function incidentesUpdate(Request $request, $id)
    {
        $incidente = Incidente::findOrFail($id);

        $data = $request->validate([
            'estado_incidente' => ['required', 'string', Rule::in(Catalogo::nombres('estados_incidente'))],
            'descripcion_falla' => 'nullable|string',
            'prioridad' => ['nullable', 'string', Rule::in(Catalogo::nombres('prioridades_incidente'))],
        ]);

        // Campo opcional: si el formulario no lo envía, se conserva la
        // prioridad actual en vez de sobrescribirla con null.
        if (empty($data['prioridad'])) {
            unset($data['prioridad']);
        }

        // fecha_cierre se deriva del estado, no se captura a mano: se fija la
        // primera vez que el incidente entra a un estado cerrado y se limpia
        // si se reabre (evita fechas de cierre "congeladas" al reabrir).
        $seCierraAhora = in_array($data['estado_incidente'], Catalogo::estadosIncidenteCerrados(), true);
        if ($seCierraAhora && !$incidente->fecha_cierre) {
            $data['fecha_cierre'] = now();
        } elseif (!$seCierraAhora) {
            $data['fecha_cierre'] = null;
        }

        $incidente->update($data);

        return redirect()->back()->with('success', 'Estado del incidente actualizado con éxito');
    }

    // ─────────────────────────────────────────────
    //  MANTENIMIENTO
    // ─────────────────────────────────────────────

    public function mantenimientoIndex(Request $request)
    {
        $activos = ActivoTecnologico::select('id_activo', 'nombre_equipo', 'codigo_patrimonial')->orderBy('id_activo')->get();
        $usuarios = Usuario::select('id_usuario', 'nombre', 'apellido')->orderBy('nombre')->get();
        $origenes = OrigenMantenimiento::orderBy('id_origen')->get();
        $evaluaciones = EvaluacionRiesgo::with('activo:id_activo,nombre_equipo')
            ->select('id_evaluacion', 'fk_id_activo', 'nivel_riesgo', 'score_calculado', 'fecha_evaluacion')
            ->orderByDesc('fecha_evaluacion')
            ->get();
        $incidentes = Incidente::with('activo:id_activo,nombre_equipo')
            ->select('id_incidente', 'fk_id_activo', 'descripcion_falla', 'estado_incidente', 'fecha_incidente')
            ->orderByDesc('fecha_incidente')
            ->get();

        // Prellenado automático desde query params
        $preOrigen = $request->query('origen');
        $preEvaluacionId = $request->query('evaluacion_id');
        $preIncidenteId = $request->query('incidente_id');
        $preActivoId = $request->query('activo_id');

        return view('mantenimiento.programar', compact(
            'activos', 'usuarios', 'origenes', 'evaluaciones', 'incidentes',
            'preOrigen', 'preEvaluacionId', 'preIncidenteId', 'preActivoId'
        ));
    }

    public function mantenimientoStore(Request $request)
    {
        $data = $request->validate([
            'fk_id_activo' => 'required|integer|exists:activos_tecnologicos,id_activo',
            'fk_id_usuario_asignado' => 'required|integer|exists:usuarios,id_usuario',
            // El incidente o la evaluación que origina el mantenimiento deben ser del mismo activo.
            'id_evaluacion_origen' => [
                'nullable', 'integer',
                Rule::exists('evaluacion_riesgo', 'id_evaluacion')->where('fk_id_activo', $request->input('fk_id_activo')),
            ],
            'fk_id_incidente' => [
                'nullable', 'integer',
                Rule::exists('incidentes', 'id_incidente')->where('fk_id_activo', $request->input('fk_id_activo')),
            ],
            'tipo_mantenimiento' => ['required', 'string', Rule::in(Catalogo::nombres('tipos_mantenimiento'))],
            'fecha_programada' => 'required|date',
            'fecha_ejecucion' => 'nullable|date|after_or_equal:fecha_programada',
            'descripcion_trabajo' => 'nullable|string',
            'estado_mantenimiento' => ['required', 'string', Rule::in(Catalogo::nombres('estados_mantenimiento'))],
            'evidencia_pdf' => 'nullable|file|mimes:pdf|max:5120',
        ], [
            'id_evaluacion_origen.exists' => 'La evaluación de riesgo seleccionada no corresponde al activo elegido.',
            'fk_id_incidente.exists' => 'El incidente seleccionado no corresponde al activo elegido.',
            'fecha_ejecucion.after_or_equal' => 'La fecha de ejecución no puede ser anterior a la fecha programada.',
        ]);

        // Determinar automáticamente el origen según la presencia de evaluación o incidente
        if (!empty($data['fk_id_incidente'])) {
            $data['fk_id_origen'] = Catalogo::origenMantenimientoId('Incidente');
            $data['id_evaluacion_origen'] = null;
        } elseif (!empty($data['id_evaluacion_origen'])) {
            $data['fk_id_origen'] = Catalogo::origenMantenimientoId('Evaluación de riesgos');
            $data['fk_id_incidente'] = null;
        } else {
            $data['fk_id_origen'] = Catalogo::origenMantenimientoId('Preventivo');
            $data['id_evaluacion_origen'] = null;
            $data['fk_id_incidente'] = null;
        }

        if ($request->hasFile('evidencia_pdf')) {
            $data['evidencia_pdf'] = $request->file('evidencia_pdf')->store('mantenimientos', 'public');
        }

        Mantenimiento::create($data);

        if ($request->input('retorno') === 'ficha' || $request->has('from_ficha')) {
            return redirect()->to(route('admin.activos.show', $data['fk_id_activo']) . '#mantenimiento')
                ->with('success', 'Mantenimiento programado con éxito para este activo.');
        }

        return redirect()->route('admin.mantenimiento.programar')->with('success', 'Mantenimiento registrado con éxito');
    }

    public function mantenimientoCronograma(Request $request)
    {
        $tresDiasDespues = \Carbon\Carbon::now()->addDays(3)->endOfDay();

        $alertasProx3Dias = Mantenimiento::with(['activo.ubicacion', 'usuarioAsignado', 'usuarioFinaliza', 'actividades', 'origenMantenimiento'])
            ->whereIn('estado_mantenimiento', Catalogo::estadosMantenimientoAbiertos())
            ->where('fecha_programada', '<=', $tresDiasDespues)
            ->orderBy('fecha_programada')
            ->get();

        $query = Mantenimiento::with(['activo.ubicacion', 'usuarioAsignado', 'usuarioFinaliza', 'actividades', 'origenMantenimiento', 'evaluacionOrigen', 'incidente']);

        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('tipo_mantenimiento', 'like', "%{$buscar}%")
                  ->orWhere('estado_mantenimiento', 'like', "%{$buscar}%")
                  ->orWhere('descripcion_trabajo', 'like', "%{$buscar}%")
                  ->orWhereHas('activo', fn($a) => $a->where('nombre_equipo', 'like', "%{$buscar}%")->orWhere('codigo_patrimonial', 'like', "%{$buscar}%"))
                  ->orWhereHas('usuarioAsignado', fn($u) => $u->where('nombre', 'like', "%{$buscar}%")->orWhere('apellido', 'like', "%{$buscar}%"))
                  ->orWhereHas('origenMantenimiento', fn($o) => $o->where('nombre', 'like', "%{$buscar}%"));
            });
        }

        $mantenimientos = $query->orderBy('fecha_programada')->get();

        return view('mantenimiento.cronograma', compact('mantenimientos', 'alertasProx3Dias'));
    }

    public function mantenimientoUpdate(Request $request, $id, MotorFmeaService $service)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);

        $data = $request->validate([
            'estado_mantenimiento' => ['required', 'string', Rule::in(Catalogo::nombres('estados_mantenimiento'))],
            'descripcion_trabajo' => 'nullable|string',
            'tareas' => 'nullable|array',
        ]);

        $pasaAFinalizado = in_array($data['estado_mantenimiento'], Catalogo::estadosMantenimientoFinalizados(), true);

        // Si ya está realizado, proteger la trazabilidad histórica
        if ($mantenimiento->estaRealizado() && $data['estado_mantenimiento'] !== $mantenimiento->estado_mantenimiento) {
            return redirect()->back()->withErrors([
                'estado_mantenimiento' => 'Este mantenimiento ya se encuentra Realizado y su evidencia histórica no puede ser modificada libremente.'
            ]);
        }

        // Si intenta cambiar a 'Realizado' / 'Finalizado', validar evidencia obligatoria
        if ($pasaAFinalizado && !$mantenimiento->estaRealizado()) {
            $tareas = $request->input('tareas', []);
            $observaciones = trim((string) ($data['descripcion_trabajo'] ?? ''));

            if (empty($tareas) && $observaciones === '') {
                return redirect()->back()->withErrors([
                    'evidencia' => 'Debe registrar al menos una tarea realizada o ingresar una observación/detalle del mantenimiento antes de finalizar.'
                ])->withInput();
            }

            $data['fecha_ejecucion'] = date('Y-m-d');
            $data['fecha_hora_realizacion'] = now();
            $data['fk_id_usuario_finaliza'] = Auth::id() ?? $mantenimiento->fk_id_usuario_asignado;

            // Guardar tareas seleccionadas
            $mantenimiento->actividades()->delete();
            foreach ($tareas as $item) {
                $numTarea = is_numeric($item) ? (int) $item : null;
                $nombreTarea = null;

                if ($numTarea && isset(Mantenimiento::TAREAS_CHECKLIST[$numTarea])) {
                    $nombreTarea = Mantenimiento::TAREAS_CHECKLIST[$numTarea];
                } elseif (is_string($item) && in_array($item, Mantenimiento::TAREAS_CHECKLIST, true)) {
                    $nombreTarea = $item;
                    $numTarea = array_search($item, Mantenimiento::TAREAS_CHECKLIST, true) ?: null;
                } elseif (is_string($item) && trim($item) !== '') {
                    $nombreTarea = trim($item);
                }

                if ($nombreTarea) {
                    $mantenimiento->actividades()->create([
                        'numero_tarea' => $numTarea,
                        'tarea' => $nombreTarea,
                    ]);
                }
            }
        }

        unset($data['tareas']);
        $mantenimiento->update($data);

        // Cierre del ciclo FMEA si proviene de evaluación de riesgo
        if ($pasaAFinalizado && $mantenimiento->id_evaluacion_origen) {
            $actualizados = PlanTratamientoRiesgo::where('fk_id_evaluacion', $mantenimiento->id_evaluacion_origen)
                ->whereIn('estado_tratamiento', Catalogo::estadosTratamientoAbiertos())
                ->update(['estado_tratamiento' => 'Completado']);

            if ($actualizados > 0) {
                $service->evaluarActivo($mantenimiento->fk_id_activo);
            }
        }

        return redirect()->back()->with('success', 'Mantenimiento actualizado con éxito');
    }

    /**
     * Registra un mantenimiento como "Realizado" con evidencia obligatoria
     * (checklist de tareas y/o observaciones detalladas).
     */
    public function mantenimientoRealizar(Request $request, $id, MotorFmeaService $service)
    {
        $mantenimiento = Mantenimiento::findOrFail($id);

        if ($mantenimiento->estaRealizado()) {
            return redirect()->back()->withErrors([
                'evidencia' => 'Este mantenimiento ya fue registrado como Realizado previamente.'
            ]);
        }

        $request->validate([
            'tareas' => 'nullable|array',
            'tareas.*' => 'nullable',
            'observaciones' => 'nullable|string|max:5000',
        ]);

        $tareas = $request->input('tareas', []);
        $observaciones = trim((string) $request->input('observaciones', ''));

        // Regla de validación: Condición A (al menos 1 tarea) O Condición B (observación con contenido)
        if (empty($tareas) && $observaciones === '') {
            return redirect()->back()->withErrors([
                'evidencia' => 'Debe registrar al menos una tarea realizada o ingresar una observación/detalle del mantenimiento antes de finalizar.'
            ])->withInput();
        }

        // Actualizar datos del mantenimiento
        $mantenimiento->estado_mantenimiento = 'Realizado';
        $mantenimiento->fecha_ejecucion = date('Y-m-d');
        $mantenimiento->fecha_hora_realizacion = now();
        $mantenimiento->fk_id_usuario_finaliza = Auth::id() ?? $mantenimiento->fk_id_usuario_asignado;
        $mantenimiento->descripcion_trabajo = $observaciones !== '' ? $observaciones : null;
        $mantenimiento->save();

        // Guardar únicamente las tareas que fueron seleccionadas
        $mantenimiento->actividades()->delete();
        foreach ($tareas as $item) {
            $numTarea = is_numeric($item) ? (int) $item : null;
            $nombreTarea = null;

            if ($numTarea && isset(Mantenimiento::TAREAS_CHECKLIST[$numTarea])) {
                $nombreTarea = Mantenimiento::TAREAS_CHECKLIST[$numTarea];
            } elseif (is_string($item) && in_array($item, Mantenimiento::TAREAS_CHECKLIST, true)) {
                $nombreTarea = $item;
                $numTarea = array_search($item, Mantenimiento::TAREAS_CHECKLIST, true) ?: null;
            } elseif (is_string($item) && trim($item) !== '') {
                $nombreTarea = trim($item);
            }

            if ($nombreTarea) {
                $mantenimiento->actividades()->create([
                    'numero_tarea' => $numTarea,
                    'tarea' => $nombreTarea,
                ]);
            }
        }

        // Cierre del tratamiento en evaluación de riesgos FMEA si aplica
        if ($mantenimiento->id_evaluacion_origen) {
            $actualizados = PlanTratamientoRiesgo::where('fk_id_evaluacion', $mantenimiento->id_evaluacion_origen)
                ->whereIn('estado_tratamiento', Catalogo::estadosTratamientoAbiertos())
                ->update(['estado_tratamiento' => 'Completado']);

            if ($actualizados > 0) {
                $service->evaluarActivo($mantenimiento->fk_id_activo);
            }
        }

        return redirect()->back()->with('success', 'Mantenimiento registrado como Realizado con éxito y evidencia almacenada en el historial.');
    }

    public function mantenimientoShow($id)
    {
        $mantenimiento = Mantenimiento::with([
            'activo.categoria', 
            'activo.ubicacion', 
            'usuarioAsignado.rol', 
            'usuarioFinaliza.rol',
            'actividades',
            'origenMantenimiento', 
            'evaluacionOrigen.activo', 
            'incidente.usuarioReporta'
        ])->findOrFail($id);

        return view('mantenimiento.show', compact('mantenimiento'));
    }

    public function mantenimientoHistorial(Request $request)
    {
        $query = Mantenimiento::with(['activo.ubicacion', 'usuarioAsignado', 'usuarioFinaliza', 'actividades', 'origenMantenimiento', 'evaluacionOrigen', 'incidente']);

        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('tipo_mantenimiento', 'like', "%{$buscar}%")
                  ->orWhere('estado_mantenimiento', 'like', "%{$buscar}%")
                  ->orWhere('descripcion_trabajo', 'like', "%{$buscar}%")
                  ->orWhereHas('activo', fn($a) => $a->where('nombre_equipo', 'like', "%{$buscar}%")->orWhere('codigo_patrimonial', 'like', "%{$buscar}%"))
                  ->orWhereHas('usuarioAsignado', fn($u) => $u->where('nombre', 'like', "%{$buscar}%")->orWhere('apellido', 'like', "%{$buscar}%"))
                  ->orWhereHas('origenMantenimiento', fn($o) => $o->where('nombre', 'like', "%{$buscar}%"));
            });
        }

        $mantenimientos = $query->orderByDesc('fecha_programada')->paginate(15)->withQueryString();

        return view('mantenimiento.historial', compact('mantenimientos'));
    }
}
