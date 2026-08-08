<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\EvaluacionRiesgo;
use App\Models\Incidente;
use App\Models\Log;
use App\Models\Mantenimiento;
use App\Models\PlanTratamientoRiesgo;
use App\Models\Ubicacion;
use App\Models\Usuario;
use App\Services\ExportService;
use App\Services\MotorFmeaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Módulo de Informes y Reportes (Sprint 6, HU-16/17/18, RF-16/17/18).
 *
 * Solo contiene reportes agregados, tendencias en el tiempo o cruces entre
 * módulos — NUNCA duplica un listado operativo que ya existe en su propio
 * módulo (Activos, Incidentes, Mantenimiento, Riesgo, Tratamiento).
 *
 * Los gráficos (Chart.js) solo existen en las vistas de pantalla. Las
 * exportaciones a PDF (dompdf, sin motor de JavaScript) muestran los mismos
 * datos como tablas/resúmenes numéricos.
 */
class InformesController extends Controller
{
    private const MESES_ABREV = [
        '01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr',
        '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
        '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic',
    ];

    public function __construct(
        private MotorFmeaService $motor,
        private ExportService $exportService,
    ) {
    }

    /**
     * Distribución de ACTIVOS (no de filas de evaluación) según el nivel de
     * riesgo más alto entre sus evaluaciones vigentes. Reutiliza
     * MotorFmeaService::obtenerNivelYAlerta() — única fuente de verdad de
     * los umbrales, nunca se reimplementan aquí. Usada por auditoriaConsolidado().
     */
    private function distribucionPorNivelRiesgo(): array
    {
        $totalActivos = ActivoTecnologico::count();

        $maxScoresPorActivo = EvaluacionRiesgo::where('vigente', true)
            ->selectRaw('fk_id_activo, MAX(score_calculado) as max_score')
            ->groupBy('fk_id_activo')
            ->pluck('max_score', 'fk_id_activo');

        $conteo = ['Crítico' => 0, 'Alto' => 0, 'Medio' => 0, 'Bajo' => 0];

        foreach ($maxScoresPorActivo as $maxScore) {
            $nivel = $this->motor->obtenerNivelYAlerta((float) $maxScore)['nivel'];
            $conteo[$nivel] = ($conteo[$nivel] ?? 0) + 1;
        }

        $conteo['Sin evaluar'] = max(0, $totalActivos - $maxScoresPorActivo->count());

        return $conteo;
    }

    // =========================================================================
    // 4.2 — Vida Útil Remanente
    // =========================================================================

    public function vidaUtilRemanente(Request $request)
    {
        $activos = $this->activosConVidaUtil($request);
        $categorias = Categoria::orderBy('nombre_categoria')->get();
        $ubicaciones = Ubicacion::orderBy('seccion')->orderBy('aula')->get();

        return view('informes.vida-util', compact('activos', 'categorias', 'ubicaciones'));
    }

    private function activosConVidaUtil(Request $request)
    {
        $query = ActivoTecnologico::with(['categoria', 'ubicacion']);

        if ($request->filled('fk_id_categoria')) {
            $query->where('fk_id_categoria', $request->input('fk_id_categoria'));
        }
        if ($request->filled('fk_id_ubicacion')) {
            $query->where('fk_id_ubicacion', $request->input('fk_id_ubicacion'));
        }

        return $query->get()
            ->map(function ($activo) {
                $activo->vida_util = $this->motor->calcularVidaUtilRemanente($activo);

                return $activo;
            })
            ->sortByDesc(fn ($a) => $a->vida_util['porcentaje_consumido'])
            ->values();
    }

    public function vidaUtilPdf(Request $request)
    {
        $activos = $this->activosConVidaUtil($request);

        return $this->exportService->exportarPdf('informes.pdf.vida-util', ['activos' => $activos], 'vida-util-remanente');
    }

    public function vidaUtilExcel(Request $request)
    {
        $activos = $this->activosConVidaUtil($request);

        $encabezados = ['Código', 'Activo', 'Categoría', 'Ubicación', 'Antigüedad (meses)', 'Vida Útil (meses)', 'Meses Restantes', '% Consumido'];
        $datos = $activos->map(fn ($a) => [
            $a->codigo_patrimonial,
            $a->nombre_equipo,
            $a->categoria->nombre_categoria ?? '-',
            $a->ubicacion ? $a->ubicacion->seccion . ' - ' . $a->ubicacion->aula : '-',
            $a->vida_util['antiguedad_meses'],
            $a->vida_util['vida_util_meses'],
            $a->vida_util['meses_restantes'],
            $a->vida_util['porcentaje_consumido'] . '%',
        ])->toArray();

        return $this->exportService->exportarExcel($datos, $encabezados, 'vida-util-remanente');
    }

    // =========================================================================
    // 4.3 — Evolución del Score por Activo
    // =========================================================================

    public function evolucionScore(Request $request)
    {
        $activos = ActivoTecnologico::orderBy('nombre_equipo')->get();
        $activoSeleccionado = null;
        $datos = null;

        if ($request->filled('fk_id_activo')) {
            $activoSeleccionado = ActivoTecnologico::find($request->input('fk_id_activo'));
            $datos = $this->obtenerEvolucionScore($request);
        }

        return view('informes.evolucion-score', compact('activos', 'activoSeleccionado', 'datos'));
    }

    private function obtenerEvolucionScore(Request $request): array
    {
        $query = EvaluacionRiesgo::where('fk_id_activo', $request->input('fk_id_activo'))
            ->with('modoFalla')
            ->orderBy('fecha_evaluacion');

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_evaluacion', '>=', $request->input('fecha_inicio'));
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_evaluacion', '<=', $request->input('fecha_fin'));
        }

        $evaluaciones = $query->get();

        $porModo = [];
        $scoreGlobalPorFecha = [];

        foreach ($evaluaciones as $ev) {
            $nombreModo = $ev->modoFalla->nombre_modo_falla ?? 'Modo eliminado';
            $fecha = Carbon::parse($ev->fecha_evaluacion)->format('Y-m-d');

            $porModo[$nombreModo][] = ['fecha' => $fecha, 'score' => (float) $ev->score_calculado];
            $scoreGlobalPorFecha[$fecha] = max($scoreGlobalPorFecha[$fecha] ?? 0, (float) $ev->score_calculado);
        }

        ksort($scoreGlobalPorFecha);

        return [
            'por_modo' => $porModo,
            'score_global' => $scoreGlobalPorFecha,
            'tabla' => $evaluaciones->map(fn ($ev) => [
                'fecha' => Carbon::parse($ev->fecha_evaluacion)->format('d/m/Y H:i'),
                'modo_falla' => $ev->modoFalla->nombre_modo_falla ?? 'Modo eliminado',
                'score' => (float) $ev->score_calculado,
                'nivel_riesgo' => $ev->nivel_riesgo,
            ])->toArray(),
        ];
    }

    public function evolucionScoreExcel(Request $request)
    {
        $request->validate(['fk_id_activo' => 'required|integer|exists:activos_tecnologicos,id_activo']);

        $activo = ActivoTecnologico::findOrFail($request->input('fk_id_activo'));
        $datos = $this->obtenerEvolucionScore($request);

        $encabezados = ['Fecha', 'Modo de Falla', 'Score', 'Nivel de Riesgo'];
        $filas = array_map(
            fn ($f) => [$f['fecha'], $f['modo_falla'], $f['score'], $f['nivel_riesgo']],
            $datos['tabla']
        );

        return $this->exportService->exportarExcel($filas, $encabezados, 'evolucion-score-' . $activo->codigo_patrimonial);
    }

    // =========================================================================
    // 4.4 — Modos de Falla Más Frecuentes
    // =========================================================================

    public function modosFallaFrecuentes()
    {
        $porEvaluacion = EvaluacionRiesgo::query()
            ->join('modos_falla', 'modos_falla.id_modo_falla', '=', 'evaluacion_riesgo.fk_id_modo_falla')
            ->selectRaw("modos_falla.nombre_modo_falla as modo_falla,
                COUNT(*) as total_evaluaciones,
                ROUND(AVG(evaluacion_riesgo.score_calculado), 2) as promedio_score,
                SUM(CASE WHEN evaluacion_riesgo.nivel_riesgo IN ('Alto', 'Crítico') THEN 1 ELSE 0 END) as veces_criticas")
            ->groupBy('modos_falla.id_modo_falla', 'modos_falla.nombre_modo_falla')
            ->orderByDesc('veces_criticas')
            ->get();

        // Complementario: solo disponible porque incidentes.fk_id_modo_falla
        // existe (Fase 1). Los incidentes sin clasificar caen en un bucket propio.
        $porIncidente = Incidente::query()
            ->leftJoin('modos_falla', 'modos_falla.id_modo_falla', '=', 'incidentes.fk_id_modo_falla')
            ->selectRaw("COALESCE(modos_falla.nombre_modo_falla, 'Sin clasificar') as modo_falla, COUNT(*) as total_incidentes")
            ->groupBy('modos_falla.id_modo_falla', 'modos_falla.nombre_modo_falla')
            ->orderByDesc('total_incidentes')
            ->get();

        return view('informes.modos-falla-frecuentes', compact('porEvaluacion', 'porIncidente'));
    }

    // =========================================================================
    // 4.5 — Mantenimiento Preventivo vs. Correctivo
    // =========================================================================

    public function mantenimientoPreventivoCorrectivo(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', now()->subMonths(11)->startOfMonth()->toDateString());
        $fechaFin = $request->input('fecha_fin', now()->toDateString());

        $query = Mantenimiento::whereNotNull('fecha_ejecucion')
            ->whereDate('fecha_ejecucion', '>=', $fechaInicio)
            ->whereDate('fecha_ejecucion', '<=', $fechaFin);

        if ($request->filled('fk_id_categoria')) {
            $query->whereHas('activo', fn ($q) => $q->where('fk_id_categoria', $request->input('fk_id_categoria')));
        }

        $mantenimientos = $query->get();

        $porTipo = $mantenimientos->groupBy('tipo_mantenimiento')->map->count();
        $tipos = $porTipo->keys()->values()->all();

        $porMes = $mantenimientos->groupBy(fn ($m) => Carbon::parse($m->fecha_ejecucion)->format('Y-m'));

        $etiquetasMensuales = [];
        $tendenciaMensual = array_fill_keys($tipos, []);

        $cursor = Carbon::parse($fechaInicio)->startOfMonth();
        $fin = Carbon::parse($fechaFin)->startOfMonth();

        while ($cursor->lte($fin)) {
            $etiquetasMensuales[] = self::MESES_ABREV[$cursor->format('m')] . ' ' . $cursor->format('y');
            $grupoDelMes = $porMes->get($cursor->format('Y-m'), collect());

            foreach ($tipos as $tipo) {
                $tendenciaMensual[$tipo][] = $grupoDelMes->where('tipo_mantenimiento', $tipo)->count();
            }

            $cursor->addMonth();
        }

        $categorias = Categoria::orderBy('nombre_categoria')->get();

        return view('informes.mantenimiento-tipo', compact(
            'porTipo',
            'tipos',
            'etiquetasMensuales',
            'tendenciaMensual',
            'categorias',
            'fechaInicio',
            'fechaFin'
        ));
    }

    // =========================================================================
    // 4.6 — Evaluaciones Críticas Sin Tratamiento
    // =========================================================================

    public function evaluacionesSinTratamiento()
    {
        $evaluaciones = $this->obtenerEvaluacionesSinTratamiento();

        return view('informes.sin-tratamiento', compact('evaluaciones'));
    }

    private function obtenerEvaluacionesSinTratamiento()
    {
        return EvaluacionRiesgo::sinTratamiento()
            ->with(['activo:id_activo,nombre_equipo,codigo_patrimonial', 'modoFalla:id_modo_falla,nombre_modo_falla'])
            ->get()
            ->map(function ($ev) {
                $ev->dias_sin_tratamiento = (int) Carbon::parse($ev->fecha_evaluacion)->diffInDays(now());

                return $ev;
            })
            ->sortByDesc('dias_sin_tratamiento')
            ->values();
    }

    public function evaluacionesSinTratamientoPdf()
    {
        $evaluaciones = $this->obtenerEvaluacionesSinTratamiento();

        return $this->exportService->exportarPdf('informes.pdf.sin-tratamiento', ['evaluaciones' => $evaluaciones], 'evaluaciones-sin-tratamiento');
    }

    public function evaluacionesSinTratamientoExcel()
    {
        $evaluaciones = $this->obtenerEvaluacionesSinTratamiento();

        $encabezados = ['Activo', 'Código', 'Modo de Falla', 'Nivel de Riesgo', 'Fecha Evaluación', 'Días sin Tratamiento'];
        $datos = $evaluaciones->map(fn ($e) => [
            $e->activo->nombre_equipo ?? '-',
            $e->activo->codigo_patrimonial ?? '-',
            $e->modoFalla->nombre_modo_falla ?? '-',
            $e->nivel_riesgo,
            Carbon::parse($e->fecha_evaluacion)->format('d/m/Y'),
            $e->dias_sin_tratamiento,
        ])->toArray();

        return $this->exportService->exportarExcel($datos, $encabezados, 'evaluaciones-sin-tratamiento');
    }

    // =========================================================================
    // 4.7 — Trazabilidad de Usuarios (Log de Auditoría)
    // =========================================================================

    public function trazabilidadUsuarios(Request $request)
    {
        $logs = $this->obtenerLogs($request)->paginate(20)->withQueryString();
        $usuarios = Usuario::orderBy('nombre')->get();
        $tablas = Log::query()->select('tabla_afectada')->distinct()->orderBy('tabla_afectada')->pluck('tabla_afectada');

        return view('informes.trazabilidad', compact('logs', 'usuarios', 'tablas'));
    }

    private function obtenerLogs(Request $request)
    {
        $query = Log::with('usuario')->orderByDesc('fecha_hora');

        if ($request->filled('id_usuario')) {
            $query->where('id_usuario', $request->input('id_usuario'));
        }
        if ($request->filled('tabla_afectada')) {
            $query->where('tabla_afectada', $request->input('tabla_afectada'));
        }
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_hora', '>=', $request->input('fecha_inicio'));
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_hora', '<=', $request->input('fecha_fin'));
        }

        return $query;
    }

    public function trazabilidadExcel(Request $request)
    {
        $logs = $this->obtenerLogs($request)->get();

        $encabezados = ['Fecha/Hora', 'Usuario', 'Acción', 'Tabla Afectada', 'Registro ID', 'Valor Anterior', 'Valor Nuevo'];
        $datos = $logs->map(fn ($l) => [
            Carbon::parse($l->fecha_hora)->format('d/m/Y H:i:s'),
            $l->usuario ? trim($l->usuario->nombre . ' ' . ($l->usuario->apellido ?? '')) : '-',
            $l->accion_realizada,
            $l->tabla_afectada,
            $l->registro_id,
            $l->valor_anterior,
            $l->valor_nuevo,
        ])->toArray();

        return $this->exportService->exportarExcel($datos, $encabezados, 'trazabilidad-usuarios');
    }

    // =========================================================================
    // 4.8 — Auditoría Consolidada (resumen ejecutivo para Dirección)
    // =========================================================================

    public function auditoriaConsolidado(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin = $request->input('fecha_fin', now()->toDateString());

        $resumen = $this->calcularResumenAuditoria($fechaInicio, $fechaFin);

        return view('informes.auditoria-consolidado', compact('resumen', 'fechaInicio', 'fechaFin'));
    }

    private function calcularResumenAuditoria(string $fechaInicio, string $fechaFin): array
    {
        $totalActivos = ActivoTecnologico::count();
        $distribucionRiesgo = $this->distribucionPorNivelRiesgo();

        $totalIncidentes = Incidente::whereDate('fecha_incidente', '>=', $fechaInicio)
            ->whereDate('fecha_incidente', '<=', $fechaFin)
            ->count();

        $mantenimientosEjecutados = Mantenimiento::whereNotNull('fecha_ejecucion')
            ->whereDate('fecha_ejecucion', '>=', $fechaInicio)
            ->whereDate('fecha_ejecucion', '<=', $fechaFin)
            ->get();

        $tratamientosVencidos = PlanTratamientoRiesgo::where('estado_tratamiento', '!=', 'Completado')
            ->whereNotNull('fecha_compromiso')
            ->whereDate('fecha_compromiso', '<', now()->toDateString())
            ->count();

        $tratamientosPendientes = PlanTratamientoRiesgo::whereIn('estado_tratamiento', ['Pendiente', 'En Proceso'])->count();

        $activos = ActivoTecnologico::with('categoria')->get();
        $promedioConsumido = $activos->isEmpty()
            ? 0.0
            : round($activos->avg(fn ($a) => $this->motor->calcularVidaUtilRemanente($a)['porcentaje_consumido']), 1);

        return [
            'periodo' => ['inicio' => $fechaInicio, 'fin' => $fechaFin],
            'total_activos' => $totalActivos,
            'distribucion_riesgo' => $distribucionRiesgo,
            'total_incidentes' => $totalIncidentes,
            'total_mantenimientos' => $mantenimientosEjecutados->count(),
            'mantenimientos_por_tipo' => $mantenimientosEjecutados->groupBy('tipo_mantenimiento')->map->count(),
            'tratamientos_pendientes' => $tratamientosPendientes,
            'tratamientos_vencidos' => $tratamientosVencidos,
            'promedio_vida_util_consumida' => $promedioConsumido,
        ];
    }

    public function auditoriaConsolidadoPdf(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin = $request->input('fecha_fin', now()->toDateString());

        $resumen = $this->calcularResumenAuditoria($fechaInicio, $fechaFin);

        return $this->exportService->exportarPdf('informes.pdf.auditoria-consolidado', ['resumen' => $resumen], 'auditoria-consolidado');
    }
}
