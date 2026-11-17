@extends('layouts.principal')

@section('title', 'Registrar Usuario')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <h2 class="text-2xl font-semibold text-slate-900">Crear Usuario Nuevo</h2>
    </div>

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
                <input id="inputCorreo" name="correo" type="email" value="{{ old('correo') }}" placeholder="usuario@empresa.com" class="w-full border @error('correo') border-rose-500 @else border-gray-200 @enderror px-4 py-2.5 text-sm rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50" required />
                <div id="emailDnsFeedback" class="mt-1 text-xs transition-all duration-200"></div>
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

            {{-- Sección de Permisos por Módulo --}}
            <div class="md:col-span-2 pt-4 border-t border-slate-200">
                <div class="flex items-center gap-2 mb-4">
                   <!--  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg> -->
                    <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Permisos de Acceso por Módulo</h4>
                </div>
                <p class="text-xs text-slate-500 mb-4">Los permisos se configuran automáticamente según el rol seleccionado, pero puedes personalizarlos manualmente.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3" id="permisos-container">
                    {{-- Dashboard --}}
                    <label class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 cursor-pointer hover:border-indigo-300 transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
                            </div>
                            <span class="text-sm font-medium text-slate-700">Dashboard</span>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="permiso_dashboard" value="1" class="sr-only peer permiso-switch" data-modulo="dashboard" checked>
                            <div class="w-10 h-5 bg-slate-300 peer-checked:bg-indigo-600 rounded-full transition-colors duration-200"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm peer-checked:translate-x-5 transition-transform duration-200"></div>
                        </div>
                    </label>

                    {{-- Activos Tecnológicos --}}
                    <label class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 cursor-pointer hover:border-indigo-300 transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                            </div>
                            <span class="text-sm font-medium text-slate-700">Activos Tecnológicos</span>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="permiso_activos" value="1" class="sr-only peer permiso-switch" data-modulo="activos" checked>
                            <div class="w-10 h-5 bg-slate-300 peer-checked:bg-indigo-600 rounded-full transition-colors duration-200"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm peer-checked:translate-x-5 transition-transform duration-200"></div>
                        </div>
                    </label>

                    {{-- Incidentes --}}
                    <label class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 cursor-pointer hover:border-indigo-300 transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </div>
                            <span class="text-sm font-medium text-slate-700">Incidentes</span>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="permiso_incidentes" value="1" class="sr-only peer permiso-switch" data-modulo="incidentes" checked>
                            <div class="w-10 h-5 bg-slate-300 peer-checked:bg-indigo-600 rounded-full transition-colors duration-200"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm peer-checked:translate-x-5 transition-transform duration-200"></div>
                        </div>
                    </label>

                    {{-- Evaluación de Riesgos --}}
                    <label class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 cursor-pointer hover:border-indigo-300 transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            </div>
                            <span class="text-sm font-medium text-slate-700">Evaluación de Riesgos</span>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="permiso_riesgos" value="1" class="sr-only peer permiso-switch" data-modulo="riesgos" checked>
                            <div class="w-10 h-5 bg-slate-300 peer-checked:bg-indigo-600 rounded-full transition-colors duration-200"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm peer-checked:translate-x-5 transition-transform duration-200"></div>
                        </div>
                    </label>

                    {{-- Mantenimiento --}}
                    <label class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 cursor-pointer hover:border-indigo-300 transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            </div>
                            <span class="text-sm font-medium text-slate-700">Mantenimiento</span>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="permiso_mantenimiento" value="1" class="sr-only peer permiso-switch" data-modulo="mantenimiento" checked>
                            <div class="w-10 h-5 bg-slate-300 peer-checked:bg-indigo-600 rounded-full transition-colors duration-200"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm peer-checked:translate-x-5 transition-transform duration-200"></div>
                        </div>
                    </label>

                    {{-- Informes y Reportes --}}
                    <label class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 cursor-pointer hover:border-indigo-300 transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </div>
                            <span class="text-sm font-medium text-slate-700">Informes y Reportes</span>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="permiso_informes" value="1" class="sr-only peer permiso-switch" data-modulo="informes" checked>
                            <div class="w-10 h-5 bg-slate-300 peer-checked:bg-indigo-600 rounded-full transition-colors duration-200"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm peer-checked:translate-x-5 transition-transform duration-200"></div>
                        </div>
                    </label>

                    {{-- Gestión de Usuarios --}}
                    <label class="flex items-center justify-between bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 cursor-pointer hover:border-indigo-300 transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-slate-200 text-slate-600 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                            </div>
                            <span class="text-sm font-medium text-slate-700">Gestión de Usuarios</span>
                        </div>
                        <div class="relative">
                            <input type="checkbox" name="permiso_usuarios" value="1" class="sr-only peer permiso-switch" data-modulo="usuarios">
                            <div class="w-10 h-5 bg-slate-300 peer-checked:bg-indigo-600 rounded-full transition-colors duration-200"></div>
                            <div class="absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full shadow-sm peer-checked:translate-x-5 transition-transform duration-200"></div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="md:col-span-2 pt-2 flex items-center justify-end gap-3">
                <a href="{{ route('admin.usuarios.index') }}" class="btn btn-ghost">
                    Ver Lista de Usuarios
                </a>
                <button type="submit" class="btn btn-primary">
                    Registrar Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var permisosDefault = @json($permisosDefault);
    var rolSelect = document.querySelector('select[name="fk_id_rol"]');
    var switches = document.querySelectorAll('.permiso-switch');

    function aplicarPermisosPorRol(rolId) {
        if (!rolId || !permisosDefault[rolId]) return;
        var permisos = permisosDefault[rolId];
        switches.forEach(function(sw) {
            var modulo = sw.getAttribute('data-modulo');
            var campo = 'permiso_' + modulo;
            if (permisos.hasOwnProperty(campo)) {
                sw.checked = permisos[campo];
            }
        });
    }

    if (rolSelect) {
        rolSelect.addEventListener('change', function() {
            aplicarPermisosPorRol(this.value);
        });

        // Aplicar permisos del rol inicial al cargar (si hay un rol seleccionado)
        if (rolSelect.value) {
            aplicarPermisosPorRol(rolSelect.value);
        }
    }

    // Verificación en tiempo real de dominio DNS/MX
    var inputCorreo = document.getElementById('inputCorreo');
    var feedbackDiv = document.getElementById('emailDnsFeedback');
    var timer = null;

    if (inputCorreo && feedbackDiv) {
        function verificarCorreoActual() {
            var email = inputCorreo.value.trim();
            if (!email || !email.includes('@')) {
                feedbackDiv.innerHTML = '';
                return;
            }

            feedbackDiv.className = "mt-1 text-xs text-blue-600 font-medium flex items-center gap-1";
            feedbackDiv.innerHTML = "🔍 Verificando dominio DNS/MX...";

            fetch("{{ route('admin.usuarios.verificar-email') }}?email=" + encodeURIComponent(email))
                .then(res => res.json())
                .then(data => {
                    if (data.valid) {
                        feedbackDiv.className = "mt-1 text-xs text-emerald-600 font-semibold flex items-center gap-1";
                        feedbackDiv.innerHTML = "✓ " + data.message;
                    } else {
                        feedbackDiv.className = "mt-1 text-xs text-rose-600 font-semibold flex items-center gap-1";
                        feedbackDiv.innerHTML = "✕ " + data.message;
                    }
                })
                .catch(() => {
                    feedbackDiv.innerHTML = '';
                });
        }

        inputCorreo.addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(verificarCorreoActual, 600);
        });

        inputCorreo.addEventListener('blur', function() {
            clearTimeout(timer);
            verificarCorreoActual();
        });

        if (inputCorreo.value) {
            verificarCorreoActual();
        }
    }
})();
</script>
@endsection
