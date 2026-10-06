<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AuditoriaInternaExport;
use App\Http\Controllers\Controller;
use App\Models\ActivoTecnologico;
use App\Models\AuditoriaInterna;
use App\Models\AuditoriaMuestra;
use App\Models\HallazgoAuditoria;
use App\Models\Incidente;
use App\Models\Log;
use App\Models\Mantenimiento;
use App\Models\Usuario;
use App\Services\AuditoriaInternaService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Asistente de Auditoría Interna de TI (Fase 4). Autorización en línea
 * (no Policies de Laravel): el resto del sistema controla acceso con
 * middleware 'modulo:X' + métodos isAdmin()/isSoporte()/isDirector() en el
 * propio controlador (ver ProcesoRiesgoController, UsuarioController); se
 * mantiene esa misma convención aquí en vez de introducir un mecanismo de
 * autorización nuevo que el resto del proyecto no usa.
 *
 * Mapeo de roles §6 del pliego (el sistema no tiene un rol "Auditor" propio):
 * Auditor y Administrador = rol Administrador (isAdmin), Encargado de TI =
 * rol Soporte (isSoporte), Directora = rol Director (isDirector).
 */
class AuditoriaInternaController extends Controller
{
    public function __construct(
        private AuditoriaInternaService $servicio,
        private ExportService $exportService,
    ) {
    }

    private function puedeEditar(AuditoriaInterna $auditoria): bool
    {
        return auth()->user()->isAdmin() && !$auditoria->esInmutable();
    }

    private function puedeComentar(): bool
    {
        return auth()->user()->isSoporte();
    }

    public function index()
    {
        $auditorias = AuditoriaInterna::with('auditor')->orderByDesc('gestion')->orderByDesc('id')->get();

        return view('auditoria-interna.index', compact('auditorias'));
    }

    public function create()
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Solo el Auditor/Administrador puede crear una nueva auditoría.');

        $anteriorAprobada = $this->servicio->ultimaAuditoriaAprobada();
        $usuarios = Usuario::orderBy('nombre')->get();

        return view('auditoria-interna.crear', compact('anteriorAprobada', 'usuarios'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $data = $request->validate([
            'gestion' => 'required|integer|min:2000|max:2100',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'fecha_corte' => 'required|date',
            'participantes' => 'nullable|string',
            'fk_id_responsable_ti' => 'nullable|integer|exists:usuarios,id_usuario',
            'fk_id_director' => 'nullable|integer|exists:usuarios,id_usuario',
        ]);

        $anteriorAprobada = $this->servicio->ultimaAuditoriaAprobada();

        $auditoria = AuditoriaInterna::create(array_merge($data, [
            'codigo' => $this->servicio->generarCodigoAuditoria((int) $data['gestion']),
            'estado' => 'BORRADOR',
            'fk_id_auditor' => auth()->id(),
            'fk_id_auditoria_anterior' => $anteriorAprobada?->id,
        ]));

        // Objetivo, alcance y antecedentes se generan automáticamente por PLN
        // desde el primer momento (no se piden a mano en el formulario de
        // creación); el auditor los revisa y ajusta en la pestaña "Datos
        // Generales" — nunca quedan vacíos ni exigen redactar texto libre.
        $auditoria->update($this->servicio->generarTextosAutomaticos($auditoria));

        return redirect()->route('admin.auditoria-interna.show', $auditoria->id)
            ->with('success', 'Auditoría creada como borrador: ' . $auditoria->codigo . '. Los textos se generaron automáticamente; puedes editarlos y regenerarlos en cualquier momento.');
    }

    /**
     * Botón "Generar con PLN": regenera los 7 campos narrativos a partir del
     * estado actual de la auditoría (muestra, indicadores, semáforo,
     * hallazgos). El auditor conserva el control: solo rellena los campos,
     * quedan editables y no se guardan hasta este punto.
     */
    public function generarTextos(int $id)
    {
        $auditoria = AuditoriaInterna::with(['muestra', 'hallazgos', 'auditoriaAnterior'])->findOrFail($id);
        abort_unless($this->puedeEditar($auditoria), 403);

        $auditoria->update($this->servicio->generarTextosAutomaticos($auditoria));

        return redirect()->route('admin.auditoria-interna.show', $auditoria->id)
            ->with('success', 'Textos generados automáticamente a partir de los indicadores del sistema. Puedes editarlos antes de aprobar.');
    }

    public function show(int $id)
    {
        $auditoria = AuditoriaInterna::with(['auditor', 'responsableTi', 'director', 'muestra.activo', 'hallazgos', 'auditoriaAnterior'])->findOrFail($id);

        $indicadores = $this->servicio->calcularIndicadores($auditoria);
        $semaforo = $this->servicio->semaforoPorApartado($auditoria);
        $opinionCalculada = $this->servicio->opinionGeneral($auditoria);
        $seguimientoAnterior = $this->servicio->cargarSeguimientoAnterior($auditoria);
        $criterios = config('auditoria.criterios');
        $usuarios = Usuario::orderBy('nombre')->get();

        $puedeEditar = $this->puedeEditar($auditoria);
        $puedeComentar = $this->puedeComentar();

        return view('auditoria-interna.wizard', compact(
            'auditoria', 'indicadores', 'semaforo', 'opinionCalculada',
            'seguimientoAnterior', 'criterios', 'usuarios', 'puedeEditar', 'puedeComentar'
        ));
    }

    public function update(Request $request, int $id)
    {
        $auditoria = AuditoriaInterna::findOrFail($id);
        abort_unless($this->puedeEditar($auditoria), 403, 'La auditoría no admite edición (aprobada o sin permisos).');

        $data = $request->validate([
            'objetivo' => 'nullable|string',
            'alcance' => 'nullable|string',
            'antecedentes' => 'nullable|string',
            'metodologia' => 'nullable|string',
            'limitaciones' => 'nullable|string',
            'participantes' => 'nullable|string',
            'buenas_practicas' => 'nullable|string',
            'conclusiones' => 'nullable|string',
            'fk_id_responsable_ti' => 'nullable|integer|exists:usuarios,id_usuario',
            'fk_id_director' => 'nullable|integer|exists:usuarios,id_usuario',
        ]);

        $auditoria->update($data);

        return redirect()->route('admin.auditoria-interna.show', $auditoria->id)->with('success', 'Datos actualizados.');
    }

    public function generarMuestra(int $id)
    {
        $auditoria = AuditoriaInterna::findOrFail($id);
        abort_unless($this->puedeEditar($auditoria), 403);

        try {
            $this->servicio->generarMuestra($auditoria);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.auditoria-interna.show', $auditoria->id)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.auditoria-interna.show', $auditoria->id)->with('success', 'Muestra generada.');
    }

    public function verificarMuestra(Request $request, int $id, int $muestraId)
    {
        $auditoria = AuditoriaInterna::findOrFail($id);
        $item = AuditoriaMuestra::where('fk_id_auditoria', $auditoria->id)->findOrFail($muestraId);
        abort_unless($this->puedeEditar($auditoria), 403);

        $data = $request->validate([
            'encontrado' => 'required|boolean',
            'ubicacion_coincide' => 'required|boolean',
            'estado_coincide' => 'required|boolean',
            'observacion' => 'nullable|string',
        ]);

        $item->update(array_merge($data, [
            'fk_id_verificado_por' => auth()->id(),
            'fecha_verificacion' => now(),
        ]));

        return redirect()->route('admin.auditoria-interna.show', $auditoria->id)->with('success', 'Verificación registrada.');
    }

    public function hallazgoStore(Request $request, int $id)
    {
        $auditoria = AuditoriaInterna::findOrFail($id);
        abort_unless($this->puedeEditar($auditoria), 403);

        $data = $request->validate([
            'apartado' => 'required|string|in:' . implode(',', HallazgoAuditoria::APARTADOS),
            'requerimiento_ref' => 'nullable|integer|min:1|max:255',
            'condicion' => 'required|string',
            'criterio' => 'required|string',
            'causa' => 'required|string',
            'efecto' => 'required|string',
            'evidencia' => 'required|string',
            'recomendacion' => 'required|string',
            'clasificacion' => 'required|string|in:' . implode(',', HallazgoAuditoria::CLASIFICACIONES),
            'responsable' => 'required|string',
            'fecha_compromiso' => 'nullable|date',
            'activos' => 'nullable|array',
            'activos.*' => 'integer|exists:activos_tecnologicos,id_activo',
        ]);

        $hallazgo = HallazgoAuditoria::create(array_merge(
            collect($data)->except('activos')->all(),
            [
                'fk_id_auditoria' => $auditoria->id,
                'codigo' => $this->servicio->generarCodigoHallazgo($auditoria),
            ]
        ));

        if (!empty($data['activos'])) {
            $hallazgo->activos()->sync($data['activos']);
            $hallazgo->update(['clasificacion_sugerida' => $this->servicio->sugerirClasificacion($hallazgo)]);
        }

        return redirect()->route('admin.auditoria-interna.show', $auditoria->id)->with('success', 'Hallazgo registrado: ' . $hallazgo->codigo);
    }

    public function hallazgoUpdate(Request $request, int $hallazgoId)
    {
        $hallazgo = HallazgoAuditoria::findOrFail($hallazgoId);
        $auditoria = $hallazgo->auditoria;

        if ($this->puedeEditar($auditoria)) {
            $data = $request->validate([
                'clasificacion' => 'required|string|in:' . implode(',', HallazgoAuditoria::CLASIFICACIONES),
                'responsable' => 'required|string',
                'fecha_compromiso' => 'nullable|date',
                'estado' => 'required|string|in:' . implode(',', HallazgoAuditoria::ESTADOS),
            ]);
            $hallazgo->update($data);
        } elseif ($this->puedeComentar()) {
            $data = $request->validate([
                'comentario_area' => 'nullable|string',
                'estado' => 'required|string|in:' . implode(',', HallazgoAuditoria::ESTADOS),
            ]);
            $hallazgo->update(array_merge($data, [
                'fecha_cierre' => $data['estado'] === 'CERRADO' && !$hallazgo->fecha_cierre ? now() : $hallazgo->fecha_cierre,
            ]));
        } else {
            abort(403);
        }

        return redirect()->route('admin.auditoria-interna.show', $auditoria->id)->with('success', 'Hallazgo actualizado.');
    }

    /**
     * Seguimiento (pestaña 5): actualizar el estado de un hallazgo de la
     * auditoría ANTERIOR desde la pantalla de la auditoría actual.
     */
    public function seguimientoUpdate(Request $request, int $hallazgoId)
    {
        $hallazgo = HallazgoAuditoria::findOrFail($hallazgoId);
        abort_unless(auth()->user()->isAdmin() || auth()->user()->isSoporte(), 403);

        $data = $request->validate([
            'estado' => 'required|string|in:' . implode(',', HallazgoAuditoria::ESTADOS),
            'comentario_area' => 'nullable|string',
        ]);

        $hallazgo->update(array_merge($data, [
            'fecha_cierre' => $data['estado'] === 'CERRADO' && !$hallazgo->fecha_cierre ? now() : $hallazgo->fecha_cierre,
        ]));

        return back()->with('success', 'Seguimiento actualizado.');
    }

    public function enviarRevision(int $id)
    {
        $auditoria = AuditoriaInterna::findOrFail($id);
        abort_unless(auth()->user()->isAdmin() && $auditoria->estado === 'BORRADOR', 403);

        $auditoria->update(['estado' => 'EN_REVISION']);

        return redirect()->route('admin.auditoria-interna.show', $auditoria->id)->with('success', 'Auditoría enviada a revisión.');
    }

    public function aprobar(int $id)
    {
        $auditoria = AuditoriaInterna::findOrFail($id);
        abort_unless(auth()->user()->isAdmin() && !$auditoria->esInmutable(), 403);

        try {
            $this->servicio->aprobar($auditoria);
        } catch (\RuntimeException $e) {
            return redirect()->route('admin.auditoria-interna.show', $auditoria->id)->with('error', $e->getMessage());
        }

        return redirect()->route('admin.auditoria-interna.show', $auditoria->id)->with('success', 'Auditoría aprobada. El informe ya es inmutable.');
    }

    /**
     * Si la auditoría está APROBADA, el informe se construye desde
     * snapshot_indicadores (congelado por aprobar()), nunca desde datos en
     * vivo — así el documento no cambia aunque el sistema cambie después.
     */
    private function resolverDatosInforme(AuditoriaInterna $auditoria): array
    {
        if ($auditoria->esInmutable() && $auditoria->snapshot_indicadores) {
            return $auditoria->snapshot_indicadores;
        }

        return [
            'indicadores' => $this->servicio->calcularIndicadores($auditoria),
            'semaforo' => $this->servicio->semaforoPorApartado($auditoria),
            'opinion' => $this->servicio->opinionGeneral($auditoria),
            'resumen_ejecutivo' => $this->servicio->generarResumenEjecutivo($auditoria),
        ];
    }

    /**
     * Anexos A, B y C: listados completos (no solo totales) de activos,
     * mantenimientos e incidentes — cada uno es su propio anexo en el PDF.
     * Reutilizan las mismas columnas base que RA-01/02/03, sin duplicar su
     * lógica de filtros/indicadores (aquí no hay filtros: es el listado
     * íntegro del período de la auditoría).
     */
    private function anexos(AuditoriaInterna $auditoria): array
    {
        return [
            'activos' => ActivoTecnologico::with(['categoria', 'ubicacion'])->orderBy('codigo_patrimonial')->get(),
            'mantenimientos_periodo' => Mantenimiento::with('activo')
                ->whereBetween('fecha_programada', [$auditoria->fecha_inicio, $auditoria->fecha_fin])
                ->orderBy('fecha_programada')
                ->get(),
            'incidentes_periodo' => Incidente::with('activo')
                ->whereBetween('fecha_incidente', [$auditoria->fecha_inicio, $auditoria->fecha_fin])
                ->orderBy('fecha_incidente')
                ->get(),
            'logs_periodo' => Log::whereBetween('fecha_hora', [$auditoria->fecha_inicio, $auditoria->fecha_fin])
                ->orderByDesc('fecha_hora')
                ->limit(50)
                ->get(),
        ];
    }

    public function exportarPdf(int $id)
    {
        $auditoria = AuditoriaInterna::with(['auditor', 'responsableTi', 'director', 'muestra.activo', 'hallazgos', 'auditoriaAnterior'])->findOrFail($id);

        // La sección de Aprobación y Distribución debe mostrar los nombres
        // reales de quienes aprueban, no espacios en blanco: se exige
        // completarlos antes de poder generar el documento (incluso ya
        // aprobada, porque de lo contrario el PDF quedaría con esos campos
        // vacíos para siempre).
        if (!$auditoria->fk_id_responsable_ti || !$auditoria->fk_id_director) {
            return redirect()->route('admin.auditoria-interna.show', $auditoria->id)
                ->with('error', 'Antes de generar el informe, completa "Responsable de TI" y "Dirección" en la pestaña Datos Generales.');
        }

        $datos = $this->resolverDatosInforme($auditoria);
        $anexos = $this->anexos($auditoria);
        $seguimientoAnterior = $this->servicio->cargarSeguimientoAnterior($auditoria);

        Log::create([
            'id_usuario' => auth()->id() ?? (Usuario::first()->id_usuario ?? 1),
            'accion_realizada' => 'Exportación PDF — Informe General de Auditoría Interna de TI',
            'tabla_afectada' => 'auditorias_internas',
            'registro_id' => $auditoria->id,
            'fecha_hora' => now(),
            'valor_anterior' => null,
            'valor_nuevo' => $auditoria->codigo,
        ]);

        return $this->exportService->exportarPdf(
            'informes.pdf.auditoria-interna',
            compact('auditoria', 'datos', 'anexos', 'seguimientoAnterior'),
            'informe-general-auditoria-interna-' . $auditoria->codigo
        );
    }

    public function exportarExcel(int $id)
    {
        $auditoria = AuditoriaInterna::with(['muestra.activo', 'hallazgos'])->findOrFail($id);
        $datos = $this->resolverDatosInforme($auditoria);

        Log::create([
            'id_usuario' => auth()->id() ?? (Usuario::first()->id_usuario ?? 1),
            'accion_realizada' => 'Exportación Excel — Informe General de Auditoría Interna de TI',
            'tabla_afectada' => 'auditorias_internas',
            'registro_id' => $auditoria->id,
            'fecha_hora' => now(),
            'valor_anterior' => null,
            'valor_nuevo' => $auditoria->codigo,
        ]);

        return Excel::download(new AuditoriaInternaExport($auditoria, $datos), 'informe-general-auditoria-interna-' . $auditoria->codigo . '.xlsx');
    }
}
