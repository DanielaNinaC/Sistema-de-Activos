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
        // Todos los activos registrados con su cálculo de vida útil
        $todosActivos = ActivoTecnologico::with(['categoria', 'ubicacion'])->get()->map(function ($activo) {
            $activo->vida_util = $this->motor->calcularVidaUtilRemanente($activo);
            return $activo;
        });

        // 1. Resumen General (totalidad de activos registrados)
        $totalActivos = $todosActivos->count();
        $superaronVidaUtil = $todosActivos->filter(fn ($a) => ($a->vida_util['meses_restantes'] ?? 1) <= 0)->count();
        $cumplenUnAnio = $todosActivos->filter(fn ($a) => ($a->vida_util['meses_restantes'] ?? 0) > 0 && ($a->vida_util['meses_restantes'] ?? 0) <= 12)->count();
        $sugerenciaReemplazo = $superaronVidaUtil + $cumplenUnAnio;

        $resumenGeneral = [
            'total_activos' => $totalActivos,
            'sugerencia_reemplazo' => $sugerenciaReemplazo,
            'superaron_vida_util' => $superaronVidaUtil,
            'cumplen_un_anio' => $cumplenUnAnio,
        ];

        // 2. Resumen por Categoría de Activo (a partir de las categorías existentes en BD)
        $categorias = Categoria::orderBy('nombre_categoria')->get();
        $resumenCategorias = $categorias->map(function ($cat) use ($todosActivos) {
            $activosCat = $todosActivos->where('fk_id_categoria', $cat->id_categoria);
            $totalCat = $activosCat->count();
            $superadaCat = $activosCat->filter(fn ($a) => ($a->vida_util['meses_restantes'] ?? 1) <= 0)->count();
            $cumpleCat = $activosCat->filter(fn ($a) => ($a->vida_util['meses_restantes'] ?? 0) > 0 && ($a->vida_util['meses_restantes'] ?? 0) <= 12)->count();
            $sugerenciaCat = $superadaCat + $cumpleCat;

            return [
                'id_categoria' => $cat->id_categoria,
                'nombre' => $cat->nombre_categoria,
                'vida_util_meses' => $cat->vida_util_meses ?? 60,
                'total' => $totalCat,
                'superada' => $superadaCat,
                'cumple_1_anio' => $cumpleCat,
                'sugerencia_reemplazo' => $sugerenciaCat,
            ];
        });

        // 3. Listado de Activos con Filtros y Paginación (10 por página)
        $activosFiltrados = $todosActivos;
        if ($request->filled('fk_id_categoria')) {
            $activosFiltrados = $activosFiltrados->where('fk_id_categoria', (int) $request->input('fk_id_categoria'));
        }
        if ($request->filled('fk_id_ubicacion')) {
            $activosFiltrados = $activosFiltrados->where('fk_id_ubicacion', (int) $request->input('fk_id_ubicacion'));
        }

        $activosOrdenados = $activosFiltrados->sortByDesc(fn ($a) => $a->vida_util['porcentaje_consumido'])->values();

        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $perPage = 10;
        $currentPageItems = $activosOrdenados->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $activos = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentPageItems,
            $activosOrdenados->count(),
            $perPage,
            $currentPage,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        $ubicaciones = Ubicacion::orderBy('seccion')->orderBy('aula')->get();

        return view('informes.vida-util', compact(
            'activos',
            'categorias',
            'ubicaciones',
            'resumenGeneral',
            'resumenCategorias'
        ));
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

        $totalActivos = $activos->count();
        $superaronVidaUtil = $activos->filter(fn ($a) => ($a->vida_util['meses_restantes'] ?? 1) <= 0)->count();
        $cumplenUnAnio = $activos->filter(fn ($a) => ($a->vida_util['meses_restantes'] ?? 0) > 0 && ($a->vida_util['meses_restantes'] ?? 0) <= 12)->count();
        $sugerenciaReemplazo = $superaronVidaUtil + $cumplenUnAnio;

        $resumenGeneral = [
            'total_activos' => $totalActivos,
            'sugerencia_reemplazo' => $sugerenciaReemplazo,
            'superaron_vida_util' => $superaronVidaUtil,
            'cumplen_un_anio' => $cumplenUnAnio,
        ];

        $categorias = Categoria::orderBy('nombre_categoria')->get();
        $resumenCategorias = $categorias->map(function ($cat) use ($activos) {
            $activosCat = $activos->where('fk_id_categoria', $cat->id_categoria);
            $totalCat = $activosCat->count();
            $superadaCat = $activosCat->filter(fn ($a) => ($a->vida_util['meses_restantes'] ?? 1) <= 0)->count();
            $cumpleCat = $activosCat->filter(fn ($a) => ($a->vida_util['meses_restantes'] ?? 0) > 0 && ($a->vida_util['meses_restantes'] ?? 0) <= 12)->count();
            $sugerenciaCat = $superadaCat + $cumpleCat;

            return [
                'id_categoria' => $cat->id_categoria,
                'nombre' => $cat->nombre_categoria,
                'vida_util_meses' => $cat->vida_util_meses ?? 60,
                'total' => $totalCat,
                'superada' => $superadaCat,
                'cumple_1_anio' => $cumpleCat,
                'sugerencia_reemplazo' => $sugerenciaCat,
            ];
        });

        if ($request->filled('fk_id_categoria')) {
            $resumenCategorias = $resumenCategorias->filter(fn ($c) => $c['total'] > 0)->values();
        }

        return $this->exportService->exportarPdf('informes.pdf.vida-util', [
            'activos' => $activos,
            'resumenGeneral' => $resumenGeneral,
            'resumenCategorias' => $resumenCategorias,
        ], 'vida-util');
    }

    public function vidaUtilExcel(Request $request)
    {
        $activos = $this->activosConVidaUtil($request);

        $encabezados = ['Código', 'Activo', 'Categoría', 'Ubicación', 'Antigüedad (meses)', 'Vida Útil (meses)', 'Meses Restantes', '% Consumido', 'Sugerencia de Reemplazo'];
        $datos = $activos->map(fn ($a) => [
            $a->codigo_patrimonial,
            $a->nombre_equipo,
            $a->categoria->nombre_categoria ?? '-',
            $a->ubicacion ? $a->ubicacion->seccion . ($a->ubicacion->aula ? ' - ' . $a->ubicacion->aula : '') : '-',
            $a->vida_util['antiguedad_meses'],
            $a->vida_util['vida_util_meses'],
            $a->vida_util['meses_restantes'],
            $a->vida_util['porcentaje_consumido'] . '%',
            $a->vida_util['meses_restantes'] <= 0 ? 'Vida útil superada' : ($a->vida_util['meses_restantes'] <= 12 ? 'Cumple en 1 año' : 'En tiempo'),
        ])->toArray();

        return $this->exportService->exportarExcel($datos, $encabezados, 'vida-util', 'Vida Útil del Inventario');
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

        return $this->exportService->exportarExcel($filas, $encabezados, 'evolucion-score-' . $activo->codigo_patrimonial, 'Evolución de Score — ' . $activo->nombre_equipo);
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

        return $this->exportService->exportarExcel($datos, $encabezados, 'evaluaciones-sin-tratamiento', 'Evaluaciones Críticas Sin Tratamiento');
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

        return $this->exportService->exportarExcel($datos, $encabezados, 'trazabilidad-usuarios', 'Trazabilidad de Usuarios — Log de Auditoría');
    }

    // =========================================================================
    // 4.8 — Auditoría Consolidada (resumen ejecutivo para Dirección)
    // =========================================================================

    public function auditoriaConsolidado(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin = $request->input('fecha_fin', now()->toDateString());

        // La Introducción/Conclusión por PLN solo se muestran en el PDF (ver
        // auditoriaConsolidadoPdf()), no en pantalla — no se calculan aquí.
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
        $introduccion = $this->generarIntroduccionAuditoria($resumen);
        $conclusion = $this->generarConclusionAuditoria($resumen);

        return $this->exportService->exportarPdf(
            'informes.pdf.auditoria-consolidado',
            ['resumen' => $resumen, 'introduccion' => $introduccion, 'conclusion' => $conclusion],
            'resumen-general-activos'
        );
    }

    /**
     * Introducción del Informe General generada mediante PLN basado en
     * plantillas (NLG determinística): construye el texto exclusivamente a
     * partir de $resumen (los mismos indicadores ya calculados por
     * calcularResumenAuditoria()), sin ninguna fuente externa. Al ser pura
     * interpolación de datos reales, el texto cambia solo si cambian los
     * indicadores — nunca inventa cifras ni las modifica.
     */
    private function generarIntroduccionAuditoria(array $resumen): string
    {
        $fechaInicio = Carbon::parse($resumen['periodo']['inicio'])->format('d/m/Y');
        $fechaFin = Carbon::parse($resumen['periodo']['fin'])->format('d/m/Y');
        $totalActivos = $resumen['total_activos'];

        return sprintf(
            'El presente Informe General consolida los principales indicadores del Sistema de Activos Tecnológicos '
            . 'correspondientes al período comprendido entre el %s y el %s. El análisis considera un total de %d %s '
            . 'registrados en el sistema, e incluye su distribución por nivel de riesgo, el estado de los planes de '
            . 'tratamiento asociados, los incidentes reportados, los mantenimientos ejecutados en el período y el '
            . 'porcentaje promedio de vida útil consumida del inventario. La información presentada corresponde '
            . 'exclusivamente a los datos registrados en el sistema a la fecha de generación de este informe.',
            $fechaInicio,
            $fechaFin,
            $totalActivos,
            $totalActivos === 1 ? 'activo tecnológico' : 'activos tecnológicos'
        );
    }

    /**
     * Conclusión del Informe General: describe objetivamente los indicadores
     * de $resumen (sin asumir causas, sin predecir comportamiento futuro y
     * sin recomendaciones no sustentadas en los datos). Cada oración se arma
     * solo si el dato correspondiente existe, para no fabricar contenido.
     */
    private function generarConclusionAuditoria(array $resumen): string
    {
        $totalActivos = $resumen['total_activos'];
        $distribucion = $resumen['distribucion_riesgo'];
        $sinEvaluar = $distribucion['Sin evaluar'] ?? 0;
        $evaluados = $totalActivos - $sinEvaluar;
        $oraciones = [];

        if ($totalActivos === 0) {
            $oraciones[] = 'No hay activos tecnológicos registrados en el sistema a la fecha de generación de este informe.';

            return implode(' ', $oraciones);
        }

        if ($sinEvaluar > 0) {
            $porcentajeSinEvaluar = number_format(($sinEvaluar / $totalActivos) * 100, 1);
            $oraciones[] = sprintf(
                'Del total de %d activos tecnológicos registrados, %d (%s%%) no cuentan con una evaluación de riesgo vigente.',
                $totalActivos,
                $sinEvaluar,
                $porcentajeSinEvaluar
            );
        } else {
            $oraciones[] = sprintf(
                'La totalidad de los %d activos tecnológicos registrados cuenta con una evaluación de riesgo vigente.',
                $totalActivos
            );
        }

        if ($evaluados > 0) {
            $oraciones[] = sprintf(
                'De los activos con evaluación vigente, %d se encuentran en nivel Crítico, %d en Alto, %d en Medio y %d en Bajo.',
                $distribucion['Crítico'] ?? 0,
                $distribucion['Alto'] ?? 0,
                $distribucion['Medio'] ?? 0,
                $distribucion['Bajo'] ?? 0
            );
        }

        if ($resumen['tratamientos_pendientes'] > 0) {
            $verboRegistrar = $resumen['tratamientos_pendientes'] === 1 ? 'Se registra' : 'Se registran';
            $planes = $resumen['tratamientos_pendientes'] === 1 ? 'plan de tratamiento' : 'planes de tratamiento';
            $conector = $resumen['tratamientos_pendientes'] === 1 ? 'del cual' : 'de los cuales';
            $verboPresentar = $resumen['tratamientos_vencidos'] === 1 ? 'presenta' : 'presentan';

            $oraciones[] = $resumen['tratamientos_vencidos'] > 0
                ? sprintf(
                    '%s %d %s en estado Pendiente o En Proceso, %s %d %s la fecha de compromiso vencida a la fecha de este informe.',
                    $verboRegistrar,
                    $resumen['tratamientos_pendientes'],
                    $planes,
                    $conector,
                    $resumen['tratamientos_vencidos'],
                    $verboPresentar
                )
                : sprintf(
                    '%s %d %s en estado Pendiente o En Proceso, sin fechas de compromiso vencidas a la fecha de este informe.',
                    $verboRegistrar,
                    $resumen['tratamientos_pendientes'],
                    $planes
                );
        } else {
            $oraciones[] = 'No se registran planes de tratamiento en estado Pendiente o En Proceso a la fecha de este informe.';
        }

        $oraciones[] = $resumen['total_incidentes'] > 0
            ? sprintf(
                'En el período analizado se %s %d %s.',
                $resumen['total_incidentes'] === 1 ? 'registró' : 'registraron',
                $resumen['total_incidentes'],
                $resumen['total_incidentes'] === 1 ? 'incidente' : 'incidentes'
            )
            : 'No se registraron incidentes en el período analizado.';

        if ($resumen['total_mantenimientos'] > 0) {
            $porTipo = $resumen['mantenimientos_por_tipo']
                ->map(fn ($cantidad, $tipo) => "{$cantidad} {$tipo}")
                ->implode(', ');
            $oraciones[] = sprintf(
                'Se %s %d %s en el período analizado (%s).',
                $resumen['total_mantenimientos'] === 1 ? 'ejecutó' : 'ejecutaron',
                $resumen['total_mantenimientos'],
                $resumen['total_mantenimientos'] === 1 ? 'mantenimiento' : 'mantenimientos',
                $porTipo
            );
        } else {
            $oraciones[] = 'No se ejecutaron mantenimientos en el período analizado.';
        }

        $oraciones[] = sprintf(
            'El porcentaje promedio de vida útil consumida del inventario es de %s%%.',
            number_format($resumen['promedio_vida_util_consumida'], 1)
        );

        $oraciones[] = 'Los resultados presentados corresponden a los datos registrados en el sistema para el período indicado.';

        return implode(' ', $oraciones);
    }

    // =========================================================================
    // 4.9 — Estado de Activos: Reporte de Activos por Categoría y Estado
    // =========================================================================

    public function estadoActivos(Request $request)
    {
        $datos = $this->obtenerDistribucionActivosEstado($request);
        $categorias = Categoria::orderBy('nombre_categoria')->get();
        $ubicaciones = Ubicacion::orderBy('seccion')->orderBy('aula')->get();
        $estados = ['Óptimo', 'Regular', 'Obsoleto'];

        return view('informes.estado-activos', compact('datos', 'categorias', 'ubicaciones', 'estados'));
    }

    public function estadoActivosPdf(Request $request)
    {
        $datos = $this->obtenerDistribucionActivosEstado($request);

        return $this->exportService->exportarPdf(
            'informes.pdf.estado-activos',
            ['datos' => $datos, 'filtros' => $request->all()],
            'reporte-activos-por-categoria-y-estado'
        );
    }

    public function estadoActivosExcel(Request $request)
    {
        $datos = $this->obtenerDistribucionActivosEstado($request);

        $encabezados = ['#', 'Categoría', 'Óptimo', 'Regular', 'Obsoleto', 'Total'];
        $filas = array_map(function ($f) {
            return [
                $f['numero'],
                $f['categoria'],
                $f['optimo'],
                $f['regular'],
                $f['obsoleto'],
                $f['total'],
            ];
        }, $datos['filas']);

        // Fila de totales generales
        $filas[] = [
            '',
            'TOTAL GENERAL',
            $datos['totales']['optimo'],
            $datos['totales']['regular'],
            $datos['totales']['obsoleto'],
            $datos['totales']['total'],
        ];

        return $this->exportService->exportarExcel($filas, $encabezados, 'reporte-activos-por-categoria-y-estado', 'Distribución de Activos por Categoría y Estado');
    }

    public function clasificarEstadoActivo(?string $estado): string
    {
        $est = mb_strtolower(trim($estado ?? ''));
        if (str_contains($est, 'inoperativo') || str_contains($est, 'baja') || str_contains($est, 'obsolet') || str_contains($est, 'cambio') || str_contains($est, 'dañad')) {
            return 'Obsoleto';
        }
        if (str_contains($est, 'mantenimiento') || str_contains($est, 'regular') || str_contains($est, 'antiguo') || str_contains($est, 'revis') || str_contains($est, 'medio')) {
            return 'Regular';
        }
        return 'Óptimo';
    }

    private function obtenerDistribucionActivosEstado(Request $request): array
    {
        $query = ActivoTecnologico::query()->with(['categoria', 'ubicacion']);

        if ($request->filled('fk_id_categoria')) {
            $query->where('fk_id_categoria', $request->input('fk_id_categoria'));
        }

        if ($request->filled('fk_id_ubicacion')) {
            $query->where('fk_id_ubicacion', $request->input('fk_id_ubicacion'));
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_adquisicion', '>=', $request->input('fecha_inicio'));
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_adquisicion', '<=', $request->input('fecha_fin'));
        }

        $activos = $query->get();

        $filtroEstado = $request->input('estado');
        if ($filtroEstado) {
            $activos = $activos->filter(function ($a) use ($filtroEstado) {
                return $this->clasificarEstadoActivo($a->estado_operativo) === $filtroEstado;
            })->values();
        }

        $todasCategorias = Categoria::orderBy('nombre_categoria')->get();
        if ($request->filled('fk_id_categoria')) {
            $categoriasReporte = $todasCategorias->where('id_categoria', $request->input('fk_id_categoria'));
        } else {
            $categoriasReporte = $todasCategorias;
        }

        $activosPorCat = $activos->groupBy('fk_id_categoria');

        $filas = [];
        $totalGeneral = 0;
        $totalOptimo = 0;
        $totalRegular = 0;
        $totalObsoleto = 0;
        $num = 1;

        foreach ($categoriasReporte as $cat) {
            $grupo = $activosPorCat->get($cat->id_categoria, collect());

            $cantOptimo = 0;
            $cantRegular = 0;
            $cantObsoleto = 0;

            foreach ($grupo as $activo) {
                $estadoClasif = $this->clasificarEstadoActivo($activo->estado_operativo);
                if ($estadoClasif === 'Óptimo') {
                    $cantOptimo++;
                } elseif ($estadoClasif === 'Regular') {
                    $cantRegular++;
                } else {
                    $cantObsoleto++;
                }
            }

            $totalCat = $cantOptimo + $cantRegular + $cantObsoleto;

            $filas[] = [
                'numero' => $num++,
                'id_categoria' => $cat->id_categoria,
                'categoria' => $cat->nombre_categoria,
                'optimo' => $cantOptimo,
                'regular' => $cantRegular,
                'obsoleto' => $cantObsoleto,
                'total' => $totalCat,
            ];

            $totalOptimo += $cantOptimo;
            $totalRegular += $cantRegular;
            $totalObsoleto += $cantObsoleto;
            $totalGeneral += $totalCat;
        }

        return [
            'filas' => $filas,
            'totales' => [
                'total' => $totalGeneral,
                'optimo' => $totalOptimo,
                'regular' => $totalRegular,
                'obsoleto' => $totalObsoleto,
            ],
            'chart' => [
                'labels' => array_column($filas, 'categoria'),
                'optimo' => array_column($filas, 'optimo'),
                'regular' => array_column($filas, 'regular'),
                'obsoleto' => array_column($filas, 'obsoleto'),
            ],
        ];
    }

    // =========================================================================
    // 4.10 — Reporte de Activos Dados de Baja
    // =========================================================================
    //
    // 'estado_operativo' = 'De Baja' es la condición formal de baja, distinta
    // de los estados técnicos Óptimo/Regular/Obsoleto que usa el reporte 4.9
    // (un activo Obsoleto puede seguir en servicio). Este reporte consulta
    // exclusivamente esa condición y es independiente del de Estado de Activos.

    public function activosDeBaja(Request $request)
    {
        $activos = $this->obtenerActivosDeBaja($request)->get();
        $resumen = $this->resumirActivosDeBaja($activos);

        $categorias = Categoria::orderBy('nombre_categoria')->get();

        return view('informes.activos-baja', [
            'activos' => $activos,
            'resumen' => $resumen,
            'categorias' => $categorias,
            'motivos' => ActivoTecnologicoController::MOTIVOS_BAJA,
        ]);
    }

    public function activosDeBajaPdf(Request $request)
    {
        $activos = $this->obtenerActivosDeBaja($request)->get();
        $resumen = $this->resumirActivosDeBaja($activos);

        return $this->exportService->exportarPdf('informes.pdf.activos-baja', [
            'activos' => $activos,
            'resumen' => $resumen,
            'filtros' => $request->all(),
        ], 'reporte-activos-dados-de-baja');
    }

    public function activosDeBajaExcel(Request $request)
    {
        $activos = $this->obtenerActivosDeBaja($request)->get();

        $encabezados = ['Código', 'Categoría', 'Marca', 'Modelo', 'Última Ubicación', 'Estado Previo a la Baja', 'Fecha de Baja', 'Motivo de Baja', 'Responsable', 'Observaciones'];
        $datos = $activos->map(fn ($a) => [
            $a->codigo_patrimonial,
            $a->categoria->nombre_categoria ?? '-',
            $a->marca ?? '-',
            $a->modelo ?? '-',
            $a->ubicacion ? trim($a->ubicacion->seccion . ' ' . ($a->ubicacion->aula ?? '')) : '-',
            $a->estado_previo_baja ?? 'No especificado',
            $a->fecha_baja ? Carbon::parse($a->fecha_baja)->format('d/m/Y') : 'No especificado',
            $a->motivo_baja ?? 'No especificado',
            $a->usuarioBaja ? trim($a->usuarioBaja->nombre . ' ' . ($a->usuarioBaja->apellido ?? '')) : 'No especificado',
            $a->observaciones ?? '',
        ])->toArray();

        return $this->exportService->exportarExcel($datos, $encabezados, 'reporte-activos-dados-de-baja', 'Activos Dados de Baja');
    }

    private function obtenerActivosDeBaja(Request $request)
    {
        $query = ActivoTecnologico::where('estado_operativo', 'De Baja')
            ->with(['categoria', 'ubicacion', 'usuarioBaja']);

        if ($request->filled('buscar')) {
            $query->where('codigo_patrimonial', 'like', '%' . $request->input('buscar') . '%');
        }
        if ($request->filled('fk_id_categoria')) {
            $query->where('fk_id_categoria', $request->input('fk_id_categoria'));
        }
        if ($request->filled('motivo_baja')) {
            $query->where('motivo_baja', $request->input('motivo_baja'));
        }
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_baja', '>=', $request->input('fecha_inicio'));
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_baja', '<=', $request->input('fecha_fin'));
        }

        return $query->orderByDesc('fecha_baja')->orderByDesc('id_activo');
    }

    private function resumirActivosDeBaja($activos): array
    {
        $porCategoria = $activos->groupBy(fn ($a) => $a->categoria->nombre_categoria ?? 'Sin categoría')
            ->map->count()
            ->sortDesc();

        $porMotivo = $activos->groupBy(fn ($a) => $a->motivo_baja ?? 'No especificado')
            ->map->count()
            ->sortDesc();

        return [
            'total' => $activos->count(),
            'por_categoria' => $porCategoria,
            'por_motivo' => $porMotivo,
            'con_motivo_registrado' => $activos->whereNotNull('motivo_baja')->count(),
        ];
    }

    // =========================================================================
    // 4.11 — Historial de Tratamiento (sugerido vs. ejecutado)
    // =========================================================================
    //
    // Sin tabla de historial propia: cada evaluación ya genera su propio
    // PlanTratamientoRiesgo y nunca se sobreescribe (FASE 0 de esta ronda), así
    // que el historial completo de un activo sale de recorrer sus evaluaciones
    // (vigentes e históricas) con el tratamiento asociado a cada una.

    public function historialTratamiento(Request $request)
    {
        $activos = ActivoTecnologico::orderBy('nombre_equipo')->get(['id_activo', 'nombre_equipo', 'codigo_patrimonial']);
        $filas = [];

        if ($request->filled('fk_id_activo')) {
            $filas = $this->obtenerHistorialTratamiento($request);
        }

        return view('informes.historial-tratamiento', compact('activos', 'filas'));
    }

    private function obtenerHistorialTratamiento(Request $request)
    {
        $query = EvaluacionRiesgo::with(['planesTratamiento', 'modoFalla'])
            ->where('fk_id_activo', $request->input('fk_id_activo'));

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_evaluacion', '>=', $request->input('fecha_inicio'));
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_evaluacion', '<=', $request->input('fecha_fin'));
        }

        return $query->orderByDesc('fecha_evaluacion')
            ->orderByDesc('id_evaluacion')
            ->get()
            ->map(function ($evaluacion) {
                $tratamiento = $evaluacion->planesTratamiento->first();

                return [
                    'fecha_evaluacion' => $evaluacion->fecha_evaluacion,
                    'nivel_riesgo' => $evaluacion->nivel_riesgo,
                    'modo_falla' => $evaluacion->modoFalla->nombre_modo_falla ?? '-',
                    'estrategia_sugerida' => $tratamiento->estrategia_sugerida ?? null,
                    'accion_sugerida' => $tratamiento->accion_sugerida ?? null,
                    'estrategia_aplicada' => $tratamiento->estrategia ?? null,
                    'resultado_real' => $this->resolverResultadoRealTratamiento($tratamiento),
                ];
            });
    }

    /**
     * "Resultado real" sin campo nuevo: para 'Mitigar' vive en
     * descripcion_trabajo del Mantenimiento vinculado (vía
     * id_evaluacion_origen) una vez 'Finalizado'; para las estrategias que
     * nunca generan Mantenimiento, vive en observacion_cierre del propio
     * Tratamiento.
     */
    private function resolverResultadoRealTratamiento(?PlanTratamientoRiesgo $tratamiento): string
    {
        if (!$tratamiento) {
            return 'Sin tratamiento registrado';
        }

        if ($tratamiento->estrategia === 'Mitigar') {
            $mantenimiento = Mantenimiento::where('id_evaluacion_origen', $tratamiento->fk_id_evaluacion)
                ->orderByDesc('id_mantenimiento')
                ->first();

            if ($mantenimiento && in_array($mantenimiento->estado_mantenimiento, ['Finalizado', 'Completado'], true)) {
                return $mantenimiento->descripcion_trabajo ?? 'Finalizado sin descripción registrada';
            }

            return 'En ejecución';
        }

        if ($tratamiento->estado_tratamiento !== 'Completado') {
            return 'En ejecución';
        }

        return $tratamiento->observacion_cierre ?? 'Sin observación registrada';
    }
}
