<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\Incidente;
use App\Models\Log;
use App\Models\Mantenimiento;
use App\Models\Rol;
use App\Models\Ubicacion;
use App\Models\Usuario;
use App\Services\ExportService;
use App\Services\MotorFmeaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Reportes RA-01..RA-07 para el auditor de TI (Fase 1 del módulo de
 * Auditoría Interna de TI). Documentos formales con fecha de corte, pensados
 * para entrega al auditor — no duplican los listados operativos de cada
 * módulo, reutilizan sus consultas/scopes (ver docs/bitacora-tecnica.md §4.5).
 *
 * Regla de diseño: el riesgo no es objeto de auditoría. El nivel de riesgo
 * del motor FMEA solo se usa para RA-04 (clasificación informativa) y para
 * priorizar la muestra del Informe General (Fase 3), nunca como hallazgo.
 *
 * Vive en un controlador propio (no en InformesController, que ya supera
 * 1000 líneas) pero comparte config('auditoria.php') y el mismo
 * ExportService/MotorFmeaService del resto del módulo de Informes.
 */
class AuditoriaReportesController extends Controller
{
    private const MESES_ABREV = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
    ];

    private const PERMISOS_MODULO = [
        'permiso_dashboard', 'permiso_activos', 'permiso_incidentes',
        'permiso_riesgos', 'permiso_mantenimiento', 'permiso_informes', 'permiso_usuarios',
    ];

    public function __construct(
        private MotorFmeaService $motor,
        private ExportService $exportService,
    ) {
    }

    private function filtros(Request $request): array
    {
        return [
            'gestion' => (int) $request->input('gestion', now()->year),
            'fecha_corte' => $request->input('fecha_corte', now()->toDateString()),
            'fk_id_categoria' => $request->filled('fk_id_categoria') ? (int) $request->input('fk_id_categoria') : null,
            'fk_id_ubicacion' => $request->filled('fk_id_ubicacion') ? (int) $request->input('fk_id_ubicacion') : null,
        ];
    }

    private function registrarLog(string $accion, array $filtros = []): void
    {
        Log::create([
            'id_usuario' => auth()->id() ?? (Usuario::first()->id_usuario ?? 1),
            'accion_realizada' => $accion,
            'tabla_afectada' => 'auditoria_ti',
            'registro_id' => null,
            'fecha_hora' => now(),
            'valor_anterior' => null,
            'valor_nuevo' => empty($filtros) ? null : json_encode($filtros),
        ]);
    }

    // =========================================================================
    // RA-01 — Inventario general de activos tecnológicos (Req. 5)
    // =========================================================================

    private function obtenerInventarioRA01(array $filtros): array
    {
        $query = ActivoTecnologico::with(['categoria', 'ubicacion'])
            ->whereDate('fecha_adquisicion', '<=', $filtros['fecha_corte']);

        if ($filtros['fk_id_categoria']) {
            $query->where('fk_id_categoria', $filtros['fk_id_categoria']);
        }
        if ($filtros['fk_id_ubicacion']) {
            $query->where('fk_id_ubicacion', $filtros['fk_id_ubicacion']);
        }

        $activos = $query->get()->map(function ($activo) {
            $activo->vida_util = $this->motor->calcularVidaUtilRemanente($activo);

            return $activo;
        });

        return [
            'activos' => $activos,
            'total' => $activos->count(),
            'por_categoria' => $activos->groupBy(fn ($a) => $a->categoria->nombre_categoria ?? 'Sin categoría')->map->count(),
            'por_ubicacion' => $activos->groupBy(fn ($a) => $a->ubicacion ? trim($a->ubicacion->seccion . ' ' . ($a->ubicacion->aula ?? '')) : 'Sin ubicación')->map->count(),
            'por_estado' => $activos->groupBy('estado_operativo')->map->count(),
            'alertas' => [
                // fk_id_ubicacion es NOT NULL en la BD actual: este conteo
                // documenta la regla pero hoy siempre da 0 (ver bitácora/plan).
                'sin_ubicacion' => $activos->whereNull('fk_id_ubicacion')->count(),
                'vida_util_vencida' => $activos->filter(fn ($a) => ($a->vida_util['meses_restantes'] ?? 1) <= 0)->count(),
            ],
        ];
    }

    public function ra01Inventario(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerInventarioRA01($filtros);
        $categorias = Categoria::orderBy('nombre_categoria')->get();
        $ubicaciones = Ubicacion::orderBy('seccion')->orderBy('aula')->get();

        $this->registrarLog('Generación de Reporte RA-01 (Inventario General)', $filtros);

        return view('informes.auditoria-ti.ra-01-inventario', compact('datos', 'filtros', 'categorias', 'ubicaciones'));
    }

    public function ra01InventarioPdf(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerInventarioRA01($filtros);

        $this->registrarLog('Exportación PDF — Reporte RA-01 (Inventario General)', $filtros);

        return $this->exportService->exportarPdf('informes.pdf.auditoria-ti.ra-01-inventario', compact('datos', 'filtros'), 'ra-01-inventario-general');
    }

    public function ra01InventarioExcel(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerInventarioRA01($filtros);

        $encabezados = ['Código', 'Categoría', 'Ubicación', 'Estado Operativo', 'Etapa Ciclo de Vida', 'Fecha Adquisición', 'Meses Restantes Vida Útil'];
        $filas = $datos['activos']->map(fn ($a) => [
            $a->codigo_patrimonial,
            $a->categoria->nombre_categoria ?? '-',
            $a->ubicacion ? trim($a->ubicacion->seccion . ' ' . ($a->ubicacion->aula ?? '')) : '-',
            $a->estado_operativo,
            $a->etapa_ciclo_vida,
            $a->fecha_adquisicion ? Carbon::parse($a->fecha_adquisicion)->format('d/m/Y') : '-',
            $a->vida_util['meses_restantes'],
        ])->toArray();

        $this->registrarLog('Exportación Excel — Reporte RA-01 (Inventario General)', $filtros);

        return $this->exportService->exportarExcel($filas, $encabezados, 'ra-01-inventario-general', 'RA-01 Inventario General de Activos Tecnológicos');
    }

    // =========================================================================
    // RA-02 — Plan y ejecución de mantenimiento (Req. 18)
    // =========================================================================

    private function obtenerMantenimientoRA02(array $filtros): array
    {
        $inicio = Carbon::create($filtros['gestion'], 1, 1)->startOfYear();
        $fin = Carbon::create($filtros['gestion'], 1, 1)->endOfYear();

        $query = Mantenimiento::with(['activo.categoria'])
            ->whereBetween('fecha_programada', [$inicio->toDateString(), $fin->toDateString()]);

        if ($filtros['fk_id_categoria']) {
            $query->whereHas('activo', fn ($q) => $q->where('fk_id_categoria', $filtros['fk_id_categoria']));
        }

        $mantenimientos = $query->get();

        $programados = $mantenimientos->count();
        $ejecutados = $mantenimientos->whereNotNull('fecha_ejecucion')->count();
        $preventivos = $mantenimientos->where('tipo_mantenimiento', 'Preventivo')->count();

        $vencidos = $mantenimientos->filter(function ($m) use ($filtros) {
            return is_null($m->fecha_ejecucion)
                && Carbon::parse($m->fecha_programada)->lt(Carbon::parse($filtros['fecha_corte']));
        })->values();

        $cronograma = [];
        foreach (self::MESES_ABREV as $mes => $etiqueta) {
            $delMes = $mantenimientos->filter(fn ($m) => Carbon::parse($m->fecha_programada)->month === $mes);
            $cronograma[] = [
                'mes' => $etiqueta,
                'programados' => $delMes->count(),
                'ejecutados' => $delMes->whereNotNull('fecha_ejecucion')->count(),
            ];
        }

        return [
            'mantenimientos' => $mantenimientos,
            'programados' => $programados,
            'ejecutados' => $ejecutados,
            'cumplimiento_pct' => $programados > 0 ? round(($ejecutados / $programados) * 100, 1) : null,
            'preventivo_pct' => $mantenimientos->count() > 0 ? round(($preventivos / $mantenimientos->count()) * 100, 1) : null,
            'vencidos' => $vencidos,
            'cronograma' => $cronograma,
        ];
    }

    public function ra02Mantenimiento(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerMantenimientoRA02($filtros);
        $categorias = Categoria::orderBy('nombre_categoria')->get();

        $this->registrarLog('Generación de Reporte RA-02 (Plan y Ejecución de Mantenimiento)', $filtros);

        return view('informes.auditoria-ti.ra-02-mantenimiento', compact('datos', 'filtros', 'categorias'));
    }

    public function ra02MantenimientoPdf(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerMantenimientoRA02($filtros);

        $this->registrarLog('Exportación PDF — Reporte RA-02 (Plan y Ejecución de Mantenimiento)', $filtros);

        return $this->exportService->exportarPdf('informes.pdf.auditoria-ti.ra-02-mantenimiento', compact('datos', 'filtros'), 'ra-02-plan-mantenimiento');
    }

    public function ra02MantenimientoExcel(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerMantenimientoRA02($filtros);

        $encabezados = ['Activo', 'Categoría', 'Tipo', 'Fecha Programada', 'Fecha Ejecución', 'Estado'];
        $filas = $datos['mantenimientos']->map(fn ($m) => [
            $m->activo->nombre_equipo ?? '-',
            $m->activo->categoria->nombre_categoria ?? '-',
            $m->tipo_mantenimiento,
            Carbon::parse($m->fecha_programada)->format('d/m/Y'),
            $m->fecha_ejecucion ? Carbon::parse($m->fecha_ejecucion)->format('d/m/Y') : 'No ejecutado',
            $m->estado_mantenimiento,
        ])->toArray();

        $this->registrarLog('Exportación Excel — Reporte RA-02 (Plan y Ejecución de Mantenimiento)', $filtros);

        return $this->exportService->exportarExcel($filas, $encabezados, 'ra-02-plan-mantenimiento', 'RA-02 Plan y Ejecución de Mantenimiento');
    }

    // =========================================================================
    // RA-03 — Registro de incidentes de la gestión (Req. 21)
    // =========================================================================

    private function obtenerIncidentesRA03(array $filtros): array
    {
        $inicio = Carbon::create($filtros['gestion'], 1, 1)->startOfYear();
        $fin = Carbon::create($filtros['gestion'], 1, 1)->endOfYear();

        // No existe columna 'tipo_incidente' en el sistema actual (ver plan,
        // Fase 0): no se ofrece ese filtro.
        $incidentes = Incidente::with(['activo.categoria'])
            ->whereBetween('fecha_incidente', [$inicio, $fin])
            ->get();

        $cerrados = $incidentes->whereNotNull('fecha_cierre');
        $tmrDias = $cerrados->isNotEmpty()
            ? round($cerrados->avg(fn ($i) => Carbon::parse($i->fecha_incidente)->diffInDays(Carbon::parse($i->fecha_cierre))), 1)
            : null;

        $abiertosACorte = $incidentes->filter(function ($i) use ($filtros) {
            $cerradoAntesDeCorte = $i->fecha_cierre && Carbon::parse($i->fecha_cierre)->lte(Carbon::parse($filtros['fecha_corte']));

            return !$cerradoAntesDeCorte;
        })->count();

        return [
            'incidentes' => $incidentes,
            'total' => $incidentes->count(),
            'por_activo' => $incidentes->groupBy(fn ($i) => $i->activo->nombre_equipo ?? 'Activo eliminado')->map->count(),
            'por_categoria' => $incidentes->groupBy(fn ($i) => $i->activo->categoria->nombre_categoria ?? 'Sin categoría')->map->count(),
            'por_estado' => $incidentes->groupBy('estado_incidente')->map->count(),
            'tmr_dias' => $tmrDias,
            'abiertos_a_corte' => $abiertosACorte,
        ];
    }

    public function ra03Incidentes(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerIncidentesRA03($filtros);

        $this->registrarLog('Generación de Reporte RA-03 (Registro de Incidentes)', $filtros);

        return view('informes.auditoria-ti.ra-03-incidentes', compact('datos', 'filtros'));
    }

    public function ra03IncidentesPdf(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerIncidentesRA03($filtros);

        $this->registrarLog('Exportación PDF — Reporte RA-03 (Registro de Incidentes)', $filtros);

        return $this->exportService->exportarPdf('informes.pdf.auditoria-ti.ra-03-incidentes', compact('datos', 'filtros'), 'ra-03-registro-incidentes');
    }

    public function ra03IncidentesExcel(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerIncidentesRA03($filtros);

        $encabezados = ['Activo', 'Categoría', 'Fecha Incidente', 'Fecha Cierre', 'Estado', 'Días de Resolución'];
        $filas = $datos['incidentes']->map(fn ($i) => [
            $i->activo->nombre_equipo ?? '-',
            $i->activo->categoria->nombre_categoria ?? '-',
            Carbon::parse($i->fecha_incidente)->format('d/m/Y H:i'),
            $i->fecha_cierre ? Carbon::parse($i->fecha_cierre)->format('d/m/Y H:i') : 'Sin cerrar',
            $i->estado_incidente,
            $i->fecha_cierre ? Carbon::parse($i->fecha_incidente)->diffInDays(Carbon::parse($i->fecha_cierre)) : '-',
        ])->toArray();

        $this->registrarLog('Exportación Excel — Reporte RA-03 (Registro de Incidentes)', $filtros);

        return $this->exportService->exportarExcel($filas, $encabezados, 'ra-03-registro-incidentes', 'RA-03 Registro de Incidentes de la Gestión');
    }

    // =========================================================================
    // RA-04 — Clasificación de activos por criticidad (Req. 7, parcial)
    // =========================================================================

    private function obtenerCriticidadRA04(): array
    {
        $activos = ActivoTecnologico::with('categoria')->get();
        $total = $activos->count();

        $porDependencia = $activos->groupBy(fn ($a) => $a->categoria->nivel_dependencia ?? 'Sin definir')->map->count();
        $porNivelRiesgo = $activos->groupBy(fn ($a) => $a->nivel_riesgo ?? 'Sin evaluar')->map->count();

        $cruce = $activos->groupBy(fn ($a) => $a->categoria->nivel_dependencia ?? 'Sin definir')
            ->map(fn ($grupo) => $grupo->groupBy(fn ($a) => $a->nivel_riesgo ?? 'Sin evaluar')->map->count());

        return compact('total', 'porDependencia', 'porNivelRiesgo', 'cruce');
    }

    public function ra04Criticidad(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerCriticidadRA04();

        $this->registrarLog('Generación de Reporte RA-04 (Clasificación por Criticidad)', $filtros);

        return view('informes.auditoria-ti.ra-04-criticidad', compact('datos', 'filtros'));
    }

    public function ra04CriticidadPdf(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerCriticidadRA04();

        $this->registrarLog('Exportación PDF — Reporte RA-04 (Clasificación por Criticidad)', $filtros);

        return $this->exportService->exportarPdf('informes.pdf.auditoria-ti.ra-04-criticidad', compact('datos', 'filtros'), 'ra-04-clasificacion-criticidad');
    }

    public function ra04CriticidadExcel(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerCriticidadRA04();

        $encabezados = ['Nivel de Dependencia', 'Nivel de Riesgo', 'Total Activos'];
        $filas = [];
        foreach ($datos['cruce'] as $dependencia => $porNivel) {
            foreach ($porNivel as $nivel => $total) {
                $filas[] = [$dependencia, $nivel, $total];
            }
        }

        $this->registrarLog('Exportación Excel — Reporte RA-04 (Clasificación por Criticidad)', $filtros);

        return $this->exportService->exportarExcel($filas, $encabezados, 'ra-04-clasificacion-criticidad', 'RA-04 Clasificación de Activos por Criticidad');
    }

    // =========================================================================
    // RA-05 — Matriz de roles y permisos del sistema (Req. 9, parcial)
    // =========================================================================

    private function obtenerRolesPermisosRA05(): array
    {
        $roles = Rol::with('usuarios')->get();

        $matriz = $roles->map(function ($rol) {
            return [
                'rol' => $rol->nombre_rol,
                'total_usuarios' => $rol->usuarios->count(),
                'permisos' => collect(self::PERMISOS_MODULO)->mapWithKeys(
                    fn ($permiso) => [$permiso => $rol->usuarios->where($permiso, true)->count()]
                ),
            ];
        });

        $admins = Usuario::where('fk_id_rol', (int) config('roles.admin'))->get()->map(function ($usuario) {
            return [
                'nombre' => trim($usuario->nombre . ' ' . ($usuario->apellido ?? '')),
                'correo' => $usuario->correo,
                'estado' => $usuario->estado ? 'Activo' : 'Inactivo',
                // No existe columna de último login (ver plan, Fase 0): se
                // aproxima con la última actividad registrada en bitácora.
                'ultima_actividad' => Log::where('id_usuario', $usuario->id_usuario)->max('fecha_hora'),
            ];
        });

        return compact('matriz', 'admins');
    }

    public function ra05RolesPermisos(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerRolesPermisosRA05();

        $this->registrarLog('Generación de Reporte RA-05 (Matriz de Roles y Permisos)', $filtros);

        return view('informes.auditoria-ti.ra-05-roles-permisos', compact('datos', 'filtros'));
    }

    public function ra05RolesPermisosPdf(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerRolesPermisosRA05();

        $this->registrarLog('Exportación PDF — Reporte RA-05 (Matriz de Roles y Permisos)', $filtros);

        return $this->exportService->exportarPdf('informes.pdf.auditoria-ti.ra-05-roles-permisos', compact('datos', 'filtros'), 'ra-05-matriz-roles-permisos');
    }

    public function ra05RolesPermisosExcel(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerRolesPermisosRA05();

        $encabezados = array_merge(['Rol', 'Usuarios'], self::PERMISOS_MODULO);
        $filas = $datos['matriz']->map(function ($fila) {
            return array_merge([$fila['rol'], $fila['total_usuarios']], $fila['permisos']->values()->toArray());
        })->toArray();

        $this->registrarLog('Exportación Excel — Reporte RA-05 (Matriz de Roles y Permisos)', $filtros);

        return $this->exportService->exportarExcel($filas, $encabezados, 'ra-05-matriz-roles-permisos', 'RA-05 Matriz de Roles y Permisos del Sistema');
    }

    // =========================================================================
    // RA-06 — Hoja de verificación de activos por ubicación / CPD (Req. 19, parcial)
    // =========================================================================

    public function ra06Verificacion(Request $request)
    {
        $ubicaciones = Ubicacion::orderBy('seccion')->orderBy('aula')->get();
        $ubicacionSeleccionada = null;
        $activos = collect();

        if ($request->filled('fk_id_ubicacion')) {
            $ubicacionSeleccionada = Ubicacion::find($request->input('fk_id_ubicacion'));
            $activos = ActivoTecnologico::with('categoria')
                ->where('fk_id_ubicacion', $request->input('fk_id_ubicacion'))
                ->orderBy('nombre_equipo')
                ->get();

            $this->registrarLog('Generación de Reporte RA-06 (Hoja de Verificación Física)', ['fk_id_ubicacion' => $ubicacionSeleccionada->id_ubicacion]);
        }

        return view('informes.auditoria-ti.ra-06-verificacion', compact('ubicaciones', 'ubicacionSeleccionada', 'activos'));
    }

    public function ra06VerificacionPdf(Request $request)
    {
        $request->validate(['fk_id_ubicacion' => 'required|integer|exists:ubicaciones,id_ubicacion']);

        $ubicacionSeleccionada = Ubicacion::findOrFail($request->input('fk_id_ubicacion'));
        $activos = ActivoTecnologico::with('categoria')
            ->where('fk_id_ubicacion', $ubicacionSeleccionada->id_ubicacion)
            ->orderBy('nombre_equipo')
            ->get();

        $this->registrarLog('Exportación PDF — Reporte RA-06 (Hoja de Verificación Física)', ['fk_id_ubicacion' => $ubicacionSeleccionada->id_ubicacion]);

        return $this->exportService->exportarPdf(
            'informes.pdf.auditoria-ti.ra-06-verificacion',
            compact('ubicacionSeleccionada', 'activos'),
            'ra-06-hoja-verificacion-' . $ubicacionSeleccionada->id_ubicacion
        );
    }

    // =========================================================================
    // RA-07 — Activos de red por ubicación (Req. 20, parcial)
    // =========================================================================

    private function obtenerActivosRedRA07(): array
    {
        $categoriasRed = config('auditoria.categorias_red', []);
        $activos = collect();

        if (!empty($categoriasRed)) {
            $activos = ActivoTecnologico::with(['categoria', 'ubicacion'])
                ->whereIn('fk_id_categoria', $categoriasRed)
                ->get();
        }

        return [
            'configurado' => !empty($categoriasRed),
            'activos' => $activos,
            'por_ubicacion' => $activos->groupBy(fn ($a) => $a->ubicacion ? trim($a->ubicacion->seccion . ' ' . ($a->ubicacion->aula ?? '')) : 'Sin ubicación'),
        ];
    }

    public function ra07ActivosRed(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerActivosRedRA07();

        $this->registrarLog('Generación de Reporte RA-07 (Activos de Red por Ubicación)', $filtros);

        return view('informes.auditoria-ti.ra-07-activos-red', compact('datos', 'filtros'));
    }

    public function ra07ActivosRedPdf(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerActivosRedRA07();

        $this->registrarLog('Exportación PDF — Reporte RA-07 (Activos de Red por Ubicación)', $filtros);

        return $this->exportService->exportarPdf('informes.pdf.auditoria-ti.ra-07-activos-red', compact('datos', 'filtros'), 'ra-07-activos-de-red');
    }

    public function ra07ActivosRedExcel(Request $request)
    {
        $filtros = $this->filtros($request);
        $datos = $this->obtenerActivosRedRA07();

        $encabezados = ['Código', 'Categoría', 'Ubicación', 'Estado Operativo'];
        $filas = $datos['activos']->map(fn ($a) => [
            $a->codigo_patrimonial,
            $a->categoria->nombre_categoria ?? '-',
            $a->ubicacion ? trim($a->ubicacion->seccion . ' ' . ($a->ubicacion->aula ?? '')) : '-',
            $a->estado_operativo,
        ])->toArray();

        $this->registrarLog('Exportación Excel — Reporte RA-07 (Activos de Red por Ubicación)', $filtros);

        return $this->exportService->exportarExcel($filas, $encabezados, 'ra-07-activos-de-red', 'RA-07 Activos de Red por Ubicación');
    }
}
