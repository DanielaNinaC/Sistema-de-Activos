@extends('layouts.principal')

@section('title', 'Registrar Usuario')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold text-slate-900">Registrar Usuario</h2>
    </div>

    {{-- Alerta de éxito --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 flex items-center gap-3 shadow-sm transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Alerta de errores de validación --}}
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm space-y-2 transition">
            <div class="flex items-center gap-2 font-semibold text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Por favor corrige los siguientes errores:</span>
            </div>
            <ul class="list-disc list-inside text-xs space-y-1 text-rose-700 pl-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tarjetas resumen achatadas --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-white px-4 py-2.5 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Usuarios</span>
            <span class="text-lg font-bold text-slate-900">{{ $usuarios_count }}</span>
        </div>
        <div class="bg-white px-4 py-2.5 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Roles Configurados</span>
            <span class="text-lg font-bold text-slate-900">{{ $roles_count }}</span>
        </div>
        <div class="bg-white px-4 py-2.5 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Logs del Sistema</span>
            <span class="text-lg font-bold text-slate-900">{{ $logs_count }}</span>
        </div>
    </div>

    {{-- Formulario de creación --}}
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
        <h3 class="text-lg font-bold text-slate-900 mb-5">Ingresa los datos del nuevo usuario</h3>
        
        <form method="POST" action="{{ route('admin.usuarios.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Nombre <span class="text-rose-500">*</span></label>
                <input name="nombre" value="{{ old('nombre') }}" placeholder="Ej. Juan Carlos" class="w-full border @error('nombre') border-rose-500 @else border-gray-200 @enderror px-4 py-2.5 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50" required />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Apellido</label>
                <input name="apellido" value="{{ old('apellido') }}" placeholder="Ej. Pérez Gómez" class="w-full border @error('apellido') border-rose-500 @else border-gray-200 @enderror px-4 py-2.5 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Correo Electrónico <span class="text-rose-500">*</span></label>
                <input name="correo" type="email" value="{{ old('correo') }}" placeholder="usuario@empresa.com" class="w-full border @error('correo') border-rose-500 @else border-gray-200 @enderror px-4 py-2.5 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50" required />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Rol de Sistema <span class="text-rose-500">*</span></label>
                <select name="fk_id_rol" class="w-full border @error('fk_id_rol') border-rose-500 @else border-gray-200 @enderror px-4 py-2.5 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50" required>
                    <option value="">Selecciona un rol</option>
                    @foreach($roles as $r)
                        <option value="{{ $r->id_rol }}" {{ old('fk_id_rol') == $r->id_rol ? 'selected' : '' }}>{{ $r->nombre_rol }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Contraseña Inicial <span class="text-rose-500">*</span></label>
                <input name="contrasena" type="password" placeholder="••••••••" class="w-full max-w-md border @error('contrasena') border-rose-500 @else border-gray-200 @enderror px-4 py-2.5 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50" required />
            </div>

            <div class="md:col-span-2 pt-2 flex items-center gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition shadow-md shadow-blue-200">
                    Registrar Usuario
                </button>
                <a href="{{ route('admin.usuarios.index') }}" class="px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100 rounded-xl transition">
                    Ver Lista de Usuarios
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
