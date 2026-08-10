@extends('layouts.principal')

@section('title','Editar Usuario')

@section('content')
<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-semibold mb-4">Editar Usuario</h2>

    <form method="POST" action="{{ route('admin.usuarios.update', $usuario->id_usuario) }}" class="bg-white p-6 rounded shadow">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm">Nombre</label>
                <input name="nombre" value="{{ $usuario->nombre }}" class="w-full border px-3 py-2 rounded" required />
            </div>
            <div>
                <label class="block text-sm">Apellido</label>
                <input name="apellido" value="{{ $usuario->apellido }}" class="w-full border px-3 py-2 rounded" />
            </div>
            <div>
                <label class="block text-sm">Correo</label>
                <input name="correo" type="email" value="{{ $usuario->correo }}" class="w-full border px-3 py-2 rounded" required />
            </div>
            <div>
                <label class="block text-sm">Rol</label>
                <select name="fk_id_rol" class="w-full border px-3 py-2 rounded" required>
                    @foreach($roles as $r)
                        <option value="{{ $r->id_rol }}" @if($usuario->fk_id_rol == $r->id_rol) selected @endif>{{ $r->nombre_rol }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm">Contraseña (dejar en blanco para no cambiar)</label>
                <input name="contrasena" type="password" class="w-full border px-3 py-2 rounded" />
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-primary">Actualizar</button>
            <a href="{{ route('admin.administracion.index') }}" class="btn btn-ghost">Cancelar</a>
        </div>
    </form>
</div>
@endsection
