@extends('layouts.principal')

@section('title', 'Ficha Técnica — ' . $activo->codigo_patrimonial)

@section('content')
@php
    // ── Estado operativo badge ──
    $estadoStr = strtolower(trim($activo->estado_operativo ?? ''));
    $estadoColor = match(true) {
        str_contains($estadoStr, 'operativo') && !str_contains($estadoStr, 'inoperativo')
            => 'bg-emerald-400/20 text-emerald-200 border-emerald-400/30',
        str_contains($estadoStr, 'mantenimiento')
            => 'bg-amber-400/20 text-amber-200 border-amber-400/30',
        str_contains($estadoStr, 'inoperativo') || str_contains($estadoStr, 'baja')
            => 'bg-rose-400/20 text-rose-200 border-rose-400/30',
        default => 'bg-white/10 text-white/70 border-white/20',
    };

    // ── Nivel de riesgo (última evaluación vigente) ──
    $evalVigente = $activo->evaluaciones->where('vigente', true)->sortByDesc('score_calculado')->first();
    $nivelRiesgo = $evalVigente->nivel_riesgo ?? null;
    $riesgoEmoji = match(strtolower($nivelRiesgo ?? '')) {
        'crítico', 'critico' => '🔴',
        'alto'               => '🟠',
        'medio'              => '🟡',
        'bajo'               => '🟢',
        default              => '⚪',
    };
    $riesgoColor = match(strtolower($nivelRiesgo ?? '')) {
        'crítico', 'critico' => 'bg-rose-400/20 text-rose-200 border-rose-400/30',
        'alto'               => 'bg-orange-400/20 text-orange-200 border-orange-400/30',
        'medio'              => 'bg-amber-400/20 text-amber-200 border-amber-400/30',
        'bajo'               => 'bg-emerald-400/20 text-emerald-200 border-emerald-400/30',
        default              => 'bg-white/10 text-white/70 border-white/20',
    };

    $mantenimientosCount = $activo->mantenimientos->count();
    $incidentesCount = $activo->incidentes->count();
    $evaluacionesCount = $activo->evaluaciones->count();
    $logsCount = $logs->count();
@endphp

<div class="max-w-7xl mx-auto space-y-6">

    @if(session('warning'))
        <div class="bg-amber-50 border border-amber-200 text-amber-800 px-5 py-3.5 rounded-2xl flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm">⚠</span>
                <span class="text-sm font-medium">{{ session('warning') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-amber-500 hover:text-amber-700 text-sm font-bold ml-4">✕</button>
        </div>
    @endif

    {{-- Encabezado con Botón de Regreso y Acciones --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.activos.inventario') }}" 
               class="btn btn-outline btn-icon" 
               aria-label="Volver al inventario" title="Volver al Inventario">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    Ficha Técnica <span class="font-mono text-blue-600 font-bold">#{{ $activo->codigo_patrimonial }}</span>
                </h2>
                <p class="text-xs text-slate-500">
                    {{ $activo->nombre_equipo }} &bull; {{ $activo->categoria->nombre_categoria ?? 'Sin Categoría' }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.activos.edit', $activo->id_activo) }}" class="btn btn-warning">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar Activo
            </a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════
         BANNER / CARD — Ficha Técnica del Activo
    ═══════════════════════════════════════════════ --}}
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1D3557] via-[#182e4b] to-[#12243d] text-white shadow-lg">
        {{-- Glow accents --}}
        <div class="absolute -top-20 -left-20 w-72 h-72 bg-blue-400/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-20 -right-20 w-72 h-72 bg-indigo-400/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative p-6 lg:p-8">
            {{-- Title row --}}
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-5">
                <div class="space-y-2">
                    <p class="text-xs uppercase tracking-[0.25em] text-white/50 font-semibold">Ficha Técnica Integral</p>
                    <h1 class="text-2xl lg:text-3xl font-bold leading-tight">
                        <span class="font-mono text-blue-300/90">{{ $activo->codigo_patrimonial }}</span>
                        <span class="mx-2 text-white/30">—</span>
                        {{ $activo->nombre_equipo }}
                    </h1>
                    <p class="text-sm text-white/60">
                        {{ $activo->categoria->nombre_categoria ?? 'Sin Categoría' }}
                        <span class="mx-1.5 text-white/30">•</span>
                        {{ $activo->marca ?? '' }} {{ $activo->modelo ? '— ' . $activo->modelo : '' }}
                    </p>
                </div>

                {{-- Badges --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Estado operativo --}}
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border {{ $estadoColor }}">
                        {{ $activo->estado_operativo }}
                    </span>
                    {{-- Riesgo --}}
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border {{ $riesgoColor }}">
                        {{ $riesgoEmoji }} Riesgo: {{ $nivelRiesgo ?? 'Sin evaluar' }}
                    </span>
                    {{-- Ciclo de vida --}}
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold border bg-white/10 text-white/70 border-white/20">
                        {{ $activo->etapa_ciclo_vida }}
                    </span>
                </div>
            </div>

            {{-- Quick stats row --}}
            <div class="mt-5 grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                <div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                    <p class="text-[10px] uppercase tracking-wider text-white/40 mb-0.5">Ubicación</p>
                    <p class="text-sm font-semibold text-white/90">{{ $activo->ubicacion->seccion ?? '-' }} {{ $activo->ubicacion->aula ? '— ' . $activo->ubicacion->aula : '' }}</p>
                </div>
                <div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                    <p class="text-[10px] uppercase tracking-wider text-white/40 mb-0.5">Adquisición</p>
                    <p class="text-sm font-semibold text-white/90 font-mono">{{ $activo->fecha_adquisicion ? \Carbon\Carbon::parse($activo->fecha_adquisicion)->format('d/m/Y') : '—' }}</p>
                </div>
                <div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5 cursor-pointer hover:bg-white/10 transition" onclick="activateTab('incidentes')">
                    <p class="text-[10px] uppercase tracking-wider text-white/40 mb-0.5">Incidentes</p>
                    <p class="text-sm font-semibold text-white/90">{{ $incidentesCount }}</p>
                </div>
                <div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5 cursor-pointer hover:bg-white/10 transition" onclick="activateTab('mantenimiento')">
                    <p class="text-[10px] uppercase tracking-wider text-white/40 mb-0.5">Mantenimientos</p>
                    <p class="text-sm font-semibold text-white/90">{{ $mantenimientosCount }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════
         TABS — Navigation
    ═══════════════════════════════════════════════ --}}
    <div>
        <nav class="flex gap-2 overflow-x-auto border-b border-slate-200 pb-px" id="ficha-tabs-nav" role="tablist">
            @php
                $tabConfig = [
                    'general'       => ['label' => 'General', 'count' => null],
                    'historial'     => ['label' => 'Historial', 'count' => $logsCount],
                    'mantenimiento' => ['label' => 'Mantenimiento', 'count' => $mantenimientosCount],
                    'incidentes'    => ['label' => 'Incidentes', 'count' => $incidentesCount],
                    'riesgo'        => ['label' => 'Riesgos', 'count' => $evaluacionesCount],
                ];
            @endphp

            @foreach($tabConfig as $key => $item)
                <button
                    type="button"
                    role="tab"
                    id="tab-btn-{{ $key }}"
                    aria-selected="{{ $key === 'general' ? 'true' : 'false' }}"
                    aria-controls="tab-panel-{{ $key }}"
                    data-tab="{{ $key }}"
                    onclick="activateTab('{{ $key }}')"
                    class="ficha-tab relative px-5 py-3 text-sm font-semibold rounded-t-xl transition-all cursor-pointer whitespace-nowrap flex items-center gap-2
                           {{ $key === 'general' 
                              ? 'text-[#1D3557] bg-white border-t-2 border-x border-[#1D3557]/20 border-b-transparent shadow-xs' 
                              : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/70 border-b-2 border-transparent' }}"
                >
                    <span>{{ $item['label'] }}</span>
                    @if($item['count'] !== null && $item['count'] > 0)
                        <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold {{ $key === 'general' ? 'bg-[#1D3557]/10 text-[#1D3557]' : 'bg-slate-200 text-slate-700' }}">
                            {{ $item['count'] }}
                        </span>
                    @endif
                </button>
            @endforeach
        </nav>

        {{-- ═══════════════════════════════════════════════
             TAB PANELS
        ═══════════════════════════════════════════════ --}}

        {{-- ── TAB 1: General ── --}}
        <div id="tab-panel-general" role="tabpanel" class="ficha-tab-panel pt-5" data-tab-panel="general">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Información General</h3>
                        <p class="text-xs text-slate-500">Datos identificativos y características físicas del activo</p>
                    </div>
                    <a href="{{ route('admin.activos.edit', $activo->id_activo) }}" class="btn btn-outline btn-sm">
                        Editar Información
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">ID Activo</span>
                        <span class="text-sm font-mono font-semibold text-slate-800">{{ $activo->id_activo }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Código Patrimonial</span>
                        <span class="text-sm font-mono font-semibold text-slate-800">{{ $activo->codigo_patrimonial }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Nombre del Equipo</span>
                        <span class="text-sm font-semibold text-slate-900">{{ $activo->nombre_equipo }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Categoría</span>
                        <span class="text-sm text-slate-700">{{ $activo->categoria->nombre_categoria ?? 'Sin Categoría' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Ubicación</span>
                        <span class="text-sm text-slate-700">{{ $activo->ubicacion->seccion ?? '-' }} {{ $activo->ubicacion->aula ? '- ' . $activo->ubicacion->aula : '' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Marca</span>
                        <span class="text-sm text-slate-700">{{ $activo->marca ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Modelo</span>
                        <span class="text-sm text-slate-700">{{ $activo->modelo ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Año de Adquisición</span>
                        <span class="text-sm text-slate-700 font-mono">{{ $activo->fecha_adquisicion ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Estado Operativo</span>
                        @php
                            $estadoCardColor = match(true) {
                                str_contains($estadoStr, 'operativo') && !str_contains($estadoStr, 'inoperativo') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                str_contains($estadoStr, 'mantenimiento') => 'bg-amber-100 text-amber-800 border-amber-200',
                                str_contains($estadoStr, 'inoperativo') || str_contains($estadoStr, 'baja') => 'bg-rose-100 text-rose-800 border-rose-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 mt-1 rounded-full text-xs font-bold border {{ $estadoCardColor }}">
                            {{ $activo->estado_operativo }}
                        </span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Etapa Ciclo de Vida</span>
                        <span class="text-sm text-slate-700 font-medium">{{ $activo->etapa_ciclo_vida }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Nivel de Riesgo</span>
                        @php
                            $riesgoVal = $activo->nivel_riesgo ?? $activo->criticidad_institucional ?? null;
                            $critStr = strtolower(trim($riesgoVal ?? ''));
                            $critColor = match(true) {
                                str_contains($critStr, 'alta') || str_contains($critStr, 'alto') || str_contains($critStr, 'crítico') || str_contains($critStr, 'critico') => 'bg-rose-100 text-rose-800 border-rose-200',
                                str_contains($critStr, 'media') || str_contains($critStr, 'medio') => 'bg-amber-100 text-amber-800 border-amber-200',
                                str_contains($critStr, 'baja') || str_contains($critStr, 'bajo') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-1 mt-1 rounded-full text-xs font-semibold border {{ $critColor }}">
                            {{ $riesgoVal ?? 'Sin evaluar' }}
                        </span>
                    </div>
                </div>

                {{-- Detalles de Computadora --}}
                @if($activo->detalleComputadora)
                    <div class="pt-4 border-t border-slate-100">
                        <h4 class="text-sm font-bold text-blue-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <span>💻</span> Especificaciones de Computadora / PC
                        </h4>
                        <div class="space-y-4 bg-slate-50 border border-blue-100 rounded-xl p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <span class="block text-xs font-medium text-slate-400 uppercase">Procesador</span>
                                    <span class="text-sm font-semibold text-slate-800">{{ $activo->detalleComputadora->procesador ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-medium text-slate-400 uppercase">Memoria RAM</span>
                                    <span class="text-sm font-semibold text-slate-800">{{ $activo->detalleComputadora->ram_gb ? $activo->detalleComputadora->ram_gb . ' GB' : '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-medium text-slate-400 uppercase">Almacenamiento SSD</span>
                                    <span class="text-sm font-semibold text-slate-800">{{ $activo->detalleComputadora->disco_solido_gb ? $activo->detalleComputadora->disco_solido_gb . ' GB' : '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-medium text-slate-400 uppercase">Almacenamiento HDD</span>
                                    <span class="text-sm font-semibold text-slate-800">{{ $activo->detalleComputadora->disco_rigido_gb ? $activo->detalleComputadora->disco_rigido_gb . ' GB' : '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-medium text-slate-400 uppercase">Sistema Operativo</span>
                                    <span class="text-sm font-semibold text-slate-800">{{ $activo->detalleComputadora->sistema_operativo ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="pt-2 border-t border-blue-100/50">
                                <span class="block text-xs font-medium text-slate-400 uppercase mb-2">Periféricos y Accesorios</span>
                                <div class="flex flex-wrap gap-2">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium {{ $activo->detalleComputadora->monitor ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-slate-200 text-slate-500' }}">Monitor: {{ $activo->detalleComputadora->monitor ? 'Sí' : 'No' }}</span>
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium {{ $activo->detalleComputadora->teclado_mouse ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-slate-200 text-slate-500' }}">Teclado/Mouse: {{ $activo->detalleComputadora->teclado_mouse ? 'Sí' : 'No' }}</span>
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium {{ $activo->detalleComputadora->parlantes ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-slate-200 text-slate-500' }}">Parlantes: {{ $activo->detalleComputadora->parlantes ? 'Sí' : 'No' }}</span>
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium {{ $activo->detalleComputadora->camara_web ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-slate-200 text-slate-500' }}">Cámara Web: {{ $activo->detalleComputadora->camara_web ? 'Sí' : 'No' }}</span>
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium {{ $activo->detalleComputadora->lector ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-slate-200 text-slate-500' }}">Lector: {{ $activo->detalleComputadora->lector ? 'Sí' : 'No' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Detalles de Red --}}
                @if($activo->detalleEquipoRed)
                    <div class="pt-4 border-t border-slate-100">
                        <h4 class="text-sm font-bold text-emerald-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <span>🌐</span> Especificaciones de Equipo de Red
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 border border-emerald-100 rounded-xl p-4">
                            <div>
                                <span class="block text-xs font-medium text-slate-400 uppercase">Dirección IP</span>
                                <span class="text-sm font-mono font-semibold text-slate-800">{{ $activo->detalleEquipoRed->direccion_ip ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-medium text-slate-400 uppercase">Versión Firmware</span>
                                <span class="text-sm font-mono font-semibold text-slate-800">{{ $activo->detalleEquipoRed->version_firmware ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Detalles de Proyector --}}
                @if($activo->detalleProyector)
                    <div class="pt-4 border-t border-slate-100">
                        <h4 class="text-sm font-bold text-amber-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                            <span>📽️</span> Especificaciones de Proyector Multimedia
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 border border-amber-100 rounded-xl p-4">
                            <div>
                                <span class="block text-xs font-medium text-slate-400 uppercase">Conectividad / Cable</span>
                                <span class="text-sm font-semibold text-slate-800">{{ $activo->detalleProyector->tipo_cable ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-medium text-slate-400 uppercase">Calidad / Lúmenes</span>
                                <span class="text-sm font-semibold text-slate-800">{{ $activo->detalleProyector->calidad_imagen ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                @if(!empty($activo->observaciones))
                    <div class="pt-4 border-t border-slate-100">
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Observaciones Generales</span>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-700 whitespace-pre-line">
                            {{ $activo->observaciones }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── TAB 2: Historial ── --}}
        <div id="tab-panel-historial" role="tabpanel" class="ficha-tab-panel pt-5 hidden" data-tab-panel="historial">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Historial y Bitácora del Activo</h3>
                        <p class="text-sm text-slate-500 mt-0.5">Auditoría cronológica de cambios, revisiones y anotaciones técnicas</p>
                    </div>
                    <button type="button" onclick="openModal('modalNuevaAnotacion')" class="btn btn-primary btn-sm flex items-center gap-1.5 self-start sm:self-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nueva Anotación
                    </button>
                </div>

                @if($logs->count())
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider w-12">N°</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha / Hora</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Usuario</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Acción / Tipo</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Detalle / Valor Anterior</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Valor Nuevo / Registro</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($logs as $log)
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="px-3 py-3.5 text-center text-sm font-semibold text-slate-500 font-mono">{{ $loop->iteration }}</td>
                                        <td class="px-5 py-3.5 text-sm text-slate-700 whitespace-nowrap font-mono">
                                            {{ \Carbon\Carbon::parse($log->fecha_hora)->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-5 py-3.5 text-sm whitespace-nowrap">
                                            <span class="inline-flex items-center gap-1.5 font-medium text-slate-800">
                                                <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 text-xs flex items-center justify-center font-bold">
                                                    {{ substr($log->usuario->nombre ?? 'S', 0, 1) }}
                                                </span>
                                                {{ $log->usuario ? $log->usuario->nombre . ' ' . ($log->usuario->apellido ?? '') : 'Sistema' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 text-sm font-medium text-slate-900 whitespace-nowrap">
                                            @php
                                                $esAnotacion = str_contains($log->accion_realizada, '[') || str_contains($log->accion_realizada, 'Bitácora');
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $esAnotacion ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                                {{ $log->accion_realizada }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 text-sm text-slate-500 max-w-xs truncate" title="{{ $log->valor_anterior }}">
                                            {{ $log->valor_anterior ?: '—' }}
                                        </td>
                                        <td class="px-5 py-3.5 text-sm text-slate-800 max-w-sm" title="{{ $log->valor_nuevo }}">
                                            <div class="line-clamp-2">{{ $log->valor_nuevo ?: '—' }}</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-12 text-center">
                        <div class="mx-auto w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h4 class="text-base font-semibold text-slate-800">No hay registros en el historial todavía</h4>
                        <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                            Las modificaciones sobre el activo se registrarán automáticamente. También puedes agregar una primera nota técnica o inspección física.
                        </p>
                        <div class="mt-4">
                            <button type="button" onclick="openModal('modalNuevaAnotacion')" class="btn btn-primary btn-sm">
                                + Registrar Primera Anotación
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── TAB 3: Mantenimiento ── --}}
        <div id="tab-panel-mantenimiento" role="tabpanel" class="ficha-tab-panel pt-5 hidden" data-tab-panel="mantenimiento">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h3 class="text-lg font-bold text-slate-900">Mantenimientos</h3>
                            @php
                                $mPendientes = $activo->mantenimientos->whereIn('estado_mantenimiento', \App\Support\Catalogo::estadosMantenimientoAbiertos())->count();
                            @endphp
                            @if($mPendientes > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    {{ $mPendientes }} pendiente{{ $mPendientes > 1 ? 's' : '' }}
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-slate-500 mt-0.5">Mantenimientos preventivos y correctivos programados para este activo</p>
                    </div>

                    <div class="flex items-center gap-2 self-start sm:self-auto">
                        <button type="button" onclick="openModal('modalProgramarMantenimiento')" class="btn btn-primary btn-sm flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Programar Mantenimiento
                        </button>
                        <a href="{{ route('admin.mantenimiento.cronograma') }}" class="btn btn-outline btn-sm">
                            Cronograma
                        </a>
                    </div>
                </div>

                @if($activo->mantenimientos->count())
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider w-12">N°</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Tipo</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha Prog.</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha Ejec.</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Descripción</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Técnico</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Estado</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($activo->mantenimientos->sortByDesc('fecha_programada') as $mant)
                                    @php
                                        $mSt = strtolower(trim($mant->estado_mantenimiento ?? ''));
                                        $mBadge = match(true) {
                                            str_contains($mSt, 'finalizado') || str_contains($mSt, 'completado') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            str_contains($mSt, 'ejecución') || str_contains($mSt, 'ejecucion') || str_contains($mSt, 'proceso') => 'bg-blue-100 text-blue-800 border-blue-200',
                                            str_contains($mSt, 'programado') || str_contains($mSt, 'pendiente') => 'bg-amber-100 text-amber-800 border-amber-200',
                                            str_contains($mSt, 'cancelado') => 'bg-rose-100 text-rose-800 border-rose-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                                        };

                                        $mTipoBadge = match(strtolower(trim($mant->tipo_mantenimiento ?? ''))) {
                                            'preventivo' => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'correctivo' => 'bg-orange-50 text-orange-700 border-orange-200',
                                            default => 'bg-slate-50 text-slate-600 border-slate-200',
                                        };
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-3 py-3 text-center text-sm font-semibold text-slate-500 font-mono">{{ $loop->iteration }}</td>
                                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $mTipoBadge }}">
                                                {{ $mant->tipo_mantenimiento }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-slate-700 font-mono whitespace-nowrap">{{ $mant->fecha_programada ? \Carbon\Carbon::parse($mant->fecha_programada)->format('d/m/Y') : '—' }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-slate-700 font-mono whitespace-nowrap">{{ $mant->fecha_ejecucion ? \Carbon\Carbon::parse($mant->fecha_ejecucion)->format('d/m/Y') : '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700 max-w-xs truncate" title="{{ $mant->descripcion_trabajo }}">{{ Str::limit($mant->descripcion_trabajo, 60) ?: '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">{{ $mant->usuarioAsignado ? $mant->usuarioAsignado->nombre . ' ' . ($mant->usuarioAsignado->apellido ?? '') : '—' }}</td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $mBadge }}">{{ $mant->estado_mantenimiento }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <a href="{{ route('admin.mantenimiento.show', $mant->id_mantenimiento) }}" class="btn btn-xs btn-primary" title="Ver detalles completos y evidencia">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                    Ver
                                                </a>
                                                @if(!$mant->estaRealizado())
                                                    <button type="button" 
                                                            onclick="openRealizarModal({{ $mant->id_mantenimiento }}, '{{ addslashes($activo->nombre_equipo) }}', '{{ addslashes($activo->codigo_patrimonial ?? '') }}', '{{ addslashes($mant->tipo_mantenimiento) }}', '{{ \Carbon\Carbon::parse($mant->fecha_programada)->format('d/m/Y') }}')" 
                                                            class="px-2 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] shadow-xs flex items-center gap-1 transition"
                                                            title="Registrar evidencia del mantenimiento">
                                                        Realizar ✓
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-12 text-center">
                        <div class="mx-auto w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <h4 class="text-base font-semibold text-slate-800">No hay mantenimientos registrados</h4>
                        <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                            Puedes programar un mantenimiento preventivo o correctivo directamente para este activo.
                        </p>
                        <div class="mt-4">
                            <button type="button" onclick="openModal('modalProgramarMantenimiento')" class="btn btn-primary btn-sm">
                                + Programar Primer Mantenimiento
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── TAB 4: Incidentes ── --}}
        <div id="tab-panel-incidentes" role="tabpanel" class="ficha-tab-panel pt-5 hidden" data-tab-panel="incidentes">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h3 class="text-lg font-bold text-slate-900">Incidentes Reportados</h3>
                            @php
                                $iAbiertos = $activo->incidentes->filter(fn($i) => !in_array($i->estado_incidente, \App\Support\Catalogo::estadosIncidenteCerrados()))->count();
                            @endphp
                            @if($iAbiertos > 0)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                    {{ $iAbiertos }} abierto{{ $iAbiertos > 1 ? 's' : '' }}
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-slate-500 mt-0.5">Historial y seguimiento de averías o fallas registradas sobre este activo</p>
                    </div>

                    <div class="flex items-center gap-2 self-start sm:self-auto">
                        <button type="button" onclick="openModal('modalReportarIncidente')" class="btn btn-primary btn-sm flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Reportar Incidente
                        </button>
                        <a href="{{ route('admin.incidentes.seguimiento') }}" class="btn btn-outline btn-sm">
                            Seguimiento
                        </a>
                    </div>
                </div>

                @if($activo->incidentes->count())
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider w-12">N°</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Descripción Falla</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Modo de Falla</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Prioridad</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Reportado Por</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Estado</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($activo->incidentes->sortByDesc('fecha_incidente') as $inc)
                                    @php
                                        $iSt = strtolower(trim($inc->estado_incidente ?? ''));
                                        $iBadge = match(true) {
                                            str_contains($iSt, 'resuelto') || str_contains($iSt, 'cerrado') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            str_contains($iSt, 'proceso') => 'bg-blue-100 text-blue-800 border-blue-200',
                                            str_contains($iSt, 'reportado') => 'bg-rose-100 text-rose-800 border-rose-200',
                                            str_contains($iSt, 'espera') => 'bg-amber-100 text-amber-800 border-amber-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                                        };

                                        $prioBadge = match(strtolower(trim($inc->prioridad ?? 'media'))) {
                                            'alta' => 'bg-rose-100 text-rose-700 border-rose-200',
                                            'baja' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                            default => 'bg-amber-100 text-amber-700 border-amber-200',
                                        };
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-3 py-3 text-center text-sm font-semibold text-slate-500 font-mono">{{ $loop->iteration }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-slate-700 font-mono whitespace-nowrap">{{ $inc->fecha_incidente ? \Carbon\Carbon::parse($inc->fecha_incidente)->format('d/m/Y') : '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700 max-w-xs truncate" title="{{ $inc->descripcion_falla }}">{{ Str::limit($inc->descripcion_falla, 60) ?: '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">{{ $inc->modoFalla->nombre_modo_falla ?? '—' }}</td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border {{ $prioBadge }}">
                                                {{ $inc->prioridad ?? 'Media' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">{{ $inc->usuarioReporta ? $inc->usuarioReporta->nombre . ' ' . ($inc->usuarioReporta->apellido ?? '') : '—' }}</td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $iBadge }}">{{ $inc->estado_incidente }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <a href="{{ route('admin.incidentes.show', $inc->id_incidente) }}" class="btn btn-xs btn-primary" title="Ver detalles del incidente">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Ver
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-12 text-center">
                        <div class="mx-auto w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <h4 class="text-base font-semibold text-slate-800">No hay incidentes reportados</h4>
                        <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                            Este activo no tiene averías o fallas registradas actualmente.
                        </p>
                        <div class="mt-4">
                            <button type="button" onclick="openModal('modalReportarIncidente')" class="btn btn-primary btn-sm">
                                + Reportar un Incidente
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── TAB 5: Riesgos (FMEA - ISO 31000) ── --}}
        <div id="tab-panel-riesgo" role="tabpanel" class="ficha-tab-panel pt-5 hidden" data-tab-panel="riesgo">
            {{-- Quick Risk Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Nivel de Riesgo Global</span>
                    <div class="flex items-center gap-2">
                        <span class="text-xl">{{ $riesgoEmoji }}</span>
                        <span class="text-lg font-bold text-slate-900">{{ $nivelRiesgo ?? 'Sin evaluar' }}</span>
                    </div>
                    <span class="text-[11px] text-slate-400 block mt-1">Según última evaluación vigente</span>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Score FMEA Máximo</span>
                    <div class="text-2xl font-black text-slate-900 font-mono">
                        {{ $evalVigente ? number_format($evalVigente->score_calculado, 2) : '0.00' }}
                    </div>
                    <span class="text-[11px] text-slate-400 block mt-1">Cálculo de Criticidad Global</span>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Evaluaciones Vigentes</span>
                    <div class="text-2xl font-black text-indigo-700 font-mono">
                        {{ $activo->evaluaciones->where('vigente', true)->count() }}
                    </div>
                    <span class="text-[11px] text-slate-400 block mt-1">Total de evaluaciones activas</span>
                </div>

                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-1">Planes de Tratamiento</span>
                    @php
                        $totalPlanes = $activo->evaluaciones->flatMap(fn($e) => $e->planesTratamiento)->count();
                    @endphp
                    <div class="text-2xl font-black text-slate-900 font-mono">
                        {{ $totalPlanes }}
                    </div>
                    <span class="text-[11px] text-slate-400 block mt-1">Acciones mitigadoras registradas</span>
                </div>
            </div>

            {{-- Evaluaciones FMEA Table --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Evaluaciones FMEA (ISO 31000)</h3>
                        <p class="text-sm text-slate-500 mt-0.5">Análisis de Modos de Falla, Criticidad y Niveles de Riesgo</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                        {{-- Formulario para evaluación automática con 1 clic --}}
                        <form method="POST" action="{{ route('admin.riesgos.evaluar.activo') }}" class="inline-block">
                            @csrf
                            <input type="hidden" name="fk_id_activo" value="{{ $activo->id_activo }}">
                            <input type="hidden" name="modo_evaluacion" value="todos">
                            <input type="hidden" name="retorno" value="ficha">
                            <input type="hidden" name="from_ficha" value="1">
                            <button type="submit" class="btn btn-primary btn-sm flex items-center gap-1.5 shadow-sm">
                                <span>⚡</span> Evaluar Activo Automático
                            </button>
                        </form>

                        <button type="button" onclick="openModal('modalEvaluacionManual')" class="btn btn-outline btn-sm flex items-center gap-1">
                            <span>+</span> Evaluar Modo Manual
                        </button>
                    </div>
                </div>

                @if($activo->evaluaciones->count())
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider w-12">N°</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Modo de Falla</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Score FMEA</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Nivel Riesgo</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Vigente</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($activo->evaluaciones->sortByDesc('fecha_evaluacion') as $eval)
                                    @php
                                        $nrStr = strtolower(trim($eval->nivel_riesgo ?? ''));
                                        $nrBadge = match(true) {
                                            str_contains($nrStr, 'crítico') || str_contains($nrStr, 'critico') => 'bg-rose-100 text-rose-800 border-rose-200',
                                            str_contains($nrStr, 'alto') => 'bg-orange-100 text-orange-800 border-orange-200',
                                            str_contains($nrStr, 'medio') => 'bg-amber-100 text-amber-800 border-amber-200',
                                            str_contains($nrStr, 'bajo') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                                        };
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition {{ $eval->vigente ? '' : 'opacity-60 bg-slate-50/30' }}">
                                        <td class="px-3 py-3.5 text-center text-sm font-semibold text-slate-500 font-mono">{{ $loop->iteration }}</td>
                                        <td class="px-6 py-3.5 text-sm font-medium text-slate-800">{{ $eval->modoFalla->nombre_modo_falla ?? '—' }}</td>
                                        <td class="px-4 py-3.5 text-center text-sm font-bold text-slate-900 font-mono">{{ number_format($eval->score_calculado, 2) }}</td>
                                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $nrBadge }}">{{ $eval->nivel_riesgo }}</span>
                                        </td>
                                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                            @if($eval->vigente)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Vigente</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">Histórico</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3.5 text-center text-sm text-slate-700 font-mono whitespace-nowrap">{{ $eval->fecha_evaluacion ? \Carbon\Carbon::parse($eval->fecha_evaluacion)->format('d/m/Y') : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-12 text-center">
                        <div class="mx-auto w-16 h-16 bg-slate-100 rounded-2xl flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <h4 class="text-base font-semibold text-slate-800">No se han realizado evaluaciones FMEA para este activo</h4>
                        <p class="text-sm text-slate-500 mt-1 max-w-md mx-auto">
                            Puedes disparar la evaluación automatizada según los modos de falla de su categoría ({{ $activo->categoria->nombre_categoria ?? 'general' }}), calculando severidad y desgaste.
                        </p>
                        <div class="mt-5 flex items-center justify-center gap-3">
                            <form method="POST" action="{{ route('admin.riesgos.evaluar.activo') }}">
                                @csrf
                                <input type="hidden" name="fk_id_activo" value="{{ $activo->id_activo }}">
                                <input type="hidden" name="modo_evaluacion" value="todos">
                                <input type="hidden" name="retorno" value="ficha">
                                <input type="hidden" name="from_ficha" value="1">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    ⚡ Evaluar Activo Automáticamente
                                </button>
                            </form>
                            <button type="button" onclick="openModal('modalEvaluacionManual')" class="btn btn-outline btn-sm">
                                + Evaluación Manual
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Planes de Tratamiento asociados --}}
            @php
                $planes = $activo->evaluaciones->flatMap(fn($e) => $e->planesTratamiento->map(function($p) use ($e) {
                    $p->_eval = $e;
                    return $p;
                }))->sortByDesc('created_at');
            @endphp
            @if($planes->count())
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Planes de Tratamiento de Riesgo</h3>
                            <p class="text-sm text-slate-500 mt-0.5">Acciones mitigadoras y preventivas derivadas de las evaluaciones</p>
                        </div>
                        <a href="{{ route('admin.riesgos.tratamiento') }}" class="btn btn-outline btn-sm">
                            Gestionar Tratamientos
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider w-12">N°</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Evaluación / Modo</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Estrategia</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Acción a Realizar</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha Compromiso</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($planes as $plan)
                                    @php
                                        $ptSt = strtolower(trim($plan->estado_tratamiento ?? ''));
                                        $ptBadge = match(true) {
                                            str_contains($ptSt, 'completado') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            str_contains($ptSt, 'proceso') => 'bg-blue-100 text-blue-800 border-blue-200',
                                            str_contains($ptSt, 'pendiente') => 'bg-amber-100 text-amber-800 border-amber-200',
                                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                                        };
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-3 py-3 text-center text-sm font-semibold text-slate-500 font-mono">{{ $loop->iteration }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">
                                            #{{ $plan->_eval->id_evaluacion ?? '—' }}
                                            <span class="text-slate-400">—</span>
                                            {{ $plan->_eval->modoFalla->nombre_modo_falla ?? '' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold text-slate-800">{{ $plan->estrategia ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700 max-w-xs truncate" title="{{ $plan->accion_preventiva }}">{{ Str::limit($plan->accion_preventiva, 60) ?: '—' }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-slate-700 font-mono whitespace-nowrap">{{ $plan->fecha_compromiso ? \Carbon\Carbon::parse($plan->fecha_compromiso)->format('d/m/Y') : '—' }}</td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $ptBadge }}">{{ $plan->estado_tratamiento }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

    </div> {{-- end tabs wrapper --}}

</div>

{{-- ═══════════════════════════════════════════════════════════════════
     MODALES INTERACTIVOS (Historial, Mantenimiento, Incidentes, Riesgo)
═══════════════════════════════════════════════════════════════════════ --}}

{{-- 1. Modal: Nueva Anotación en Bitácora / Historial --}}
<div id="modalNuevaAnotacion" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 border border-slate-100 transform transition-transform" id="modalNuevaAnotacionCard">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <span>📝</span> Registrar Anotación en Bitácora
            </h3>
            <button type="button" onclick="closeModal('modalNuevaAnotacion')" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.activos.historial.store', $activo->id_activo) }}">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Tipo de Evento</label>
                    <select name="tipo_evento" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                        <option value="Inspección Física">Inspección Física / Revisión de Rutina</option>
                        <option value="Revisión Técnica">Revisión Técnica de Hardware</option>
                        <option value="Actualización Software">Actualización de Software / SO</option>
                        <option value="Traslado de Ubicación">Traslado / Reubicación de Equipo</option>
                        <option value="Cambio de Responsable">Cambio de Usuario Responsable</option>
                        <option value="Observación General">Observación General / Nota Técnica</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Título / Concepto de la Acción <span class="text-rose-500">*</span></label>
                    <input type="text" name="accion_realizada" required placeholder="Ej: Limpieza de componentes internos y verificación térmica" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Detalles / Observaciones</label>
                    <textarea name="observaciones" rows="4" placeholder="Describe los hallazgos técnicos o motivos..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-5 mt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modalNuevaAnotacion')" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Guardar en Historial
                </button>
            </div>
        </form>
    </div>
</div>

{{-- 2. Modal: Programar Mantenimiento para este Activo --}}
<div id="modalProgramarMantenimiento" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200">
    <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-5 border border-slate-100 max-h-[90vh] overflow-y-auto" id="modalProgramarMantenimientoCard">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <span>🛠️</span> Programar Mantenimiento
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Activo: <strong class="text-slate-800">{{ $activo->codigo_patrimonial }}</strong> — {{ $activo->nombre_equipo }}</p>
            </div>
            <button type="button" onclick="closeModal('modalProgramarMantenimiento')" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.mantenimiento.programar.store') }}">
            @csrf
            <input type="hidden" name="fk_id_activo" value="{{ $activo->id_activo }}">
            <input type="hidden" name="retorno" value="ficha">
            <input type="hidden" name="from_ficha" value="1">

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Tipo de Mantenimiento <span class="text-rose-500">*</span></label>
                        <select name="tipo_mantenimiento" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                            @foreach(\App\Support\Catalogo::filas('tipos_mantenimiento') as $tipo)
                                <option value="{{ $tipo->nombre }}">{{ \App\Support\Catalogo::etiqueta($tipo) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Técnico Asignado <span class="text-rose-500">*</span></label>
                        <select name="fk_id_usuario_asignado" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                            @foreach($usuarios as $usr)
                                <option value="{{ $usr->id_usuario }}" {{ (auth()->id() == $usr->id_usuario) ? 'selected' : '' }}>
                                    {{ $usr->nombre }} {{ $usr->apellido ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Fecha Programada <span class="text-rose-500">*</span></label>
                        <input type="date" name="fecha_programada" required value="{{ date('Y-m-d') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition font-mono">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Estado Inicial <span class="text-rose-500">*</span></label>
                        <select name="estado_mantenimiento" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                            @foreach(\App\Support\Catalogo::estadosMantenimientoParaRegistro() as $estado)
                                <option value="{{ $estado->nombre }}">{{ $estado->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if($activo->incidentes->whereNotIn('estado_incidente', \App\Support\Catalogo::estadosIncidenteCerrados())->count())
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Vincular con Incidente Abierto (Opcional)</label>
                        <select name="fk_id_incidente" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                            <option value="">Ninguno — Mantenimiento Preventivo Regular</option>
                            @foreach($activo->incidentes->whereNotIn('estado_incidente', \App\Support\Catalogo::estadosIncidenteCerrados()) as $incAbierto)
                                <option value="{{ $incAbierto->id_incidente }}">
                                    #{{ $incAbierto->id_incidente }} — {{ Str::limit($incAbierto->descripcion_falla, 50) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Descripción de los Trabajos a Realizar</label>
                    <textarea name="descripcion_trabajo" rows="3" placeholder="Detalla el alcance del mantenimiento, piezas a reemplazar, limpieza, etc..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-5 mt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modalProgramarMantenimiento')" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Programar Mantenimiento
                </button>
            </div>
        </form>
    </div>
</div>

{{-- 3. Modal: Reportar Incidente para este Activo --}}
<div id="modalReportarIncidente" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200">
    <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-5 border border-slate-100 max-h-[90vh] overflow-y-auto" id="modalReportarIncidenteCard">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <span>⚠️</span> Reportar Incidente / Avería
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">Activo: <strong class="text-slate-800">{{ $activo->codigo_patrimonial }}</strong> — {{ $activo->nombre_equipo }}</p>
            </div>
            <button type="button" onclick="closeModal('modalReportarIncidente')" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.incidentes.registrar.store') }}">
            @csrf
            <input type="hidden" name="fk_id_activo" value="{{ $activo->id_activo }}">
            <input type="hidden" name="fk_id_usuario_reporta" value="{{ auth()->id() ?? ($usuarios->first()->id_usuario ?? 1) }}">
            <input type="hidden" name="retorno" value="ficha">
            <input type="hidden" name="from_ficha" value="1">

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Fecha del Incidente <span class="text-rose-500">*</span></label>
                        <input type="date" name="fecha_incidente" required value="{{ date('Y-m-d') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition font-mono">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Prioridad de Atención <span class="text-rose-500">*</span></label>
                        @php
                            // Prioridad sugerida según el nivel de riesgo del activo (catálogo niveles_riesgo).
                            $sugerida = \App\Support\Catalogo::prioridadSugeridaPorNivel()[$activo->nivel_riesgo ?? ''] ?? 'Baja';
                        @endphp
                        <select name="prioridad" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                            @foreach(\App\Support\Catalogo::filas('prioridades_incidente') as $prioridad)
                                <option value="{{ $prioridad->nombre }}" {{ $sugerida === $prioridad->nombre ? 'selected' : '' }}>{{ \App\Support\Catalogo::etiqueta($prioridad) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Modo de Falla (Catálogo FMEA)</label>
                    <select name="fk_id_modo_falla" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                        <option value="">-- No determinado / Fallo genérico --</option>
                        @foreach($modosFalla as $modo)
                            <option value="{{ $modo->id_modo_falla }}">{{ $modo->nombre_modo_falla }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Descripción Detallada de la Falla <span class="text-rose-500">*</span></label>
                    <textarea name="descripcion_falla" required rows="3" placeholder="Indica los síntomas observados, mensajes de error o comportamiento anómalo..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Estado Inicial</label>
                    <select name="estado_incidente" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                        @foreach(\App\Support\Catalogo::estadosIncidenteAbiertos() as $estado)
                            <option value="{{ $estado->nombre }}" {{ $loop->first ? 'selected' : '' }}>{{ \App\Support\Catalogo::etiqueta($estado) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-5 mt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modalReportarIncidente')" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Reportar Incidente
                </button>
            </div>
        </form>
    </div>
</div>

{{-- 4. Modal: Evaluación FMEA Manual para este Activo --}}
<div id="modalEvaluacionManual" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 border border-slate-100 max-h-[90vh] overflow-y-auto" id="modalEvaluacionManualCard">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                    <span>⚡</span> Evaluación FMEA Manual
                </h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ $activo->nombre_equipo }} ({{ $activo->codigo_patrimonial }})</p>
            </div>
            <button type="button" onclick="closeModal('modalEvaluacionManual')" class="text-slate-400 hover:text-slate-600 text-lg font-bold p-1">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.riesgos.evaluacion.store') }}">
            @csrf
            <input type="hidden" name="fk_id_activo" value="{{ $activo->id_activo }}">
            <input type="hidden" name="retorno" value="ficha">
            <input type="hidden" name="from_ficha" value="1">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Modo de Falla a Evaluar <span class="text-rose-500">*</span></label>
                    <select name="fk_id_modo_falla" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                        @foreach($modosFalla as $modo)
                            <option value="{{ $modo->id_modo_falla }}">{{ $modo->nombre_modo_falla }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1" title="Severidad de impacto">Severidad (S) <span class="text-rose-500">*</span></label>
                        <select name="valor_severidad" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 font-mono">
                            @for($i=1; $i<=10; $i++)
                                <option value="{{ $i }}" {{ $i === 5 ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1" title="Frecuencia / Ocurrencia">Frecuencia (F) <span class="text-rose-500">*</span></label>
                        <select name="valor_frecuencia" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 font-mono">
                            @for($i=1; $i<=10; $i++)
                                <option value="{{ $i }}" {{ $i === 5 ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1" title="Medidas de Reducción / Detección">MSR <span class="text-rose-500">*</span></label>
                        <select name="valor_msr" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 font-mono">
                            @for($i=1; $i<=10; $i++)
                                <option value="{{ $i }}" {{ $i === 5 ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Fecha de Evaluación <span class="text-rose-500">*</span></label>
                    <input type="date" name="fecha_evaluacion" required value="{{ date('Y-m-d') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 font-mono">
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-5 mt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('modalEvaluacionManual')" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Calcular Score y Guardar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════
     SCRIPTS EMBEBIDOS EN EL CONTENIDO (Compatible con Turbo SPA y Standalone)
═══════════════════════════════════════════════════════════════════════ --}}
<script>
(function () {
    // ── Función Global para Activación de Pestañas ──
    window.activateTab = function (key) {
        var tabs = document.querySelectorAll('.ficha-tab');
        var panels = document.querySelectorAll('.ficha-tab-panel');

        if (!tabs.length || !panels.length) return;

        tabs.forEach(function (t) {
            var isActive = (t.getAttribute('data-tab') === key);
            t.setAttribute('aria-selected', isActive ? 'true' : 'false');

            if (isActive) {
                t.className = 'ficha-tab relative px-5 py-3 text-sm font-semibold rounded-t-xl transition-all cursor-pointer whitespace-nowrap flex items-center gap-2 text-[#1D3557] bg-white border-t-2 border-x border-[#1D3557]/20 border-b-transparent shadow-xs';
            } else {
                t.className = 'ficha-tab relative px-5 py-3 text-sm font-semibold rounded-t-xl transition-all cursor-pointer whitespace-nowrap flex items-center gap-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100/70 border-b-2 border-transparent';
            }
        });

        panels.forEach(function (p) {
            var isPanel = (p.getAttribute('data-tab-panel') === key);
            p.classList.toggle('hidden', !isPanel);
        });

        // Actualizar URL hash sin salto de scroll
        if (history.replaceState) {
            history.replaceState(null, '', '#' + key);
        }
    };

    // ── Funciones Globales para Control de Modales ──
    window.openModal = function (modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) return;
        var card = document.getElementById(modalId + 'Card');
        modal.classList.remove('hidden');
        if (card) {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        }
    };

    window.closeModal = function (modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) return;
        var card = document.getElementById(modalId + 'Card');
        if (card) {
            card.classList.remove('scale-100', 'opacity-100');
            card.classList.add('scale-95', 'opacity-0');
        }
        setTimeout(function () {
            modal.classList.add('hidden');
        }, 150);
    };

    // Cerrar modales con tecla Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            ['modalNuevaAnotacion', 'modalProgramarMantenimiento', 'modalReportarIncidente', 'modalEvaluacionManual'].forEach(function (id) {
                window.closeModal(id);
            });
        }
    });

    // Cerrar al hacer clic en el fondo del modal
    ['modalNuevaAnotacion', 'modalProgramarMantenimiento', 'modalReportarIncidente', 'modalEvaluacionManual'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener('click', function (e) {
                if (e.target === el) {
                    window.closeModal(id);
                }
            });
        }
    });

    // ── Inicialización de Pestañas según Hash o General ──
    window.initFichaTabs = function () {
        var hash = (window.location.hash || '').replace('#', '').trim();
        var validTabs = ['general', 'historial', 'mantenimiento', 'incidentes', 'riesgo'];
        if (hash && validTabs.indexOf(hash) !== -1) {
            window.activateTab(hash);
        } else {
            window.activateTab('general');
        }
    };

    // Ejecución inmediata y escucha de eventos
    window.initFichaTabs();
    window.addEventListener('hashchange', window.initFichaTabs);
    document.addEventListener('turbo:load', window.initFichaTabs);
})();
</script>

@include('mantenimiento.partials.modal-realizar')
@endsection
