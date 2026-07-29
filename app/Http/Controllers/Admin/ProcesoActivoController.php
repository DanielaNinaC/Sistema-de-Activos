<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivoTecnologico;
use App\Models\EvaluacionRiesgo;
use App\Models\Incidente;
use App\Models\Mantenimiento;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProcesoActivoController extends Controller
{
    public function incidentesIndex()
    {
        $activos = ActivoTecnologico::with(['categoria', 'ubicacion'])->orderBy('nombre_equipo')->get();
        $usuarios = Usuario::with('rol')->orderBy('nombre')->get();

        return view('incidentes.registrar', compact('activos', 'usuarios'));
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
            'estado_incidente' => 'required|string|max:255',
        ]);

        Incidente::create([
            'fk_id_activo' => $data['fk_id_activo'],
            'fk_id_usuario_reporta' => $data['fk_id_usuario_reporta'],
            'fecha_incidente' => $data['fecha_incidente'],
            'descripcion_falla' => $data['descripcion_falla'],
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

    public function mantenimientoIndex()
    {
        $mantenimientos = Mantenimiento::with(['activo:id_activo,nombre_equipo,codigo_patrimonial', 'usuarioAsignado:id_usuario,nombre', 'evaluacionOrigen'])
            ->orderByDesc('fecha_programada')
            ->paginate(15);

        $activos = ActivoTecnologico::select('id_activo', 'nombre_equipo', 'codigo_patrimonial')->orderBy('id_activo')->get();
        $usuarios = Usuario::select('id_usuario', 'nombre')->orderBy('nombre')->get();
        $evaluaciones = EvaluacionRiesgo::with('activo:id_activo,nombre_equipo')
            ->select('id_evaluacion', 'fk_id_activo', 'nivel_riesgo', 'score_calculado', 'fecha_evaluacion')
            ->orderByDesc('fecha_evaluacion')
            ->get();

        return view('mantenimiento.programar', compact('mantenimientos', 'activos', 'usuarios', 'evaluaciones'));
    }

    public function mantenimientoStore(Request $request)
    {
        $data = $request->validate([
            'fk_id_activo' => 'required|integer|exists:activos_tecnologicos,id_activo',
            'fk_id_usuario_asignado' => 'required|integer|exists:usuarios,id_usuario',
            'id_evaluacion_origen' => 'nullable|integer|exists:evaluacion_riesgo,id_evaluacion',
            'tipo_mantenimiento' => 'required|string|max:255',
            'fecha_programada' => 'required|date',
            'fecha_ejecucion' => 'nullable|date',
            'descripcion_trabajo' => 'nullable|string',
            'estado_mantenimiento' => 'required|string|max:255',
            'evidencia_pdf' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        if ($request->hasFile('evidencia_pdf')) {
            $data['evidencia_pdf'] = $request->file('evidencia_pdf')->store('mantenimientos', 'public');
        }

        Mantenimiento::create($data);

        return redirect()->route('admin.mantenimiento.programar')->with('success', 'Mantenimiento registrado con éxito');
    }

    public function mantenimientoHistorial(Request $request)
    {
        $query = Mantenimiento::with(['activo', 'usuarioAsignado', 'evaluacionOrigen']);

        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('tipo_mantenimiento', 'like', "%{$buscar}%")
                  ->orWhere('estado_mantenimiento', 'like', "%{$buscar}%")
                  ->orWhere('descripcion_trabajo', 'like', "%{$buscar}%")
                  ->orWhereHas('activo', fn($a) => $a->where('nombre_equipo', 'like', "%{$buscar}%"))
                  ->orWhereHas('usuarioAsignado', fn($u) => $u->where('nombre', 'like', "%{$buscar}%"));
            });
        }

        $mantenimientos = $query->orderByDesc('fecha_programada')->paginate(15)->withQueryString();

        return view('mantenimiento.historial', compact('mantenimientos'));
    }
}
