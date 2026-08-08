<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivoTecnologico;
use App\Models\EvaluacionRiesgo;
use App\Models\Incidente;
use App\Models\Mantenimiento;
use App\Models\ModoFalla;
use App\Models\OrigenMantenimiento;
use App\Models\PlanTratamientoRiesgo;
use App\Models\Usuario;
use App\Services\MotorFmeaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProcesoActivoController extends Controller
{
    public function incidentesIndex()
    {
        $activos = ActivoTecnologico::with(['categoria', 'ubicacion'])->orderBy('nombre_equipo')->get();
        $usuarios = Usuario::with('rol')->orderBy('nombre')->get();
        $modos = ModoFalla::orderBy('nombre_modo_falla')->get();

        return view('incidentes.registrar', compact('activos', 'usuarios', 'modos'));
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
            'fecha_incidente' => 'required|date',
            'descripcion_falla' => 'required|string',
            'fk_id_modo_falla' => 'nullable|integer|exists:modos_falla,id_modo_falla',
            'estado_incidente' => 'required|string|max:255',
        ]);

        Incidente::create([
            'fk_id_activo' => $data['fk_id_activo'],
            'fk_id_usuario_reporta' => $data['fk_id_usuario_reporta'],
            'fecha_incidente' => $data['fecha_incidente'],
            'descripcion_falla' => $data['descripcion_falla'],
            'fk_id_modo_falla' => $data['fk_id_modo_falla'] ?? null,
            'estado_incidente' => $data['estado_incidente'],
        ]);

        return redirect()->route('admin.incidentes.seguimiento')->with('success', 'Incidente registrado con éxito. Ahora puede realizar su seguimiento.');
    }

    public function incidentesSeguimiento(Request $request)
    {
        $query = Incidente::with(['activo.categoria', 'activo.ubicacion', 'usuarioReporta.rol'])
            ->whereNotIn('estado_incidente', ['Resuelto', 'Cerrado', 'resuelto', 'cerrado']);

        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('estado_incidente', 'like', "%{$buscar}%")
                  ->orWhere('descripcion_falla', 'like', "%{$buscar}%")
                  ->orWhereHas('activo', fn($a) => $a->where('nombre_equipo', 'like', "%{$buscar}%")->orWhere('codigo_patrimonial', 'like', "%{$buscar}%"))
                  ->orWhereHas('usuarioReporta', fn($u) => $u->where('nombre', 'like', "%{$buscar}%"));
            });
        }

        $incidentes = $query->orderByDesc('fecha_incidente')->paginate(15)->withQueryString();

        return view('incidentes.seguimiento', compact('incidentes'));
    }

    public function incidentesHistorial(Request $request)
    {
        $query = Incidente::with(['activo.categoria', 'activo.ubicacion', 'usuarioReporta.rol']);

        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('estado_incidente', 'like', "%{$buscar}%")
                  ->orWhere('descripcion_falla', 'like', "%{$buscar}%")
                  ->orWhereHas('activo', fn($a) => $a->where('nombre_equipo', 'like', "%{$buscar}%")->orWhere('codigo_patrimonial', 'like', "%{$buscar}%"))
                  ->orWhereHas('usuarioReporta', fn($u) => $u->where('nombre', 'like', "%{$buscar}%"));
            });
        }

        $incidentes = $query->orderByDesc('fecha_incidente')->paginate(15)->withQueryString();

        return view('incidentes.historial', compact('incidentes'));
    }

    public function incidentesShow($id)
    {
        $incidente = Incidente::with(['activo.categoria', 'activo.ubicacion', 'usuarioReporta.rol'])->findOrFail($id);

        return view('incidentes.show', compact('incidente'));
    }

    public function incidentesUpdate(Request $request, $id)
    {
        $incidente = Incidente::findOrFail($id);

        $data = $request->validate([
            'estado_incidente' => 'required|string|max:255',
            'descripcion_falla' => 'nullable|string',
        ]);

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
            'id_evaluacion_origen' => 'nullable|integer|exists:evaluacion_riesgo,id_evaluacion',
            'fk_id_incidente' => 'nullable|integer|exists:incidentes,id_incidente',
            'tipo_mantenimiento' => 'required|string|max:255',
            'fecha_programada' => 'required|date',
            'fecha_ejecucion' => 'nullable|date',
            'descripcion_trabajo' => 'nullable|string',
            'estado_mantenimiento' => 'required|string|max:255',
            'evidencia_pdf' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        // Determinar automáticamente el origen según la presencia de evaluación o incidente
        if (!empty($data['fk_id_incidente'])) {
            $data['fk_id_origen'] = 3; // Incidente
            $data['id_evaluacion_origen'] = null;
        } elseif (!empty($data['id_evaluacion_origen'])) {
            $data['fk_id_origen'] = 2; // Evaluación de riesgos
            $data['fk_id_incidente'] = null;
        } else {
            $data['fk_id_origen'] = 1; // Preventivo
            $data['id_evaluacion_origen'] = null;
            $data['fk_id_incidente'] = null;
        }

        if ($request->hasFile('evidencia_pdf')) {
            $data['evidencia_pdf'] = $request->file('evidencia_pdf')->store('mantenimientos', 'public');
        }

        Mantenimiento::create($data);

        return redirect()->route('admin.mantenimiento.programar')->with('success', 'Mantenimiento registrado con éxito');
    }

    public function mantenimientoCronograma(Request $request)
    {
        $tresDiasDespues = \Carbon\Carbon::now()->addDays(3)->endOfDay();

        $alertasProx3Dias = Mantenimiento::with(['activo.ubicacion', 'usuarioAsignado', 'origenMantenimiento'])
            ->whereNotIn('estado_mantenimiento', ['Finalizado', 'Cancelado', 'Completado'])
            ->where('fecha_programada', '<=', $tresDiasDespues)
            ->orderBy('fecha_programada')
            ->get();

        $query = Mantenimiento::with(['activo.ubicacion', 'usuarioAsignado', 'origenMantenimiento', 'evaluacionOrigen', 'incidente']);

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
            'estado_mantenimiento' => 'required|string|max:255',
            'descripcion_trabajo' => 'nullable|string',
        ]);

        $seFinalizo = in_array($data['estado_mantenimiento'], ['Finalizado', 'Completado']) && !$mantenimiento->fecha_ejecucion;

        if ($seFinalizo) {
            $data['fecha_ejecucion'] = date('Y-m-d');
        }

        $mantenimiento->update($data);

        // Cierre del ciclo (disparador inverso): el hecho físico que cambia
        // el riesgo es la ejecución real del Mantenimiento, no el cambio de
        // estado del Tratamiento. Al finalizar un Mantenimiento vinculado a
        // una evaluación (id_evaluacion_origen, ya existente en el
        // diccionario de datos), se cierra el Tratamiento "Mitigar" que lo
        // originó y se dispara una nueva evaluación del activo.
        if ($seFinalizo && $mantenimiento->id_evaluacion_origen) {
            $actualizados = PlanTratamientoRiesgo::where('fk_id_evaluacion', $mantenimiento->id_evaluacion_origen)
                ->where('estado_tratamiento', '!=', 'Completado')
                ->update(['estado_tratamiento' => 'Completado']);

            if ($actualizados > 0) {
                $service->evaluarActivo($mantenimiento->fk_id_activo);
            }
        }

        return redirect()->back()->with('success', 'Mantenimiento actualizado con éxito');
    }

    public function mantenimientoShow($id)
    {
        $mantenimiento = Mantenimiento::with([
            'activo.categoria', 
            'activo.ubicacion', 
            'usuarioAsignado.rol', 
            'origenMantenimiento', 
            'evaluacionOrigen.activo', 
            'incidente.usuarioReporta'
        ])->findOrFail($id);

        return view('mantenimiento.show', compact('mantenimiento'));
    }

    public function mantenimientoHistorial(Request $request)
    {
        $query = Mantenimiento::with(['activo.ubicacion', 'usuarioAsignado', 'origenMantenimiento', 'evaluacionOrigen', 'incidente']);

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
