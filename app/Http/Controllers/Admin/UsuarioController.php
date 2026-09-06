<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Log;
use Illuminate\Support\Facades\Hash;
use App\Rules\ValidEmailDomain;
use App\Services\EmailVerificationService;

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

        // Mapeo de permisos por defecto para cada rol (para JavaScript)
        $permisosDefault = [];
        foreach ($roles as $r) {
            $permisosDefault[$r->id_rol] = Usuario::permisosDefaultPorRol($r->id_rol);
        }

        return view('admin.usuarios.create', compact('roles', 'usuarios_count', 'roles_count', 'logs_count', 'permisosDefault'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'nullable|string|max:255',
            'correo' => ['required', 'email', 'unique:usuarios,correo', new ValidEmailDomain()],
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

        // Permisos por módulo (checkboxes envían '1' si marcados, ausentes si no)
        $modulos = ['dashboard', 'activos', 'incidentes', 'riesgos', 'mantenimiento', 'informes', 'usuarios'];
        foreach ($modulos as $mod) {
            $data['permiso_' . $mod] = $request->has('permiso_' . $mod) ? 1 : 0;
        }

        Usuario::create($data);

        return redirect()->route('admin.usuarios.create')->with('success', 'Usuario creado con éxito');
    }

    public function show($id)
    {
        $usuario = Usuario::with(['rol', 'logs' => function ($query) {
            $query->orderByDesc('fecha_hora');
        }])->findOrFail($id);

        $emailEstado = EmailVerificationService::verificarDominio($usuario->correo);

        return view('admin.usuarios.show', compact('usuario', 'emailEstado'));
    }

    public function edit($id)
    {
        $usuario = Usuario::findOrFail($id);
        $roles = Rol::orderBy('nombre_rol')->get();

        // Mapeo de permisos por defecto para cada rol (para JavaScript)
        $permisosDefault = [];
        foreach ($roles as $r) {
            $permisosDefault[$r->id_rol] = Usuario::permisosDefaultPorRol($r->id_rol);
        }

        return view('admin.usuarios.edit', compact('usuario', 'roles', 'permisosDefault'));
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellido' => 'nullable|string|max:255',
            'correo' => ['required', 'email', 'unique:usuarios,correo,' . $usuario->id_usuario . ',id_usuario', new ValidEmailDomain()],
            'contrasena' => 'nullable|string|min:6',
            'fk_id_rol' => 'required|integer|exists:roles,id_rol',
        ]);

        if (!empty($data['contrasena'])) {
            $data['contrasena'] = Hash::make($data['contrasena']);
        } else {
            unset($data['contrasena']);
        }

        // Permisos por módulo
        $modulos = ['dashboard', 'activos', 'incidentes', 'riesgos', 'mantenimiento', 'informes', 'usuarios'];
        foreach ($modulos as $mod) {
            $data['permiso_' . $mod] = $request->has('permiso_' . $mod) ? 1 : 0;
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

    /**
     * Endpoint AJAX para verificar la validez DNS/MX de un correo específico.
     */
    public function verificarEmail(Request $request)
    {
        $email = $request->input('email', '');
        $resultado = EmailVerificationService::verificarDominio((string) $email);

        return response()->json($resultado);
    }

    /**
     * Endpoint AJAX para verificar masivamente el estado del dominio de correos de usuarios.
     */
    public function verificarTodos(Request $request)
    {
        $usuarios = Usuario::select('id_usuario', 'correo')->get();
        $resultados = [];

        foreach ($usuarios as $u) {
            $resultados[$u->id_usuario] = EmailVerificationService::verificarDominio($u->correo);
        }

        return response()->json([
            'success' => true,
            'total' => count($resultados),
            'resultados' => $resultados,
        ]);
    }
}

