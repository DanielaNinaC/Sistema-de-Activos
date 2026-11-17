<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\Ubicacion;
use App\Models\DetalleComputadora;
use App\Models\DetalleEquipoRed;
use App\Models\DetalleProyector;
use App\Exports\InventarioActivosExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Support\Catalogo;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ActivoTecnologicoController extends Controller
{
    // Estados operativos, etapas del ciclo de vida y motivos de baja viven en
    // los catálogos estados_operativos, etapas_ciclo_vida y motivos_baja.

    public function index(Request $request)
    {
        $query = ActivoTecnologico::with(['categoria', 'ubicacion']);

        if ($buscar = $request->input('buscar')) {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_patrimonial', 'like', "%{$buscar}%")
                  ->orWhere('nombre_equipo', 'like', "%{$buscar}%")
                  ->orWhere('marca', 'like', "%{$buscar}%")
                  ->orWhere('modelo', 'like', "%{$buscar}%")
                  ->orWhere('estado_operativo', 'like', "%{$buscar}%")
                  ->orWhereHas('categoria', fn($c) => $c->where('nombre_categoria', 'like', "%{$buscar}%"))
                  ->orWhereHas('ubicacion', fn($u) => $u->where('seccion', 'like', "%{$buscar}%")->orWhere('aula', 'like', "%{$buscar}%"));
            });
        }

        $activos = $query->orderBy('id_activo')->paginate(15)->withQueryString();

        return view('activos.inventario', compact('activos'));
    }

    public function create()
    {
        $categorias = Categoria::orderBy('nombre_categoria')->get();
        $ubicaciones = Ubicacion::orderBy('seccion')->orderBy('aula')->get();

        return view('activos.registrar-activo', compact('categorias', 'ubicaciones'));
    }

    /**
     * Tipo de ficha técnica que corresponde a una categoría. Se deduce del
     * texto de su nombre (decisión de diseño existente); null si no coincide.
     */
    private function tipoDetalle(?Categoria $categoria): ?string
    {
        if (!$categoria) {
            return null;
        }

        $nombre = strtolower(trim($categoria->nombre_categoria));

        if (str_contains($nombre, 'computadora') || str_contains($nombre, 'pc') || str_contains($nombre, 'laptop')) {
            return 'computadora';
        }

        if (str_contains($nombre, 'red') || str_contains($nombre, 'servidor') || str_contains($nombre, 'switch') || str_contains($nombre, 'router') || str_contains($nombre, 'impresora') || str_contains($nombre, 'printer')) {
            return 'red';
        }

        if (str_contains($nombre, 'proyector')) {
            return 'proyector';
        }

        return null;
    }

    /** Reglas de los campos de la ficha técnica, según el tipo de categoría. */
    private function reglasDetalle(?string $tipo): array
    {
        return match ($tipo) {
            'computadora' => [
                'procesador' => 'required|string|max:255',
                'ram_gb' => 'required|integer|min:1',
                'disco_solido_gb' => 'nullable|integer|min:0',
                'disco_rigido_gb' => 'nullable|integer|min:0',
                'sistema_operativo' => 'nullable|string|max:255',
                'lector' => 'nullable',
                'parlantes' => 'nullable',
                'teclado_mouse' => 'nullable',
                'monitor' => 'nullable',
                'camara_web' => 'nullable',
            ],
            'red' => [
                'direccion_ip' => 'nullable|ip',
                'version_firmware' => 'nullable|string|max:255',
            ],
            'proyector' => [
                'tipo_cable' => 'nullable|string|max:255',
                'calidad_imagen' => 'nullable|integer|min:0',
            ],
            default => [],
        };
    }

    private const CAMPOS_DETALLE = [
        'procesador', 'ram_gb', 'disco_solido_gb', 'disco_rigido_gb', 'sistema_operativo',
        'lector', 'parlantes', 'teclado_mouse', 'monitor', 'camara_web',
        'direccion_ip', 'version_firmware', 'tipo_cable', 'calidad_imagen',
    ];

    /** Guarda (crea o actualiza) la ficha técnica del activo según su tipo. */
    private function guardarDetalle(?string $tipo, ActivoTecnologico $activo, array $detalle, Request $request): void
    {
        $clave = ['fk_id_activo' => $activo->id_activo];

        match ($tipo) {
            'computadora' => DetalleComputadora::updateOrCreate($clave, $detalle + [
                'lector' => $request->boolean('lector'),
                'parlantes' => $request->boolean('parlantes'),
                'teclado_mouse' => $request->boolean('teclado_mouse'),
                'monitor' => $request->boolean('monitor'),
                'camara_web' => $request->boolean('camara_web'),
            ]),
            'red' => DetalleEquipoRed::updateOrCreate($clave, $detalle),
            'proyector' => DetalleProyector::updateOrCreate($clave, $detalle),
            default => null,
        };
    }

    /**
     * Si la categoría no tiene ficha técnica específica pero el usuario llenó
     * campos técnicos, esos datos no se guardan: se le avisa en vez de
     * descartarlos en silencio.
     */
    private function avisoDetalleNoGuardado(?string $tipo, Request $request): string
    {
        if ($tipo !== null) {
            return '';
        }

        foreach (self::CAMPOS_DETALLE as $campo) {
            if ($request->filled($campo)) {
                return ' Aviso: los datos técnicos ingresados (RAM, IP, etc.) no se guardaron porque la categoría seleccionada no tiene ficha técnica específica.';
            }
        }

        return '';
    }

    public function store(Request $request)
    {
        $reglas = [
            'fk_id_categoria' => 'required|integer|exists:categorias,id_categoria',
            'fk_id_ubicacion' => 'required|integer|exists:ubicaciones,id_ubicacion',
            'codigo_patrimonial' => 'required|string|max:255|unique:activos_tecnologicos,codigo_patrimonial',
            'nombre_equipo' => 'required|string|max:255',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            // Obligatoria: de ella depende la antigüedad y el desgaste del activo en el cálculo de riesgo.
            'fecha_adquisicion' => 'required|date|before_or_equal:today',
            'estado_operativo' => ['required', 'string', Rule::in(Catalogo::nombres('estados_operativos'))],
            'etapa_ciclo_vida' => ['required', 'string', Rule::in(Catalogo::nombres('etapas_ciclo_vida'))],
            'observaciones' => 'nullable|string',
        ];

        // Se valida todo (activo + ficha técnica) antes de guardar nada: si algún
        // dato técnico es inválido, el activo no queda registrado a medias.
        $tipo = $this->tipoDetalle(Categoria::find($request->input('fk_id_categoria')));
        $reglasDetalle = $this->reglasDetalle($tipo);
        $validado = $request->validate($reglas + $reglasDetalle);

        DB::transaction(function () use ($validado, $reglas, $reglasDetalle, $tipo, $request) {
            $activo = ActivoTecnologico::create(Arr::only($validado, array_keys($reglas)));
            $this->guardarDetalle($tipo, $activo, Arr::only($validado, array_keys($reglasDetalle)), $request);
        });

        return redirect()->route('admin.activos.inventario')
            ->with('success', 'Activo tecnológico creado con éxito.' . $this->avisoDetalleNoGuardado($tipo, $request));
    }

    public function categorias(Request $request)
    {
        $search = $request->input('search');
        $query = Categoria::withCount('activos')->orderBy('id_categoria');

        if ($search) {
            $query->where('nombre_categoria', 'like', "%{$search}%");
        }

        $categorias = $query->get();

        return view('activos.categorias', compact('categorias', 'search'));
    }

    public function storeCategoria(Request $request)
    {
        $data = $request->validate([
            'nombre_categoria' => 'required|string|max:255|unique:categorias,nombre_categoria',
            'vida_util_valor' => 'nullable|integer|min:1',
            'vida_util_unidad' => 'nullable|in:meses,anos',
            'nivel_dependencia' => ['required', Rule::in(Catalogo::nombres('niveles_dependencia'))],
            'justificacion_dependencia' => 'nullable|string',
        ]);

        // Convertir a meses si se ingresó en años
        $vidaUtilMeses = null;
        if (!empty($data['vida_util_valor'])) {
            if (($data['vida_util_unidad'] ?? 'meses') === 'anos') {
                $vidaUtilMeses = $data['vida_util_valor'] * 12;
            } else {
                $vidaUtilMeses = $data['vida_util_valor'];
            }
        }

        Categoria::create([
            'nombre_categoria' => $data['nombre_categoria'],
            'vida_util_meses' => $vidaUtilMeses,
            'nivel_dependencia' => $data['nivel_dependencia'],
            'justificacion_dependencia' => $data['justificacion_dependencia'] ?? null,
        ]);

        return redirect()->route('admin.activos.categorias')->with('success', 'Categoría creada con éxito');
    }

    public function updateCategoria(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);

        $data = $request->validate([
            'nombre_categoria' => 'required|string|max:255|unique:categorias,nombre_categoria,' . $id . ',id_categoria',
            'vida_util_valor' => 'nullable|integer|min:1',
            'vida_util_unidad' => 'nullable|in:meses,anos',
            'nivel_dependencia' => ['required', Rule::in(Catalogo::nombres('niveles_dependencia'))],
            'justificacion_dependencia' => 'nullable|string',
        ]);

        $vidaUtilMeses = null;
        if (!empty($data['vida_util_valor'])) {
            if (($data['vida_util_unidad'] ?? 'meses') === 'anos') {
                $vidaUtilMeses = $data['vida_util_valor'] * 12;
            } else {
                $vidaUtilMeses = $data['vida_util_valor'];
            }
        }

        $categoria->update([
            'nombre_categoria' => $data['nombre_categoria'],
            'vida_util_meses' => $vidaUtilMeses,
            'nivel_dependencia' => $data['nivel_dependencia'],
            'justificacion_dependencia' => $data['justificacion_dependencia'] ?? null,
        ]);

        return redirect()->route('admin.activos.categorias')->with('success', 'Categoría actualizada con éxito');
    }

    public function activosPorCategoria(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);
        $buscar = $request->input('buscar');

        $query = ActivoTecnologico::with(['categoria', 'ubicacion'])
            ->where('fk_id_categoria', $id);

        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_patrimonial', 'like', "%{$buscar}%")
                  ->orWhere('nombre_equipo', 'like', "%{$buscar}%")
                  ->orWhere('marca', 'like', "%{$buscar}%")
                  ->orWhere('modelo', 'like', "%{$buscar}%")
                  ->orWhere('estado_operativo', 'like', "%{$buscar}%")
                  ->orWhereHas('ubicacion', fn($u) => $u->where('seccion', 'like', "%{$buscar}%")->orWhere('aula', 'like', "%{$buscar}%"));
            });
        }

        $activos = $query->orderBy('id_activo', 'desc')->paginate(15)->appends(['buscar' => $buscar]);

        return view('activos.categoria-equipos', compact('categoria', 'activos', 'buscar'));
    }

    public function ubicaciones(Request $request)
    {
        $search = $request->input('search');
        $query = Ubicacion::withCount('activos')->orderBy('id_ubicacion');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('seccion', 'like', "%{$search}%")
                  ->orWhere('aula', 'like', "%{$search}%");
            });
        }

        $ubicaciones = $query->get();

        return view('activos.ubicaciones', compact('ubicaciones', 'search'));
    }

    public function storeUbicacion(Request $request)
    {
        $data = $request->validate([
            'seccion' => 'required|string|max:255',
            'aula' => 'nullable|string|max:255',
        ]);

        if ($this->ubicacionDuplicada($data)) {
            return back()->withErrors(['aula' => 'Ya existe una ubicación con esa sección y aula.'])->withInput();
        }

        Ubicacion::create($data);

        return redirect()->route('admin.activos.ubicaciones')->with('success', 'Ubicación creada con éxito');
    }

    /** La pareja (sección, aula) es única en la base de datos; se valida antes para dar un mensaje claro. */
    private function ubicacionDuplicada(array $data, ?int $ignorarId = null): bool
    {
        return Ubicacion::where('seccion', $data['seccion'])
            ->where('aula', $data['aula'] ?? null)
            ->when($ignorarId, fn ($q) => $q->where('id_ubicacion', '!=', $ignorarId))
            ->exists();
    }

    public function updateUbicacion(Request $request, $id)
    {
        $ubicacion = Ubicacion::findOrFail($id);

        $data = $request->validate([
            'seccion' => 'required|string|max:255',
            'aula' => 'nullable|string|max:255',
        ]);

        if ($this->ubicacionDuplicada($data, (int) $ubicacion->id_ubicacion)) {
            return back()->withErrors(['aula' => 'Ya existe una ubicación con esa sección y aula.'])->withInput();
        }

        $ubicacion->update($data);

        return redirect()->route('admin.activos.ubicaciones')->with('success', 'Ubicación actualizada con éxito');
    }

    public function activosPorUbicacion(Request $request, $id)
    {
        $ubicacion = Ubicacion::findOrFail($id);
        $buscar = $request->input('buscar');

        $query = ActivoTecnologico::with(['categoria', 'ubicacion'])
            ->where('fk_id_ubicacion', $id);

        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_patrimonial', 'like', "%{$buscar}%")
                  ->orWhere('nombre_equipo', 'like', "%{$buscar}%")
                  ->orWhere('marca', 'like', "%{$buscar}%")
                  ->orWhere('modelo', 'like', "%{$buscar}%")
                  ->orWhere('estado_operativo', 'like', "%{$buscar}%")
                  ->orWhereHas('categoria', fn($c) => $c->where('nombre_categoria', 'like', "%{$buscar}%"));
            });
        }

        $activos = $query->orderBy('id_activo', 'desc')->paginate(15)->appends(['buscar' => $buscar]);

        return view('activos.ubicacion-equipos', compact('ubicacion', 'activos', 'buscar'));
    }

    public function show($id)
    {
        $activo = ActivoTecnologico::with([
            'categoria.modosFalla',
            'ubicacion',
            'detalleComputadora',
            'detalleEquipoRed',
            'detalleProyector',
            'incidentes.usuarioReporta',
            'incidentes.modoFalla',
            'mantenimientos.usuarioAsignado',
            'mantenimientos.origenMantenimiento',
            'evaluaciones.modoFalla',
            'evaluaciones.planesTratamiento',
        ])->findOrFail($id);

        // Logs de auditoría sobre este activo
        $logs = \App\Models\Log::with('usuario')
            ->where('tabla_afectada', 'activos_tecnologicos')
            ->where('registro_id', $id)
            ->orderByDesc('fecha_hora')
            ->get();

        $usuarios = \App\Models\Usuario::orderBy('nombre')->get();
        // Solo los modos de falla de la categoría del activo: evaluar contra los de
        // otra categoría no tiene sentido y el backend lo rechaza.
        $modosFalla = \App\Models\ModoFalla::where('fk_id_categoria', $activo->fk_id_categoria)->orderBy('nombre_modo_falla')->get();

        return view('activos.show', compact('activo', 'logs', 'usuarios', 'modosFalla'));
    }

    public function storeHistorial(Request $request, $id)
    {
        $activo = ActivoTecnologico::findOrFail($id);

        $data = $request->validate([
            'accion_realizada' => 'required|string|max:255',
            'observaciones' => 'nullable|string',
            'tipo_evento' => 'nullable|string|max:100',
        ]);

        $accion = $data['accion_realizada'];
        if (!empty($data['tipo_evento'])) {
            $accion = '[' . $data['tipo_evento'] . '] ' . $accion;
        }

        \App\Models\Log::create([
            'id_usuario' => auth()->id() ?? (\App\Models\Usuario::first()->id_usuario ?? 1),
            'accion_realizada' => $accion,
            'tabla_afectada' => 'activos_tecnologicos',
            'registro_id' => $activo->id_activo,
            'fecha_hora' => now(),
            'valor_anterior' => null,
            'valor_nuevo' => $data['observaciones'] ?? 'Anotación manual en bitácora',
        ]);

        return redirect()->to(route('admin.activos.show', $activo->id_activo) . '#historial')
            ->with('success', 'Anotación registrada en el historial y bitácora con éxito.');
    }

    public function edit($id)
    {
        $activo = ActivoTecnologico::with(['detalleComputadora', 'detalleEquipoRed', 'detalleProyector'])->findOrFail($id);
        $categorias = Categoria::orderBy('nombre_categoria')->get();
        $ubicaciones = Ubicacion::orderBy('seccion')->orderBy('aula')->get();

        return view('activos.edit', compact('activo', 'categorias', 'ubicaciones'));
    }

    public function update(Request $request, $id)
    {
        $activo = ActivoTecnologico::findOrFail($id);

        $reglas = [
            'fk_id_categoria' => 'required|integer|exists:categorias,id_categoria',
            'fk_id_ubicacion' => 'required|integer|exists:ubicaciones,id_ubicacion',
            'codigo_patrimonial' => 'required|string|max:255|unique:activos_tecnologicos,codigo_patrimonial,' . $activo->id_activo . ',id_activo',
            'nombre_equipo' => 'required|string|max:255',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'fecha_adquisicion' => 'required|date|before_or_equal:today',
            'estado_operativo' => ['required', 'string', Rule::in(Catalogo::nombres('estados_operativos'))],
            'etapa_ciclo_vida' => ['required', 'string', Rule::in(Catalogo::nombres('etapas_ciclo_vida'))],
            'motivo_baja' => ['nullable', 'required_if:estado_operativo,De Baja', Rule::in(Catalogo::nombres('motivos_baja'))],
            'observaciones' => 'nullable|string',
        ];

        // Se valida todo (activo + ficha técnica) antes de modificar nada.
        $tipo = $this->tipoDetalle(Categoria::find($request->input('fk_id_categoria')));
        $reglasDetalle = $this->reglasDetalle($tipo);
        $validado = $request->validate($reglas + $reglasDetalle);

        $data = Arr::only($validado, array_keys($reglas));
        $detalle = Arr::only($validado, array_keys($reglasDetalle));

        // Trazabilidad de la baja: 'De Baja' es un estado_operativo más, pero
        // fecha_baja/motivo_baja/fk_id_usuario_baja solo tienen sentido en el
        // momento en que el activo entra o sale de esa condición.
        $estadoAnterior = $activo->estado_operativo;
        $pasaABaja = $data['estado_operativo'] === 'De Baja' && $estadoAnterior !== 'De Baja';
        $saleDeBaja = $data['estado_operativo'] !== 'De Baja' && $estadoAnterior === 'De Baja';

        if ($pasaABaja) {
            $data['fecha_baja'] = now()->toDateString();
            $data['estado_previo_baja'] = $estadoAnterior;
            $data['fk_id_usuario_baja'] = auth()->id();
        } elseif ($saleDeBaja) {
            $data['fecha_baja'] = null;
            $data['motivo_baja'] = null;
            $data['estado_previo_baja'] = null;
            $data['fk_id_usuario_baja'] = null;
        } elseif ($data['estado_operativo'] !== 'De Baja') {
            unset($data['motivo_baja']);
        }

        // Registrar cambios para auditoría en el historial
        $cambios = [];
        $camposAuditables = [
            'codigo_patrimonial' => 'Código Patrimonial',
            'nombre_equipo' => 'Nombre del Equipo',
            'fk_id_categoria' => 'Categoría',
            'fk_id_ubicacion' => 'Ubicación',
            'marca' => 'Marca',
            'modelo' => 'Modelo',
            'fecha_adquisicion' => 'Fecha Adquisición',
            'estado_operativo' => 'Estado Operativo',
            'etapa_ciclo_vida' => 'Etapa Ciclo de Vida',
            'motivo_baja' => 'Motivo de Baja',
        ];

        foreach ($camposAuditables as $col => $lbl) {
            if (isset($data[$col]) && $activo->$col != $data[$col]) {
                $anterior = $activo->$col;
                $nuevo = $data[$col];
                if ($col === 'fk_id_ubicacion') {
                    $uAnt = Ubicacion::find($anterior);
                    $uNue = Ubicacion::find($nuevo);
                    $anterior = $uAnt ? ($uAnt->seccion . ' ' . ($uAnt->aula ?? '')) : $anterior;
                    $nuevo = $uNue ? ($uNue->seccion . ' ' . ($uNue->aula ?? '')) : $nuevo;
                }
                $cambios[] = "{$lbl}: {$anterior} → {$nuevo}";
            }
        }

        DB::transaction(function () use ($activo, $data, $cambios, $tipo, $detalle, $request) {
            $activo->update($data);

            if (!empty($cambios)) {
                \App\Models\Log::create([
                    'id_usuario' => auth()->id() ?? (\App\Models\Usuario::first()->id_usuario ?? 1),
                    'accion_realizada' => 'Modificación de Activo Tecnológico',
                    'tabla_afectada' => 'activos_tecnologicos',
                    'registro_id' => $activo->id_activo,
                    'fecha_hora' => now(),
                    'valor_anterior' => 'Datos previos actualizados',
                    'valor_nuevo' => implode(' | ', $cambios),
                ]);
            }

            // Actualizar/crear la ficha técnica según la categoría elegida
            $this->guardarDetalle($tipo, $activo, $detalle, $request);
        });

        return redirect()->route('admin.activos.inventario')
            ->with('success', 'Activo tecnológico actualizado con éxito.' . $this->avisoDetalleNoGuardado($tipo, $request));
    }

    /**
     * Exportar el inventario de activos a Excel, agrupado por categoría.
     * Respeta el filtro de búsqueda activo.
     */
    public function exportExcel(Request $request)
    {
        $buscar = $request->input('buscar');
        return Excel::download(new InventarioActivosExport($buscar), 'Inventario_Activos_Tecnologicos.xlsx');
    }

    /**
     * Exportar el inventario de activos a PDF, agrupado por categoría.
     * Respeta el filtro de búsqueda activo.
     */
    public function exportPdf(Request $request)
    {
        $buscar = $request->input('buscar');

        $query = ActivoTecnologico::with(['categoria', 'ubicacion']);

        if ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('codigo_patrimonial', 'like', "%{$buscar}%")
                  ->orWhere('nombre_equipo', 'like', "%{$buscar}%")
                  ->orWhere('marca', 'like', "%{$buscar}%")
                  ->orWhere('modelo', 'like', "%{$buscar}%")
                  ->orWhere('estado_operativo', 'like', "%{$buscar}%")
                  ->orWhereHas('categoria', fn($c) => $c->where('nombre_categoria', 'like', "%{$buscar}%"))
                  ->orWhereHas('ubicacion', fn($u) => $u->where('seccion', 'like', "%{$buscar}%")->orWhere('aula', 'like', "%{$buscar}%"));
            });
        }

        $activos = $query->orderBy('fk_id_categoria')->orderBy('id_activo')->get();
        $agrupados = $activos->groupBy(fn($a) => $a->categoria->nombre_categoria ?? 'Sin Categoría');

        $categorias = [];
        $listaActivos = [];
        $contador = 1;

        foreach ($activos as $activo) {
            $ubicacion = '';
            if ($activo->ubicacion) {
                $ubicacion = $activo->ubicacion->seccion ?? '';
                if (!empty($activo->ubicacion->aula)) {
                    $ubicacion .= ' — ' . $activo->ubicacion->aula;
                }
            }

            $listaActivos[] = [
                'numero' => $contador++,
                'codigo_patrimonial' => $activo->codigo_patrimonial,
                'nombre_equipo' => $activo->nombre_equipo,
                'categoria' => $activo->categoria->nombre_categoria ?? 'Sin Categoría',
                'marca' => $activo->marca ?: '—',
                'modelo' => $activo->modelo ?: '—',
                'ubicacion' => $ubicacion ?: 'Sin Asignar',
                'fecha_adquisicion' => $activo->fecha_adquisicion ? \Carbon\Carbon::parse($activo->fecha_adquisicion)->format('d/m/Y') : '—',
                'estado_operativo' => $activo->estado_operativo ?? 'Operativo',
                'etapa_ciclo_vida' => $activo->etapa_ciclo_vida ?? 'Operación',
                'criticidad' => $activo->nivel_riesgo ?? $activo->criticidad_institucional ?? 'Bajo',
            ];
        }

        foreach ($agrupados as $nombreCat => $items) {
            $cat = ['nombre' => $nombreCat, 'activos' => []];
            foreach ($items as $activo) {
                $ubicacion = '';
                if ($activo->ubicacion) {
                    $ubicacion = $activo->ubicacion->seccion ?? '';
                    if (!empty($activo->ubicacion->aula)) {
                        $ubicacion .= ' — ' . $activo->ubicacion->aula;
                    }
                }
                $cat['activos'][] = [
                    'codigo_patrimonial' => $activo->codigo_patrimonial,
                    'nombre_equipo' => $activo->nombre_equipo,
                    'marca' => $activo->marca ?? '',
                    'modelo' => $activo->modelo ?? '',
                    'ubicacion' => $ubicacion,
                    'fecha_adquisicion' => $activo->fecha_adquisicion ? \Carbon\Carbon::parse($activo->fecha_adquisicion)->format('d/m/Y') : '',
                    'estado_operativo' => $activo->estado_operativo ?? '',
                    'etapa_ciclo_vida' => $activo->etapa_ciclo_vida ?? '',
                    'criticidad' => $activo->criticidad_institucional ?? '',
                ];
            }
            $categorias[] = $cat;
        }

        $logoBase64 = null;
        $logoPath = public_path('imagenes/logoFranco.jpg');
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $siatecBase64 = null;
        $siatecPath = public_path('imagenes/siatec.png');
        if (file_exists($siatecPath)) {
            $siatecBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($siatecPath));
        }

        $responsable = auth()->user()?->name ?? 'Responsable de Activos Tecnológicos';
        $fechaGeneracion = now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('informes.pdf.inventario-activos', [
            'listaActivos' => $listaActivos,
            'categorias' => $categorias,
            'totalActivos' => $activos->count(),
            'buscar' => $buscar,
            'responsable' => $responsable,
            'fechaGeneracion' => $fechaGeneracion,
            'logoBase64' => $logoBase64,
            'siatecBase64' => $siatecBase64,
        ])
        ->setPaper('letter', 'landscape')
        ->setOption(['isPhpEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->download('Lista_Activos_Tecnologicos.pdf');
    }
}
