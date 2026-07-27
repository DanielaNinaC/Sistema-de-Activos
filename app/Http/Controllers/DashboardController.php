<?php

namespace App\Http\Controllers;

use App\Models\ActivoTecnologico;
use App\Models\Alerta;
use App\Models\EvaluacionRiesgo;
use App\Models\Incidente;
use App\Models\Mantenimiento;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        // Cache all dashboard data for 5 minutes to avoid repeated heavy queries
        $data = Cache::remember('dashboard_data', 300, function () {
            $totalActivos = ActivoTecnologico::count();
            $riesgosCriticos = EvaluacionRiesgo::where('nivel_riesgo', 'Crítico')->count();
            $incidentesAbiertos = Incidente::whereNotIn('estado_incidente', ['Resuelto', 'Cerrado'])->count();
            $mantenimientosPendientes = Mantenimiento::whereIn('estado_mantenimiento', ['Pendiente', 'Programado', 'En ejecución'])->count();

            $activosPorCategoria = ActivoTecnologico::query()
                ->selectRaw('categorias.nombre_categoria as nombre, COUNT(activos_tecnologicos.id_activo) as total')
                ->join('categorias', 'categorias.id_categoria', '=', 'activos_tecnologicos.fk_id_categoria')
                ->groupBy('categorias.nombre_categoria')
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

            // Combine 3 separate alert queries into one efficient query
            $alertasCounts = Alerta::query()
                ->selectRaw("
                    SUM(CASE WHEN EXISTS (
                        SELECT 1 FROM evaluacion_riesgo
                        WHERE evaluacion_riesgo.id_evaluacion = alertas.fk_id_evaluacion
                        AND evaluacion_riesgo.nivel_riesgo = 'Crítico'
                    ) THEN 1 ELSE 0 END) as rojas,
                    SUM(CASE WHEN tipo_alerta = 'Naranja' THEN 1 ELSE 0 END) as naranjas,
                    SUM(CASE WHEN tipo_alerta = 'Amarilla' THEN 1 ELSE 0 END) as amarillas
                ")
                ->first();

            $alertasResumen = [
                'rojas' => (int) ($alertasCounts->rojas ?? 0),
                'naranjas' => (int) ($alertasCounts->naranjas ?? 0),
                'amarillas' => (int) ($alertasCounts->amarillas ?? 0),
            ];

            return compact(
                'totalActivos',
                'riesgosCriticos',
                'incidentesAbiertos',
                'mantenimientosPendientes',
                'activosPorCategoria',
                'activosPorEstado',
                'alertasRecientes',
                'alertasResumen'
            );
        });

        return view('dashboard', $data);
    }
}
