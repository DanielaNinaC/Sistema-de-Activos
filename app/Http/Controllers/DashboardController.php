<?php

namespace App\Http\Controllers;

use App\Models\ActivoTecnologico;
use App\Models\Alerta;
use App\Models\EvaluacionRiesgo;
use App\Models\Incidente;
use App\Models\Mantenimiento;

class DashboardController extends Controller
{
    public function index()
    {
        $totalActivos = ActivoTecnologico::count();
        $riesgosCriticos = EvaluacionRiesgo::whereIn('nivel_riesgo', ['Crítico', 'Alto'])->count();
        $incidentesAbiertos = Incidente::whereNotIn('estado_incidente', ['Resuelto', 'Cerrado'])->count();
        $mantenimientosPendientes = Mantenimiento::whereIn('estado_mantenimiento', ['Pendiente', 'Programado', 'En ejecución'])->count();

        $activosPorCategoria = ActivoTecnologico::query()
            ->selectRaw('categorias.nombre_categoria as nombre, COUNT(activos_tecnologicos.id_activo) as total')
            ->join('categorias', 'categorias.id_categoria', '=', 'activos_tecnologicos.fk_id_categoria')
            ->groupBy('categorias.id_categoria', 'categorias.nombre_categoria')
            ->orderByDesc('total')
            ->get();

        $activosPorEstado = ActivoTecnologico::query()
            ->selectRaw('estado_operativo as nombre, COUNT(*) as total')
            ->groupBy('estado_operativo')
            ->orderByDesc('total')
            ->get();

        $alertasRecientes = Alerta::with('evaluacion.activo')
            ->orderByDesc('fecha_generacion')
            ->take(5)
            ->get();

        $alertasCounts = Alerta::query()
            ->selectRaw("
                SUM(CASE WHEN LOWER(tipo_alerta) = 'roja' THEN 1 ELSE 0 END) as rojas,
                SUM(CASE WHEN LOWER(tipo_alerta) = 'naranja' THEN 1 ELSE 0 END) as naranjas,
                SUM(CASE WHEN LOWER(tipo_alerta) = 'amarilla' THEN 1 ELSE 0 END) as amarillas
            ")
            ->first();

        $alertasResumen = [
            'rojas' => (int) ($alertasCounts->rojas ?? 0),
            'naranjas' => (int) ($alertasCounts->naranjas ?? 0),
            'amarillas' => (int) ($alertasCounts->amarillas ?? 0),
        ];

        return view('dashboard', compact(
            'totalActivos',
            'riesgosCriticos',
            'incidentesAbiertos',
            'mantenimientosPendientes',
            'activosPorCategoria',
            'activosPorEstado',
            'alertasRecientes',
            'alertasResumen'
        ));
    }
}
