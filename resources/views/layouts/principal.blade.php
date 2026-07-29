<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Turbo Navigation — Progress bar */
        #turbo-progress {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            z-index: 9999;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
        }
        #turbo-progress.active {
            opacity: 1;
        }
        .turbo-progress-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #6366f1, #818cf8, #a5b4fc);
            border-radius: 0 2px 2px 0;
            box-shadow: 0 0 10px rgba(99, 102, 241, 0.5);
        }

        /* Content transition */
        #main-content {
            transition: opacity 0.08s ease-out;
        }

        /* Active sidebar link */
        aside a.active-link {
            background: rgba(255, 255, 255, 0.1);
            font-weight: 500;
        }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden flex flex-col">

    <div class="bg-white shadow p-3 flex justify-between items-center shrink-0 relative">
        <div class="flex items-center gap-3">
            <img src="{{ asset('imagenes/logoFranco.jpg') }}" alt="Logo" class="h-12 w-30">
        </div>

        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <h1 class="text-xl font-bold pointer-events-auto">SISTEMA SAT</h1>
        </div>

        <div class="relative">
            <button id="profileToggle" class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg hover:bg-gray-100 focus:outline-none transition" aria-expanded="false" aria-controls="profileMenu" aria-label="Perfil">
                <div class="w-9 h-9 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-base shadow-sm">{{ strtoupper(substr(Auth::user()->nombre ?? Auth::user()->name ?? 'U',0,1)) }}</div>
                <span class="ml-1 text-base font-semibold text-slate-800">{{ Auth::user()->nombre ?? Auth::user()->name ?? 'Usuario' }}</span>
            </button>

            <div id="profileMenu" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-2xl shadow-xl border border-slate-100 z-50 overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-100">
                    <div class="font-bold text-slate-900 text-base leading-snug">{{ Auth::user()->nombre ?? 'Usuario' }} {{ Auth::user()->apellido ?? '' }}</div>
                    <div class="text-sm text-indigo-600 font-bold mt-0.5">{{ Auth::user()->rol->nombre_rol ?? 'Sin Rol' }}</div>
                    <div class="text-sm text-slate-600 mt-1 break-words font-medium">{{ Auth::user()->correo ?? '' }}</div>
                </div>

                <div class="p-2 space-y-1">
                    <button type="button" onclick="openProfileEditModal()" class="w-full flex items-center gap-2.5 px-3.5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 rounded-xl transition text-left">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        Editar Perfil
                    </button>

                    <form method="POST" action="{{ route('logout') }}" data-turbo-disabled>
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2.5 px-3.5 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50 rounded-xl transition text-left">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Cerrar Sesión
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-1 min-h-0">
                <aside class="w-64 bg-slate-950 text-white p-4 space-y-2 overflow-y-auto">
                    <nav class="mt-2 flex flex-col gap-1">
                        <a href="{{ route('dashboard') }}" class="p-2 rounded hover:bg-white/5">Dashboard</a>

                        @if(auth()->user()->isAdmin())
                                <div class="p-2 rounded hover:bg-white/5 has-submenu cursor-pointer" tabindex="0" role="button" aria-expanded="false" data-submenu-key="activos">
                                <div class="flex items-center justify-between">
                                    <div>Activos Tecnológicos</div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="submenu-arrow h-5 w-5 transform transition-transform text-white/90" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 8l4 4 4-4" clip-rule="evenodd" /></svg>
                                </div>
                                <div class="submenu hidden mt-1 pl-3" data-submenu-panel="activos">
                                    <a href="{{ route('admin.activos.inventario') }}" class="block p-2 rounded hover:bg-white/5">Inventario</a>
                                    <a href="{{ route('admin.activos.registrar') }}" class="block p-2 rounded hover:bg-white/5">Registrar Activo</a>
                                    <a href="{{ route('admin.activos.categorias') }}" class="block p-2 rounded hover:bg-white/5">Categorías</a>
                                    <a href="{{ route('admin.activos.ubicaciones') }}" class="block p-2 rounded hover:bg-white/5">Ubicaciones</a>
                                </div>
                            </div>
                            <div class="p-2 rounded hover:bg-white/5 has-submenu cursor-pointer" tabindex="0" role="button" aria-expanded="false" data-submenu-key="incidentes">
                                <div class="flex items-center justify-between">
                                    <div>Incidentes</div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="submenu-arrow h-5 w-5 transform transition-transform text-white/90" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 8l4 4 4-4" clip-rule="evenodd" /></svg>
                                </div>
                                <div class="submenu hidden mt-1 pl-3" data-submenu-panel="incidentes">
                                    <a href="{{ route('admin.incidentes.registrar') }}" class="block p-2 rounded hover:bg-white/5">Registrar Incidente</a>
                                    <a href="{{ route('admin.incidentes.seguimiento') }}" class="block p-2 rounded hover:bg-white/5">Seguimiento de Incidentes</a>
                                    <a href="{{ route('admin.incidentes.historial') }}" class="block p-2 rounded hover:bg-white/5">Historial</a>
                                </div>
                            </div>

                        <div class="p-2 rounded hover:bg-white/5 has-submenu cursor-pointer" tabindex="0" role="button" aria-expanded="false" data-submenu-key="riesgos">
                            <div class="flex items-center justify-between">
                                <div>Evaluación de Riesgos</div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="submenu-arrow h-5 w-5 transform transition-transform text-white/90" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 8l4 4 4-4" clip-rule="evenodd" /></svg>
                            </div>
                            <div class="submenu hidden mt-1 pl-3" data-submenu-panel="riesgos">
                                <a href="{{ route('admin.riesgos.modos') }}" class="block p-2 rounded hover:bg-white/5">Modos de Falla</a>
                                <a href="{{ route('admin.riesgos.evaluacion') }}" class="block p-2 rounded hover:bg-white/5">Evaluación FMEA</a>
                                <a href="{{ route('admin.riesgos.score') }}" class="block p-2 rounded hover:bg-white/5">Score</a>
                                <a href="{{ route('admin.riesgos.riesgo') }}" class="block p-2 rounded hover:bg-white/5">Riesgo</a>
                                <a href="{{ route('admin.riesgos.alertas') }}" class="block p-2 rounded hover:bg-white/5">Alertas</a>
                                <a href="{{ route('admin.riesgos.tratamiento') }}" class="block p-2 rounded hover:bg-white/5">Plan de tratamiento</a>
                            </div>
                        </div>

                            <div class="p-2 rounded hover:bg-white/5 has-submenu cursor-pointer" tabindex="0" role="button" aria-expanded="false" data-submenu-key="mantenimiento">
                                <div class="flex items-center justify-between">
                                    <div>Mantenimiento</div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="submenu-arrow h-5 w-5 transform transition-transform text-white/90" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 8l4 4 4-4" clip-rule="evenodd" /></svg>
                                </div>
                                <div class="submenu hidden mt-1 pl-3" data-submenu-panel="mantenimiento">
                                    <a href="{{ route('admin.mantenimiento.programar') }}" class="block p-2 rounded hover:bg-white/5">Registrar Mantenimiento</a>
                                    <a href="{{ route('admin.mantenimiento.cronograma') }}" class="block p-2 rounded hover:bg-white/5">Cronograma</a>
                                    <a href="{{ route('admin.mantenimiento.historial') }}" class="block p-2 rounded hover:bg-white/5">Historial</a>
                                </div>
                            </div>
                        @endif

                        <div class="p-2 rounded hover:bg-white/5 has-submenu cursor-pointer" tabindex="0" role="button" aria-expanded="false">
                            <div class="flex items-center justify-between">
                                <div>Informes y Reportes</div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="submenu-arrow h-5 w-5 transform transition-transform text-white/90" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 8l4 4 4-4" clip-rule="evenodd" /></svg>
                            </div>
                            <div class="submenu hidden mt-1 pl-3">
                                <div class="p-2 rounded hover:bg-white/5">Riesgos</div>
                                <div class="p-2 rounded hover:bg-white/5">Incidentes</div>
                                <div class="p-2 rounded hover:bg-white/5">Mantenimiento</div>
                            </div>
                        </div>

                        <div class="p-2 rounded hover:bg-white/5 has-submenu cursor-pointer" tabindex="0" role="button" aria-expanded="false">
                            <div class="flex items-center justify-between">
                                <div>Administración</div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="submenu-arrow h-5 w-5 transform transition-transform text-white/90" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 8l4 4 4-4" clip-rule="evenodd" /></svg>
                            </div>
                            <div class="submenu hidden mt-1 pl-3 space-y-0.5">
                                <a href="{{ route('admin.usuarios.create') }}" class="block p-2 rounded text-sm hover:bg-white/10 transition">Registrar Usuario</a>
                                <a href="{{ route('admin.usuarios.index') }}" class="block p-2 rounded text-sm hover:bg-white/10 transition">Lista de Usuarios</a>
                            </div>
                        </div>

                    </nav>
                </aside>

        {{-- Contenido dinámico — solo esta parte se actualiza en la navegación SPA --}}
        <main id="main-content" class="flex-1 p-8 overflow-y-auto">
            @yield('content')
        </main>
    </div>

<script>
    (function(){
        var btn = document.getElementById('profileToggle');
        var menu = document.getElementById('profileMenu');
        if(btn && menu){
            btn.addEventListener('click', function(e){
                e.stopPropagation();
                menu.classList.toggle('hidden');
            });

            // Close when clicking outside
            document.addEventListener('click', function(){
                if(!menu.classList.contains('hidden')){
                    menu.classList.add('hidden');
                }
            });
        }

        // Sidebar submenu toggles with persistence
        var parents = document.querySelectorAll('.has-submenu');
        var openKey = sessionStorage.getItem('openSubmenuKey');
        var currentPath = window.location.pathname;

        function setOpenState(parent, shouldOpen) {
            var submenu = parent.querySelector('.submenu');
            var arrow = parent.querySelector('.submenu-arrow');
            if (!submenu) return;

            submenu.classList.toggle('hidden', !shouldOpen);
            parent.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            if (arrow) {
                arrow.classList.toggle('rotate-90', shouldOpen);
            }
        }

        parents.forEach(function(p){
            var key = p.getAttribute('data-submenu-key');
            var shouldOpenByPath = (
                (key === 'activos' && currentPath.indexOf('/admin/activos/') !== -1) ||
                (key === 'incidentes' && currentPath.indexOf('/admin/incidentes/') !== -1) ||
                (key === 'mantenimiento' && currentPath.indexOf('/admin/mantenimiento/') !== -1) ||
                (key === 'riesgos' && currentPath.indexOf('/admin/riesgos/') !== -1)
            );

            if (key && (key === openKey || shouldOpenByPath)) {
                setOpenState(p, true);
                if (key) {
                    sessionStorage.setItem('openSubmenuKey', key);
                }
            }

            p.addEventListener('click', function(e){
                // Don't toggle submenu when clicking a link inside it
                if (e.target.closest('a')) return;

                var submenu = p.querySelector('.submenu');
                if (!submenu) return;

                var shouldOpen = submenu.classList.contains('hidden');
                setOpenState(p, shouldOpen);

                if (key) {
                    sessionStorage.setItem('openSubmenuKey', shouldOpen ? key : '');
                }
            });
        });
    })();
</script>

<!-- Modal Editar Perfil -->
<div id="profileEditModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 border border-slate-100 transform transition-all scale-95 opacity-0" id="profileEditModalCard">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-base">
                    {{ strtoupper(substr(Auth::user()->nombre ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Editar Mi Perfil</h3>
                    <p class="text-xs text-slate-500">Actualiza tu información personal y contraseña</p>
                </div>
            </div>
            <button type="button" onclick="closeProfileEditModal()" class="text-slate-400 hover:text-slate-600 transition focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form method="POST" action="{{ route('perfil.update') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nombre <span class="text-rose-500">*</span></label>
                    <input type="text" name="nombre" value="{{ old('nombre', Auth::user()->nombre ?? Auth::user()->name ?? '') }}" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Apellido</label>
                    <input type="text" name="apellido" value="{{ old('apellido', Auth::user()->apellido ?? '') }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Correo Electrónico <span class="text-rose-500">*</span></label>
                <input type="email" name="correo" value="{{ old('correo', Auth::user()->correo ?? Auth::user()->email ?? '') }}" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="pt-3 border-t border-slate-100 space-y-3">
                <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">Cambiar Contraseña (Opcional)</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Nueva Contraseña</label>
                        <input type="password" name="contrasena" placeholder="Mínimo 6 caracteres" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Confirmar Contraseña</label>
                        <input type="password" name="contrasena_confirmation" placeholder="Repite la contraseña" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeProfileEditModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md shadow-indigo-200 transition">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openProfileEditModal() {
        var menu = document.getElementById('profileMenu');
        if (menu) menu.classList.add('hidden');

        var modal = document.getElementById('profileEditModal');
        var card = document.getElementById('profileEditModalCard');
        if (modal && card) {
            modal.classList.remove('hidden');
            setTimeout(function () {
                card.classList.remove('scale-95', 'opacity-0');
                card.classList.add('scale-100', 'opacity-100');
            }, 10);
        }
    }

    function closeProfileEditModal() {
        var modal = document.getElementById('profileEditModal');
        var card = document.getElementById('profileEditModalCard');
        if (modal && card) {
            card.classList.remove('scale-100', 'opacity-100');
            card.classList.add('scale-95', 'opacity-0');
            setTimeout(function () {
                modal.classList.add('hidden');
            }, 150);
        }
    }
</script>

</body>
</html>
