<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\Ubicacion;
use App\Models\DetalleComputadora;
use App\Models\DetalleEquipoRed;
use App\Models\DetalleProyector;
use Illuminate\Http\Request;

class ActivoTecnologicoController extends Controller
{
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'fk_id_categoria' => 'required|integer|exists:categorias,id_categoria',
            'fk_id_ubicacion' => 'required|integer|exists:ubicaciones,id_ubicacion',
            'codigo_patrimonial' => 'required|string|max:255|unique:activos_tecnologicos,codigo_patrimonial',
            'nombre_equipo' => 'required|string|max:255',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'fecha_adquisicion' => 'nullable|date',
            'estado_operativo' => 'required|string|max:255',
            'etapa_ciclo_vida' => 'required|string|max:255',
            'criticidad_institucional' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $activo = ActivoTecnologico::create($data);

        // Guardar detalles específicos según la categoría seleccionada
        $categoria = Categoria::find($request->fk_id_categoria);
        if ($categoria) {
            $catName = strtolower(trim($categoria->nombre_categoria));

            if (str_contains($catName, 'computadora') || str_contains($catName, 'pc') || str_contains($catName, 'laptop')) {
                $compData = $request->validate([
                    'procesador' => 'nullable|string|max:255',
                    'ram_gb' => 'nullable|integer',
                    'disco_solido_gb' => 'nullable|integer',
                    'disco_rigido_gb' => 'nullable|integer',
                    'sistema_operativo' => 'nullable|string|max:255',
                    'lector' => 'nullable',
                    'parlantes' => 'nullable',
                    'teclado_mouse' => 'nullable',
                    'monitor' => 'nullable',
                    'camara_web' => 'nullable',
                ]);
                $compData['fk_id_activo'] = $activo->id_activo;
                $compData['lector'] = $request->boolean('lector');
                $compData['parlantes'] = $request->boolean('parlantes');
                $compData['teclado_mouse'] = $request->boolean('teclado_mouse');
                $compData['monitor'] = $request->boolean('monitor');
                $compData['camara_web'] = $request->boolean('camara_web');
                DetalleComputadora::create($compData);
            } elseif (str_contains($catName, 'red') || str_contains($catName, 'servidor') || str_contains($catName, 'switch') || str_contains($catName, 'router') || str_contains($catName, 'impresora') || str_contains($catName, 'printer')) {
                $redData = $request->validate([
                    'direccion_ip' => 'nullable|string|max:255',
                    'version_firmware' => 'nullable|string|max:255',
                ]);
                $redData['fk_id_activo'] = $activo->id_activo;
                DetalleEquipoRed::create($redData);
            } elseif (str_contains($catName, 'proyector')) {
                $proyData = $request->validate([
                    'tipo_cable' => 'nullable|string|max:255',
                    'calidad_imagen' => 'nullable|integer',
                ]);
                $proyData['fk_id_activo'] = $activo->id_activo;
                DetalleProyector::create($proyData);
            }
        }

        return redirect()->route('admin.activos.inventario')->with('success', 'Activo tecnológico creado con éxito.');
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
            'nivel_dependencia' => 'required|in:Alta,Media,Baja',
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
            'nivel_dependencia' => 'required|in:Alta,Media,Baja',
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

        Ubicacion::create($data);

        return redirect()->route('admin.activos.ubicaciones')->with('success', 'Ubicación creada con éxito');
    }

    public function updateUbicacion(Request $request, $id)
    {
        $ubicacion = Ubicacion::findOrFail($id);

        $data = $request->validate([
            'seccion' => 'required|string|max:255',
            'aula' => 'nullable|string|max:255',
        ]);

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
            'categoria',
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

        return view('activos.show', compact('activo', 'logs'));
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

        $data = $request->validate([
            'fk_id_categoria' => 'required|integer|exists:categorias,id_categoria',
            'fk_id_ubicacion' => 'required|integer|exists:ubicaciones,id_ubicacion',
            'codigo_patrimonial' => 'required|string|max:255|unique:activos_tecnologicos,codigo_patrimonial,' . $activo->id_activo . ',id_activo',
            'nombre_equipo' => 'required|string|max:255',
            'marca' => 'nullable|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'fecha_adquisicion' => 'nullable|date',
            'estado_operativo' => 'required|string|max:255',
            'etapa_ciclo_vida' => 'required|string|max:255',
            'criticidad_institucional' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $activo->update($data);

        // Actualizar/Crear detalles según la categoría elegida
        $categoria = Categoria::find($request->fk_id_categoria);
        if ($categoria) {
            $catName = strtolower(trim($categoria->nombre_categoria));

            if (str_contains($catName, 'computadora') || str_contains($catName, 'pc') || str_contains($catName, 'laptop')) {
                $compData = $request->validate([
                    'procesador' => 'nullable|string|max:255',
                    'ram_gb' => 'nullable|integer',
                    'disco_solido_gb' => 'nullable|integer',
                    'disco_rigido_gb' => 'nullable|integer',
                    'sistema_operativo' => 'nullable|string|max:255',
                    'lector' => 'nullable',
                    'parlantes' => 'nullable',
                    'teclado_mouse' => 'nullable',
                    'monitor' => 'nullable',
                    'camara_web' => 'nullable',
                ]);
                $compData['lector'] = $request->boolean('lector');
                $compData['parlantes'] = $request->boolean('parlantes');
                $compData['teclado_mouse'] = $request->boolean('teclado_mouse');
                $compData['monitor'] = $request->boolean('monitor');
                $compData['camara_web'] = $request->boolean('camara_web');
                DetalleComputadora::updateOrCreate(['fk_id_activo' => $activo->id_activo], $compData);
            } elseif (str_contains($catName, 'red') || str_contains($catName, 'servidor') || str_contains($catName, 'switch') || str_contains($catName, 'router') || str_contains($catName, 'impresora') || str_contains($catName, 'printer')) {
                $redData = $request->validate([
                    'direccion_ip' => 'nullable|string|max:255',
                    'version_firmware' => 'nullable|string|max:255',
                ]);
                DetalleEquipoRed::updateOrCreate(['fk_id_activo' => $activo->id_activo], $redData);
            } elseif (str_contains($catName, 'proyector')) {
                $proyData = $request->validate([
                    'tipo_cable' => 'nullable|string|max:255',
                    'calidad_imagen' => 'nullable|integer',
                ]);
                DetalleProyector::updateOrCreate(['fk_id_activo' => $activo->id_activo], $proyData);
            }
        }

        return redirect()->route('admin.activos.inventario')->with('success', 'Activo tecnológico actualizado con éxito.');
    }
}
