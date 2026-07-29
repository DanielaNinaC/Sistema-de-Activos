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
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProcesoRiesgoController extends Controller
{
    private function nivelRiesgo(float $score): string
    {
        if ($score >= 8) {
            return 'Crítico';
        }

        if ($score >= 5) {
            return 'Alto';
        }

        if ($score >= 3) {
            return 'Medio';
        }

        return 'Bajo';
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

        return view('riesgos.modos-falla', compact('modos', 'categorias'));
    }

    public function modosStore(Request $request)
    {
        $data = $request->validate([
            'fk_id_categoria' => 'required|integer|exists:categorias,id_categoria',
            'nombre_modo_falla' => 'required|string|max:255',
        ]);

        ModoFalla::create($data);

        return redirect()->route('admin.riesgos.modos')->with('success', 'Modo de falla creado con éxito');
    }

    public function modosUpdate(Request $request, $id)
    {
        $modo = ModoFalla::findOrFail($id);

        $data = $request->validate([
            'fk_id_categoria' => 'required|integer|exists:categorias,id_categoria',
            'nombre_modo_falla' => 'required|string|max:255',
        ]);

        $modo->update($data);

        return redirect()->route('admin.riesgos.modos')->with('success', 'Modo de falla actualizado con éxito');
    }

    public function evaluacionIndex()
    {
        $activos = ActivoTecnologico::orderBy('id_activo')->get();
        $modos = ModoFalla::with('categoria')->orderBy('id_modo_falla')->get();
        $evaluaciones = EvaluacionRiesgo::with(['activo', 'modoFalla'])->orderByDesc('fecha_evaluacion')->paginate(15);
        $ultimoPeso = PesoAhp::orderByDesc('id_config')->first();

        return view('riesgos.evaluacion-fmea', compact('activos', 'modos', 'evaluaciones', 'ultimoPeso'));
    }

    public function evaluacionStore(Request $request)
    {
        $data = $request->validate([
            'fk_id_activo' => 'required|integer|exists:activos_tecnologicos,id_activo',
            'fk_id_modo_falla' => 'required|integer|exists:modos_falla,id_modo_falla',
            'valor_severidad' => 'required|integer|min:1|max:10',
            'valor_frecuencia' => 'required|integer|min:1|max:10',
            'valor_msr' => 'required|integer|min:1|max:10',
            'fecha_evaluacion' => 'required|date',
        ]);

        $peso = PesoAhp::orderByDesc('id_config')->first();
        $pesoSeveridad = (float) ($peso->peso_severidad ?? 0.648);
        $pesoFrecuencia = (float) ($peso->peso_frecuencia ?? 0.230);
        $pesoMsr = (float) ($peso->peso_msr ?? 0.122);

        $score = round(
            ($data['valor_severidad'] * $pesoSeveridad) +
            ($data['valor_frecuencia'] * $pesoFrecuencia) +
            ($data['valor_msr'] * $pesoMsr),
            2
        );

        $data['score_calculado'] = $score;
        $data['nivel_riesgo'] = $this->nivelRiesgo($score);

        EvaluacionRiesgo::create($data);

        return redirect()->route('admin.riesgos.evaluacion')->with('success', 'Evaluación registrada con éxito');
    }

    public function pesosIndex()
    {
        $pesos = PesoAhp::orderByDesc('id_config')->get();

        return view('riesgos.score', compact('pesos'));
    }

    public function riesgoIndex(Request $request)
    {
        $query = EvaluacionRiesgo::with(['activo:id_activo,nombre_equipo,codigo_patrimonial', 'modoFalla:id_modo_falla,nombre_modo_falla']);

        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('nivel_riesgo', 'like', "%{$buscar}%")
                  ->orWhereHas('activo', fn($a) => $a->where('nombre_equipo', 'like', "%{$buscar}%"))
                  ->orWhereHas('modoFalla', fn($m) => $m->where('nombre_modo_falla', 'like', "%{$buscar}%"));
            });
        }

        $evaluaciones = $query->orderByDesc('score_calculado')->paginate(15)->withQueryString();
        $alertas = Alerta::with('evaluacion.activo:id_activo,nombre_equipo')
            ->orderByDesc('fecha_generacion')
            ->take(20)
            ->get();

        return view('riesgos.riesgo', compact('evaluaciones', 'alertas'));
    }

    public function pesosStore(Request $request)
    {
        $data = $request->validate([
            'peso_severidad' => 'required|numeric|min:0|max:1',
            'peso_frecuencia' => 'required|numeric|min:0|max:1',
            'peso_msr' => 'required|numeric|min:0|max:1',
            'fecha_actualizacion' => 'required|date',
        ]);

        $suma = round($data['peso_severidad'] + $data['peso_frecuencia'] + $data['peso_msr'], 3);
        if (abs($suma - 1.0) > 0.05) {
            return redirect()->back()->withErrors([
                'peso_severidad' => 'La suma de los tres pesos debe ser igual a 1.000 (100%). La suma actual ingresada es ' . number_format($suma, 3)
            ])->withInput();
        }

        $data['fecha_actualizacion'] = \Carbon\Carbon::parse($data['fecha_actualizacion'])->format('Y-m-d H:i:s');

        PesoAhp::create($data);

        return redirect()->route('admin.riesgos.score')->with('success', 'Pesos AHP actualizados con éxito');
    }

    public function tratamientoIndex()
    {
        $tratamientos = PlanTratamientoRiesgo::with(['evaluacion.activo:id_activo,nombre_equipo'])->orderByDesc('id_tratamiento')->paginate(15);
        $evaluaciones = EvaluacionRiesgo::with('activo:id_activo,nombre_equipo')
            ->select('id_evaluacion', 'fk_id_activo', 'nivel_riesgo', 'score_calculado', 'fecha_evaluacion')
            ->orderByDesc('fecha_evaluacion')
            ->get();

        return view('riesgos.plan-tratamiento', compact('tratamientos', 'evaluaciones'));
    }

    public function tratamientoStore(Request $request)
    {
        $data = $request->validate([
            'fk_id_evaluacion' => 'required|integer|exists:evaluacion_riesgo,id_evaluacion',
            'estrategia' => 'required|string|max:255',
            'accion_preventiva' => 'required|string',
            'estado_tratamiento' => 'required|string|max:255',
            'fecha_compromiso' => 'nullable|date',
        ]);

        PlanTratamientoRiesgo::create($data);

        return redirect()->route('admin.riesgos.tratamiento')->with('success', 'Plan de tratamiento creado con éxito');
    }

    public function alertasIndex()
    {
        $alertas = Alerta::with('evaluacion.activo:id_activo,nombre_equipo')->orderByDesc('fecha_generacion')->paginate(15);
        $evaluaciones = EvaluacionRiesgo::with('activo:id_activo,nombre_equipo')
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
