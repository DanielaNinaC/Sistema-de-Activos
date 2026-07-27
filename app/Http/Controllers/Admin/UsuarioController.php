<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Log;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $query = Usuario::with('rol');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%")
                  ->orWhere('correo', 'like', "%{$search}%")
                  ->orWhereHas('rol', function ($r) use ($search) {
                      $r->where('nombre_rol', 'like', "%{$search}%");
                  });
            });
        }

        $usuarios = $query->orderBy('id_usuario', 'asc')->paginate(10)->appends($request->all());

        return view('admin.usuarios.index', compact('usuarios', 'search'));
    }

    public function create()
    {
        $roles = Rol::orderBy('nombre_rol')->get();
        $usuarios_count = Usuario::count();
        $roles_count = $roles->count();
        $logs_count = Log::count();

        return view('admin.usuarios.create', compact('roles', 'usuarios_count', 'roles_count', 'logs_count'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'nullable|string|max:255',
            'correo' => 'required|email|unique:usuarios,correo',
            'contrasena' => 'required|string|min:6',
            'fk_id_rol' => 'required|integer|exists:roles,id_rol',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.unique' => 'Este correo electrónico ya está registrado.',
            'contrasena.required' => 'La contraseña es obligatoria.',
            'contrasena.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'fk_id_rol.required' => 'Debe seleccionar un rol válido.',
        ]);

        $data['contrasena'] = Hash::make($data['contrasena']);
        $data['estado'] = 1;

        Usuario::create($data);

        return redirect()->route('admin.usuarios.create')->with('success', 'Usuario creado con éxito');
    }

    public function show($id)
    {
        $usuario = Usuario::with(['rol', 'logs' => function ($query) {
            $query->orderByDesc('fecha_hora');
        }])->findOrFail($id);

        return view('admin.usuarios.show', compact('usuario'));
    }

    public function edit($id)
    {
        $usuario = Usuario::findOrFail($id);
        $roles = Rol::orderBy('nombre_rol')->get();

        return view('admin.usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'nullable|string|max:255',
            'correo' => 'required|email|unique:usuarios,correo,' . $usuario->id_usuario . ',id_usuario',
            'contrasena' => 'nullable|string|min:6',
            'fk_id_rol' => 'required|integer|exists:roles,id_rol',
        ]);

        if (!empty($data['contrasena'])) {
            $data['contrasena'] = Hash::make($data['contrasena']);
        } else {
            unset($data['contrasena']);
        }

        $usuario->update($data);

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado con éxito.');
    }

    public function destroy($id)
    {
        $usuario = Usuario::findOrFail($id);
        $nuevoEstado = !$usuario->estado;
        $usuario->estado = $nuevoEstado;
        $usuario->save();

        $mensaje = $nuevoEstado ? 'Cuenta de usuario activada con éxito.' : 'Cuenta de usuario desactivada con éxito.';

        return back()->with('success', $mensaje);
    }
}
