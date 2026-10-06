<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

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

        /* Barra de scroll del menú lateral: la del sistema pinta un canal
           claro que corta el azul del panel. Se deja el canal transparente y
           el pulgar en blanco translúcido para que se integre con el fondo.
           `scrollbar-color` cubre Firefox y Chrome moderno; las reglas
           ::-webkit-scrollbar son el respaldo para navegadores WebKit
           que aún no lo soportan. */
        aside nav {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.25) transparent;
        }
        aside nav::-webkit-scrollbar {
            width: 6px;
        }
        aside nav::-webkit-scrollbar-track {
            background: transparent;
        }
        aside nav::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.25);
            border-radius: 9999px;
        }
        aside nav::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 255, 255, 0.4);
        }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden flex">

    {{-- ===== Sidebar — full height from top to bottom ===== --}}
    <aside class="w-64 bg-[#1D3557] text-white flex flex-col shrink-0 h-screen overflow-hidden">

        {{-- Logo at the top of sidebar --}}
        <div class="px-5 py-4 border-b border-white/10 shrink-0">
            <div class="flex items-center gap-3">
                <div class="bg-white rounded-xl p-1.5 shadow">
                    <img src="{{ asset('imagenes/siatec.png') }}" alt="SIATEC" class="h-9 w-auto object-contain">
                </div>
                <div>
                    <h2 class="text-base font-bold tracking-wide leading-none">SIATEC</h2>
                    <p class="text-[10px] text-slate-400 mt-0.5">Activos Tecnológicos</p>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto p-4 space-y-1">
            @if(auth()->user()->tieneAccesoModulo('dashboard'))
            <a href="{{ route('dashboard') }}" class="p-2 rounded hover:bg-white/5 flex items-center gap-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Dashboard</span>
            </a>
            @endif

            @if(auth()->user()->tieneAccesoModulo('activos'))
                <div class="p-2 rounded hover:bg-white/5 has-submenu cursor-pointer" tabindex="0" role="button" aria-expanded="false" data-submenu-key="activos">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <span>Activos Tecnológicos</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="submenu-arrow h-5 w-5 transform transition-transform text-white/90" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 8l4 4 4-4" clip-rule="evenodd" /></svg>
                    </div>
                    <div class="submenu hidden mt-1 pl-3" data-submenu-panel="activos">
                        <a href="{{ route('admin.activos.registrar') }}" class="block p-2 rounded hover:bg-white/5">Registrar Activo</a>
                        <a href="{{ route('admin.activos.inventario') }}" class="block p-2 rounded hover:bg-white/5">Lista de activos</a>
                        <a href="{{ route('admin.activos.categorias') }}" class="block p-2 rounded hover:bg-white/5">Categorías</a>
                        <a href="{{ route('admin.activos.ubicaciones') }}" class="block p-2 rounded hover:bg-white/5">Ubicaciones</a>
                    </div>
                </div>
            @endif

            @if(auth()->user()->tieneAccesoModulo('incidentes'))
                <div class="p-2 rounded hover:bg-white/5 has-submenu cursor-pointer" tabindex="0" role="button" aria-expanded="false" data-submenu-key="incidentes">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Incidentes</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="submenu-arrow h-5 w-5 transform transition-transform text-white/90" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 8l4 4 4-4" clip-rule="evenodd" /></svg>
                    </div>
                    <div class="submenu hidden mt-1 pl-3" data-submenu-panel="incidentes">
                        <a href="{{ route('admin.incidentes.registrar') }}" class="block p-2 rounded hover:bg-white/5">Registrar Incidente</a>
                        <a href="{{ route('admin.incidentes.seguimiento') }}" class="block p-2 rounded hover:bg-white/5">Seguimiento de Incidentes</a>
                        <a href="{{ route('admin.incidentes.historial') }}" class="block p-2 rounded hover:bg-white/5">Historial</a>
                    </div>
                </div>
            @endif

            @if(auth()->user()->tieneAccesoModulo('riesgos'))
                <div class="p-2 rounded hover:bg-white/5 has-submenu cursor-pointer" tabindex="0" role="button" aria-expanded="false" data-submenu-key="riesgos">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span>Evaluación de Riesgos</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="submenu-arrow h-5 w-5 transform transition-transform text-white/90" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 8l4 4 4-4" clip-rule="evenodd" /></svg>
                    </div>
                    <div class="submenu hidden mt-1 pl-3" data-submenu-panel="riesgos">
                        <a href="{{ route('admin.riesgos.evaluacion') }}" class="block p-2 rounded hover:bg-white/5">Evaluación FMEA</a>
                        <a href="{{ route('admin.riesgos.riesgo') }}" class="block p-2 rounded hover:bg-white/5">Historial de Riesgos</a>
                        <a href="{{ route('admin.riesgos.alertas') }}" class="block p-2 rounded hover:bg-white/5">Alertas</a>
                        {{-- Modos de Falla y Score dejaron de ser accesos sueltos:
                             ahora se entra a ambos desde esta pantalla intermedia. --}}
                        <a href="{{ route('admin.riesgos.tratamiento') }}" class="block p-2 rounded hover:bg-white/5">Plan de Tratamiento</a>
                        <a href="{{ route('admin.riesgos.configuracion') }}" class="block p-2 rounded hover:bg-white/5">Configuración de Evaluación</a>
                    </div>
                </div>
            @endif

            @if(auth()->user()->tieneAccesoModulo('mantenimiento'))
                <div class="p-2 rounded hover:bg-white/5 has-submenu cursor-pointer" tabindex="0" role="button" aria-expanded="false" data-submenu-key="mantenimiento">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Mantenimiento</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="submenu-arrow h-5 w-5 transform transition-transform text-white/90" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 8l4 4 4-4" clip-rule="evenodd" /></svg>
                    </div>
                    <div class="submenu hidden mt-1 pl-3" data-submenu-panel="mantenimiento">
                        <a href="{{ route('admin.mantenimiento.programar') }}" class="block p-2 rounded hover:bg-white/5">Registrar Mantenimiento</a>
                        <a href="{{ route('admin.mantenimiento.cronograma') }}" class="block p-2 rounded hover:bg-white/5">Cronograma</a>
                        <a href="{{ route('admin.mantenimiento.historial') }}" class="block p-2 rounded hover:bg-white/5">Historial</a>
                    </div>
                </div>
            @endif

            @if(auth()->user()->tieneAccesoModulo('informes'))
                <div class="p-2 rounded hover:bg-white/5 has-submenu cursor-pointer" tabindex="0" role="button" aria-expanded="false" data-submenu-key="informes">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Informes y Reportes</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="submenu-arrow h-5 w-5 transform transition-transform text-white/90" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 8l4 4 4-4" clip-rule="evenodd" /></svg>
                    </div>
                    <div class="submenu hidden mt-1 pl-3" data-submenu-panel="informes">
                        <a href="{{ route('admin.informes.auditoria-ti.ra01') }}" class="block p-2 rounded hover:bg-white/5">Inventario General</a>
                        <a href="{{ route('admin.informes.estado-activos') }}" class="block p-2 rounded hover:bg-white/5">Estado de Activos</a>
                        
                        <a href="{{ route('admin.informes.activos-baja') }}" class="block p-2 rounded hover:bg-white/5">Activos Dados de Baja</a>
                        
                        <a href="{{ route('admin.informes.auditoria-ti.ra02') }}" class="block p-2 rounded hover:bg-white/5">Plan de Mantenimiento</a>
                        <a href="{{ route('admin.informes.auditoria-ti.ra03') }}" class="block p-2 rounded hover:bg-white/5">Registro de Incidentes</a>
                        <!-- <a href="{{ route('admin.informes.historial-tratamiento') }}" class="block p-2 rounded hover:bg-white/5">Historial de Tratamiento</a> -->
                        <!-- <a href="{{ route('admin.informes.evolucion-score') }}" class="block p-2 rounded hover:bg-white/5">Evolución de Score</a> -->
                        <a href="{{ route('admin.informes.modos-falla') }}" class="block p-2 rounded hover:bg-white/5">Modos de Falla Frecuentes</a>
                        <!-- <a href="{{ route('admin.informes.mantenimiento-tipo') }}" class="block p-2 rounded hover:bg-white/5">Preventivo vs. Correctivo</a> -->
                        <a href="{{ route('admin.informes.sin-tratamiento') }}" class="block p-2 rounded hover:bg-white/5">Sin Tratamiento</a>
                        <!-- <a href="{{ route('admin.informes.trazabilidad') }}" class="block p-2 rounded hover:bg-white/5">Trazabilidad de Usuarios</a> -->
                        <a href="{{ route('admin.informes.vida-util') }}" class="block p-2 rounded hover:bg-white/5">Vida Útil</a>
                        
                        <!-- <a href="{{ route('admin.informes.auditoria-ti.ra04') }}" class="block p-2 rounded hover:bg-white/5">RA-04 Clasificación por Criticidad</a> -->
                        <!-- <a href="{{ route('admin.informes.auditoria-ti.ra05') }}" class="block p-2 rounded hover:bg-white/5">RA-05 Roles y Permisos</a> -->
                        <!-- <a href="{{ route('admin.informes.auditoria-ti.ra06') }}" class="block p-2 rounded hover:bg-white/5">Verificación Física (CPD)</a> -->
                        <!-- <a href="{{ route('admin.informes.auditoria-ti.ra07') }}" class="block p-2 rounded hover:bg-white/5">Activos de Red</a> -->
                        <!-- <a href="{{ route('admin.informes.auditoria') }}" class="block p-2 rounded hover:bg-white/5">Informe General</a> -->
                        <a href="{{ route('admin.auditoria-interna.index') }}" class="block p-2 rounded hover:bg-white/5">Auditoría Interna de TI</a>
                    </div>
                </div>
            @endif

            @if(auth()->user()->tieneAccesoModulo('usuarios'))
                <div class="p-2 rounded hover:bg-white/5 has-submenu cursor-pointer" tabindex="0" role="button" aria-expanded="false" data-submenu-key="usuarios">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span>Usuarios</span>
                        </div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="submenu-arrow h-5 w-5 transform transition-transform text-white/90" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6 8l4 4 4-4" clip-rule="evenodd" /></svg>
                    </div>
                    <div class="submenu hidden mt-1 pl-3 space-y-0.5" data-submenu-panel="usuarios">
                        <a href="{{ route('admin.usuarios.index') }}" class="block p-2 rounded hover:bg-white/5">Lista de Usuarios</a>
                        <a href="{{ route('admin.usuarios.create') }}" class="block p-2 rounded hover:bg-white/5">Crear Usuario</a>
                    </div>
                </div>
            @endif

        </nav>
    </aside>

    {{-- ===== Right side: header + main content ===== --}}
    <div class="flex-1 flex flex-col min-h-0">

        {{-- Top header bar --}}
        <div class="bg-white shadow-sm border-b border-slate-100 p-3 px-6 flex justify-end items-center gap-3 shrink-0">
            <!-- Campanita de notificaciones -->
            <div class="relative" id="notifWrapper">
                @php
                    $notifs = $notificacionesData['items'] ?? [];
                    $totalNotifs = $notificacionesData['total'] ?? count($notifs);
                    $conteoCat = $notificacionesData['conteoPorCategoria'] ?? ['riesgos' => 0, 'incidentes' => 0, 'mantenimientos' => 0];
                @endphp
                <button id="notifToggle" class="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl transition relative focus:outline-none" title="Notificaciones" aria-label="Notificaciones" aria-expanded="false" aria-controls="notifMenu">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    @if($totalNotifs > 0)
                        <span id="notifBadge" class="absolute top-1 right-1 min-w-[18px] h-[18px] bg-rose-500 text-white text-[10px] font-bold flex items-center justify-center rounded-full ring-2 ring-white px-1">
                            {{ $totalNotifs > 9 ? '9+' : $totalNotifs }}
                        </span>
                    @else
                        <span id="notifBadge" class="hidden absolute top-1 right-1 min-w-[18px] h-[18px] bg-rose-500 text-white text-[10px] font-bold items-center justify-center rounded-full ring-2 ring-white px-1"></span>
                    @endif
                </button>

                <!-- Dropdown Menú Notificaciones -->
                <div id="notifMenu" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-100 z-50 overflow-hidden transform transition-all duration-150 origin-top-right">
                    <!-- Cabecera -->
                    <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 leading-tight">Notificaciones</h3>
                                <p class="text-[11px] text-slate-500 font-medium mt-0.5" id="notifSubtitle">
                                    <span id="notifTotalCount">{{ $totalNotifs }}</span> {{ $totalNotifs == 1 ? 'pendiente de atención' : 'pendientes de atención' }}
                                </p>
                            </div>
                        </div>

                        <button type="button" id="btnMarcarLeidas" onclick="marcarNotificacionesLeidas()" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold px-2.5 py-1 rounded-lg hover:bg-indigo-50 transition" title="Marcar todas como leídas">
                            Limpiar
                        </button>
                    </div>

                    <!-- Pestañas de Filtro Rápido -->
                    <div class="px-3 py-2 bg-slate-50/50 border-b border-slate-100 flex items-center gap-1.5 overflow-x-auto text-[11px]" id="notifTabs">
                        <button type="button" onclick="filtrarNotificaciones('todas', this)" class="notif-tab-btn px-2.5 py-1 rounded-lg font-bold bg-white text-indigo-700 shadow-xs border border-slate-200 shrink-0">
                            Todas (<span id="tabCountTodas">{{ $totalNotifs }}</span>)
                        </button>
                        @if($conteoCat['riesgos'] > 0)
                        <button type="button" onclick="filtrarNotificaciones('riesgos', this)" class="notif-tab-btn px-2.5 py-1 rounded-lg font-medium text-slate-600 hover:bg-white/80 shrink-0">
                            Riesgos ({{ $conteoCat['riesgos'] }})
                        </button>
                        @endif
                        @if($conteoCat['incidentes'] > 0)
                        <button type="button" onclick="filtrarNotificaciones('incidentes', this)" class="notif-tab-btn px-2.5 py-1 rounded-lg font-medium text-slate-600 hover:bg-white/80 shrink-0">
                            Incidentes ({{ $conteoCat['incidentes'] }})
                        </button>
                        @endif
                        @if($conteoCat['mantenimientos'] > 0)
                        <button type="button" onclick="filtrarNotificaciones('mantenimiento', this)" class="notif-tab-btn px-2.5 py-1 rounded-lg font-medium text-slate-600 hover:bg-white/80 shrink-0">
                            Mantenimiento ({{ $conteoCat['mantenimientos'] }})
                        </button>
                        @endif
                    </div>

                    <!-- Lista de Notificaciones -->
                    <div class="max-h-80 overflow-y-auto divide-y divide-slate-100" id="notifListContainer">
                        @forelse($notifs as $item)
                            <a href="{{ $item['url'] }}" class="notif-item block p-3.5 hover:bg-slate-50 transition group" data-category="{{ $item['categoria'] }}">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded-xl shrink-0 flex items-center justify-center text-xs font-bold {{ $item['icon_color'] ?? 'bg-slate-100 text-slate-600' }} shadow-xs mt-0.5">
                                        @if($item['icono'] === 'riesgos')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        @elseif($item['icono'] === 'incidentes')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-1 mb-0.5">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $item['badge_class'] ?? 'bg-slate-100 text-slate-700' }}">
                                                {{ $item['tipo_label'] }}
                                            </span>
                                            <span class="text-[10px] text-slate-400 font-medium shrink-0">{{ $item['fecha'] }}</span>
                                        </div>
                                        <h4 class="text-xs font-bold text-slate-800 truncate group-hover:text-indigo-600 transition">{{ $item['titulo'] }}</h4>
                                        <p class="text-[11px] text-slate-500 leading-snug line-clamp-2 mt-0.5">{{ $item['descripcion'] }}</p>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="py-10 px-4 text-center" id="emptyNotifState">
                                <div class="w-12 h-12 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-2.5 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h4 class="text-xs font-bold text-slate-800">¡Todo al día!</h4>
                                <p class="text-[11px] text-slate-400 mt-0.5">No tienes alertas ni incidentes pendientes.</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pie del Dropdown -->
                    <div class="p-2.5 bg-slate-50 border-t border-slate-100 text-center">
                        <a href="{{ route('dashboard') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition inline-flex items-center gap-1">
                            <span>Ir al Dashboard General</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Perfil de usuario con Rol -->
            <div class="relative">
                <button id="profileToggle" class="flex items-center gap-2.5 px-3 py-1.5 rounded-xl hover:bg-slate-100 focus:outline-none transition" aria-expanded="false" aria-controls="profileMenu" aria-label="Perfil">
                    <div class="w-9 h-9 rounded-full bg-[#1D3557] text-white flex items-center justify-center font-bold text-base shadow-sm shrink-0">
                        {{ strtoupper(substr(Auth::user()->nombre ?? Auth::user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="text-left hidden sm:block">
                        <div class="text-sm font-bold text-slate-800 leading-tight">
                            {{ Auth::user()->nombre ?? Auth::user()->name ?? 'Usuario' }}
                        </div>
                        <div class="text-[11px] font-semibold text-indigo-600 leading-tight">
                            {{ Auth::user()->rol->nombre_rol ?? 'Sin Rol' }}
                        </div>
                    </div>
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

        {{-- Contenido dinámico — solo esta parte se actualiza en la navegación SPA --}}
        <main id="main-content" class="flex-1 p-8 overflow-y-auto">
            {{-- Errores de validación por campo: los coloca junto a cada campo resources/js/errores-campos.js
                 (los del perfil van en su propia bolsa 'perfil' y se muestran en su modal). --}}
            {{-- Va en un atributo data- y no en un <script>: turbo-nav recrea los <script> copiando solo su texto. --}}
            @if($errors->getBag('default')->any())
                <div id="errores-validacion" class="hidden" data-errores="{{ json_encode($errors->getBag('default')->messages(), JSON_UNESCAPED_UNICODE) }}"></div>
            @endif

            @if(session('success'))
                <div class="mb-6 flex items-center justify-between p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-sm shadow-xs animate-fade-in">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm shrink-0">
                            ✓
                        </div>
                        <span class="font-semibold">{{ session('success') }}</span>
                    </div>
                    <button type="button" onclick="this.closest('.animate-fade-in').remove()" class="text-emerald-500 hover:text-emerald-700 p-1 rounded-lg hover:bg-emerald-100/50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 flex items-center justify-between p-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-sm shadow-xs animate-fade-in">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-sm shrink-0">
                            !
                        </div>
                        <span class="font-semibold">{{ session('error') }}</span>
                    </div>
                    <button type="button" onclick="this.closest('.animate-fade-in').remove()" class="text-rose-500 hover:text-rose-700 p-1 rounded-lg hover:bg-rose-100/50 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

<!-- Modal Editar Mi Perfil -->
<div id="profileEditModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200" onclick="if(event.target === this) closeProfileEditModal()">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 border border-slate-100 transform transition-all duration-200 scale-95 opacity-0" id="profileEditModalCard">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-base border border-indigo-100">
                    {{ strtoupper(substr(Auth::user()->nombre ?? Auth::user()->name ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">Editar Mi Perfil</h3>
                    <p class="text-xs text-slate-500">Actualiza tus datos personales y contraseña de acceso</p>
                </div>
            </div>
            <button type="button" onclick="closeProfileEditModal()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition focus:outline-none" aria-label="Cerrar modal">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        @if($errors->perfil->any())
            <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs space-y-1">
                @foreach($errors->perfil->all() as $error)
                    <div class="flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-rose-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('perfil.update') }}" class="space-y-4" data-turbo-disabled>
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nombre <span class="text-rose-500">*</span></label>
                    <input type="text" name="nombre" value="{{ old('nombre', Auth::user()->nombre ?? Auth::user()->name ?? '') }}" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Apellido</label>
                    <input type="text" name="apellido" value="{{ old('apellido', Auth::user()->apellido ?? '') }}" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Correo Electrónico <span class="text-rose-500">*</span></label>
                <input type="email" name="correo" value="{{ old('correo', Auth::user()->correo ?? Auth::user()->email ?? '') }}" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
            </div>

            <div class="pt-3 border-t border-slate-100 space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">Cambiar Contraseña</p>
                    <span class="text-[11px] text-slate-400 font-medium">(Dejar en blanco para mantener la actual)</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Nueva Contraseña</label>
                        <input type="password" name="contrasena" placeholder="Mínimo 6 caracteres" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    </div>

                    <div>
                        <label class="block text-xs text-slate-600 mb-1">Confirmar Contraseña</label>
                        <input type="password" name="contrasena_confirmation" placeholder="Repite la contraseña" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeProfileEditModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-sm font-bold text-white shadow-sm hover:shadow transition">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Inicialización de menús desplegables (Notificaciones, Perfil y Sidebar)
    (function () {
        function initDropdowns() {
            var notifBtn = document.getElementById('notifToggle');
            var notifMenu = document.getElementById('notifMenu');
            var profileBtn = document.getElementById('profileToggle');
            var profileMenu = document.getElementById('profileMenu');

            if (notifBtn && notifMenu) {
                notifBtn.onclick = function (e) {
                    e.stopPropagation();
                    if (profileMenu && !profileMenu.classList.contains('hidden')) {
                        profileMenu.classList.add('hidden');
                    }
                    notifMenu.classList.toggle('hidden');
                };
            }

            if (profileBtn && profileMenu) {
                profileBtn.onclick = function (e) {
                    e.stopPropagation();
                    if (notifMenu && !notifMenu.classList.contains('hidden')) {
                        notifMenu.classList.add('hidden');
                    }
                    profileMenu.classList.toggle('hidden');
                };
            }

            // Cerrar al hacer clic fuera
            document.onclick = function (e) {
                if (notifMenu && !notifMenu.classList.contains('hidden') && !e.target.closest('#notifWrapper')) {
                    notifMenu.classList.add('hidden');
                }
                if (profileMenu && !profileMenu.classList.contains('hidden') && !e.target.closest('#profileToggle') && !e.target.closest('#profileMenu')) {
                    profileMenu.classList.add('hidden');
                }
            };

            // Cerrar con tecla ESC
            document.onkeydown = function (e) {
                if (e.key === 'Escape') {
                    if (notifMenu) notifMenu.classList.add('hidden');
                    if (profileMenu) profileMenu.classList.add('hidden');
                    if (window.closeProfileEditModal) window.closeProfileEditModal();
                }
            };

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

            parents.forEach(function (p) {
                var key = p.getAttribute('data-submenu-key');
                var shouldOpenByPath = (
                    (key === 'activos' && currentPath.indexOf('/admin/activos/') !== -1) ||
                    (key === 'incidentes' && currentPath.indexOf('/admin/incidentes/') !== -1) ||
                    (key === 'mantenimiento' && currentPath.indexOf('/admin/mantenimiento/') !== -1) ||
                    (key === 'riesgos' && currentPath.indexOf('/admin/riesgos/') !== -1) ||
                    (key === 'informes' && currentPath.indexOf('/admin/informes/') !== -1) ||
                    (key === 'usuarios' && currentPath.indexOf('/admin/usuarios') !== -1)
                );

                if (key && (key === openKey || shouldOpenByPath)) {
                    setOpenState(p, true);
                    if (key) {
                        sessionStorage.setItem('openSubmenuKey', key);
                    }
                }

                p.onclick = function (e) {
                    if (e.target.closest('a')) return;
                    var submenu = p.querySelector('.submenu');
                    if (!submenu) return;

                    var shouldOpen = submenu.classList.contains('hidden');
                    setOpenState(p, shouldOpen);

                    if (key) {
                        sessionStorage.setItem('openSubmenuKey', shouldOpen ? key : '');
                    }
                };
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDropdowns);
        } else {
            initDropdowns();
        }
    })();

    // Filtrar notificaciones por categoría
    window.filtrarNotificaciones = function (categoria, btn) {
        var buttons = document.querySelectorAll('.notif-tab-btn');
        buttons.forEach(function (b) {
            b.className = 'notif-tab-btn px-2.5 py-1 rounded-lg font-medium text-slate-600 hover:bg-white/80 shrink-0';
        });
        if (btn) {
            btn.className = 'notif-tab-btn px-2.5 py-1 rounded-lg font-bold bg-white text-indigo-700 shadow-xs border border-slate-200 shrink-0';
        }

        var items = document.querySelectorAll('.notif-item');
        var visibleCount = 0;

        items.forEach(function (item) {
            var itemCat = item.getAttribute('data-category');
            if (categoria === 'todas' || itemCat === categoria) {
                item.style.display = 'block';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        var emptyState = document.getElementById('emptyNotifState');
        if (emptyState) {
            emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    };

    // Marcar notificaciones como leídas
    window.marcarNotificacionesLeidas = function () {
        var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        var badge = document.getElementById('notifBadge');
        var subtitle = document.getElementById('notifSubtitle');
        var btn = document.getElementById('btnMarcarLeidas');

        if (btn) {
            btn.innerText = 'Listo ✓';
            btn.disabled = true;
        }

        fetch("{{ route('notificaciones.marcar-leidas') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token || ''
            }
        })
        .then(function (res) { return res.json(); })
        .then(function () {
            if (badge) {
                badge.style.display = 'none';
            }
            if (subtitle) {
                subtitle.innerText = 'Todas las notificaciones leídas';
            }
        })
        .catch(function (err) {
            console.error('Error al marcar notificaciones:', err);
        });
    };

    // Modal Editar Perfil
    window.openProfileEditModal = function () {
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
    };

    window.closeProfileEditModal = function () {
        var modal = document.getElementById('profileEditModal');
        var card = document.getElementById('profileEditModalCard');
        if (modal && card) {
            card.classList.remove('scale-100', 'opacity-100');
            card.classList.add('scale-95', 'opacity-0');
            setTimeout(function () {
                modal.classList.add('hidden');
            }, 150);
        }
    };

    @if($errors->perfil->any())
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.openProfileEditModal === 'function') {
                window.openProfileEditModal();
            }
        });
    } else {
        if (typeof window.openProfileEditModal === 'function') {
            window.openProfileEditModal();
        }
    }
    @endif
</script>

@stack('scripts')

</body>
</html>
