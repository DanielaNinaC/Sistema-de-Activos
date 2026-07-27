<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivoTecnologico;
use App\Models\Categoria;
use App\Models\Ubicacion;
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
            'fecha_adquisicion' => 'nullable|date_format:Y',
            'estado_operativo' => 'required|string|max:255',
            'etapa_ciclo_vida' => 'required|string|max:255',
            'criticidad_institucional' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        ActivoTecnologico::create($data);

        return redirect()->route('admin.activos.registrar')->with('success', 'Activo tecnológico creado con éxito');
    }

    public function categorias()
    {
        $categorias = Categoria::orderBy('id_categoria')->get();

        return view('activos.categorias', compact('categorias'));
    }

    public function storeCategoria(Request $request)
    {
        $data = $request->validate([
            'nombre_categoria' => 'required|string|max:255|unique:categorias,nombre_categoria',
            'vida_util_meses' => 'nullable|integer|min:1',
        ]);

        Categoria::create($data);

        return redirect()->route('admin.activos.categorias')->with('success', 'Categoría creada con éxito');
    }

    public function ubicaciones()
    {
        $ubicaciones = Ubicacion::orderBy('id_ubicacion')->get();

        return view('activos.ubicaciones', compact('ubicaciones'));
    }

    public function storeUbicacion(Request $request)
    {
        $data = $request->validate([
            'seccion' => 'required|string|max:255',
            'aula' => 'required|string|max:255',
        ]);

        Ubicacion::create($data);

        return redirect()->route('admin.activos.ubicaciones')->with('success', 'Ubicación creada con éxito');
    }
}
