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
@endphp

<div class="max-w-7xl mx-auto space-y-6">

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

        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.activos.edit', $activo->id_activo) }}" class="btn btn-warning">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar Activo
            </a>
            <a href="{{ route('admin.activos.inventario') }}" class="btn btn-secondary">
                Volver al Inventario
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
                    <p class="text-xs uppercase tracking-[0.25em] text-white/50 font-semibold">Ficha Técnica</p>
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
                <div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                    <p class="text-[10px] uppercase tracking-wider text-white/40 mb-0.5">Incidentes</p>
                    <p class="text-sm font-semibold text-white/90">{{ $activo->incidentes->count() }}</p>
                </div>
                <div class="rounded-xl bg-white/5 border border-white/10 px-3 py-2.5">
                    <p class="text-[10px] uppercase tracking-wider text-white/40 mb-0.5">Mantenimientos</p>
                    <p class="text-sm font-semibold text-white/90">{{ $activo->mantenimientos->count() }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════
         TABS — Navigation
    ═══════════════════════════════════════════════ --}}
    <div>
        <nav class="flex gap-1 overflow-x-auto border-b border-slate-200 pb-px" id="ficha-tabs-nav" role="tablist">
            @foreach(['general' => 'General', 'historial' => 'Historial', 'mantenimiento' => 'Mantenimiento', 'incidentes' => 'Incidentes', 'riesgo' => 'Riesgo'] as $key => $label)
                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $key === 'general' ? 'true' : 'false' }}"
                    aria-controls="tab-panel-{{ $key }}"
                    data-tab="{{ $key }}"
                    class="ficha-tab relative px-4 py-2.5 text-sm font-medium rounded-t-lg transition whitespace-nowrap
                           {{ $key === 'general' ? 'text-[#1D3557] after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-[#1D3557] after:rounded-full' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50' }}"
                >
                    {{ $label }}
                    @if($key === 'incidentes' && $activo->incidentes->count())
                        <span class="ml-1.5 inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700">{{ $activo->incidentes->count() }}</span>
                    @elseif($key === 'mantenimiento' && $activo->mantenimientos->count())
                        <span class="ml-1.5 inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700">{{ $activo->mantenimientos->count() }}</span>
                    @elseif($key === 'riesgo' && $activo->evaluaciones->count())
                        <span class="ml-1.5 inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700">{{ $activo->evaluaciones->count() }}</span>
                    @endif
                </button>
            @endforeach
        </nav>

        {{-- ═══════════════════════════════════════════════
             TAB PANELS
        ═══════════════════════════════════════════════ --}}

        {{-- ── TAB: General ── --}}
        <div id="tab-panel-general" role="tabpanel" class="ficha-tab-panel pt-5" data-tab-panel="general">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8 space-y-6">
                <h3 class="text-lg font-bold text-slate-900 border-b border-slate-100 pb-3">Información General</h3>

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
                        <span class="text-sm text-slate-700">{{ $activo->fecha_adquisicion ?? '-' }}</span>
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
                        <span class="text-sm text-slate-700">{{ $activo->etapa_ciclo_vida }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Criticidad Institucional</span>
                        @php
                            $critStr = strtolower(trim($activo->criticidad_institucional ?? ''));
                            $critColor = match(true) {
                                str_contains($critStr, 'alta') || str_contains($critStr, 'alto') || str_contains($critStr, 'crítico') || str_contains($critStr, 'critico') => 'bg-rose-100 text-rose-800 border-rose-200',
                                str_contains($critStr, 'media') || str_contains($critStr, 'medio') => 'bg-amber-100 text-amber-800 border-amber-200',
                                str_contains($critStr, 'baja') || str_contains($critStr, 'bajo') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-1 mt-1 rounded-full text-xs font-semibold border {{ $critColor }}">
                            {{ $activo->criticidad_institucional ?? '-' }}
                        </span>
                    </div>
                </div>

                {{-- Detalles de Computadora --}}
                @if($activo->detalleComputadora)
                    <div class="pt-4 border-t border-slate-100">
                        <h4 class="text-sm font-bold text-blue-700 uppercase tracking-wider mb-3">Especificaciones de Computadora / PC</h4>
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
                                    <span class="block text-xs font-medium text-slate-400 uppercase">Disco Sólido SSD</span>
                                    <span class="text-sm font-semibold text-slate-800">{{ $activo->detalleComputadora->disco_solido_gb ? $activo->detalleComputadora->disco_solido_gb . ' GB' : '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-medium text-slate-400 uppercase">Disco Rígido HDD</span>
                                    <span class="text-sm font-semibold text-slate-800">{{ $activo->detalleComputadora->disco_rigido_gb ? $activo->detalleComputadora->disco_rigido_gb . ' GB' : '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs font-medium text-slate-400 uppercase">Sistema Operativo</span>
                                    <span class="text-sm font-semibold text-slate-800">{{ $activo->detalleComputadora->sistema_operativo ?? '-' }}</span>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-blue-100/60">
                                <span class="block text-xs font-medium text-slate-400 uppercase mb-2">Componentes y Periféricos Incluidos</span>
                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $activo->detalleComputadora->monitor ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                                        Monitor: {{ $activo->detalleComputadora->monitor ? 'Sí' : 'No' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $activo->detalleComputadora->teclado_mouse ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                                        Teclado / Mouse: {{ $activo->detalleComputadora->teclado_mouse ? 'Sí' : 'No' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $activo->detalleComputadora->camara_web ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                                        Cámara Web: {{ $activo->detalleComputadora->camara_web ? 'Sí' : 'No' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $activo->detalleComputadora->parlantes ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                                        Parlantes: {{ $activo->detalleComputadora->parlantes ? 'Sí' : 'No' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $activo->detalleComputadora->lector ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                                        Lector: {{ $activo->detalleComputadora->lector ? 'Sí' : 'No' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Detalles de Equipo de Red --}}
                @if($activo->detalleEquipoRed)
                    <div class="pt-4 border-t border-slate-100">
                        <h4 class="text-sm font-bold text-emerald-700 uppercase tracking-wider mb-3">Especificaciones de Equipo de Red / Servidor / Impresora</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 border border-emerald-100 rounded-xl p-4">
                            <div>
                                <span class="block text-xs font-medium text-slate-400 uppercase">Dirección IP</span>
                                <span class="text-sm font-mono font-semibold text-slate-800">{{ $activo->detalleEquipoRed->direccion_ip ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-medium text-slate-400 uppercase">Firmware</span>
                                <span class="text-sm font-semibold text-slate-800">{{ $activo->detalleEquipoRed->version_firmware ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Detalles de Proyector --}}
                @if($activo->detalleProyector)
                    <div class="pt-4 border-t border-slate-100">
                        <h4 class="text-sm font-bold text-amber-700 uppercase tracking-wider mb-3">Especificaciones de Proyector Multimedia</h4>
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
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Observaciones</span>
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-700">
                            {{ $activo->observaciones }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── TAB: Historial ── --}}
        <div id="tab-panel-historial" role="tabpanel" class="ficha-tab-panel pt-5 hidden" data-tab-panel="historial">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-900">Historial de Cambios</h3>
                    <p class="text-sm text-slate-500 mt-0.5">Registro de auditoría de modificaciones realizadas sobre este activo</p>
                </div>

                @if($logs->count())
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha / Hora</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Usuario</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Acción</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Valor Anterior</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Valor Nuevo</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($logs as $log)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap font-mono">
                                            {{ \Carbon\Carbon::parse($log->fecha_hora)->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">
                                            {{ $log->usuario ? $log->usuario->nombre . ' ' . ($log->usuario->apellido ?? '') : 'Sistema' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700">
                                            {{ $log->accion_realizada }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-500 max-w-xs truncate" title="{{ $log->valor_anterior }}">
                                            {{ Str::limit($log->valor_anterior, 80) ?: '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-500 max-w-xs truncate" title="{{ $log->valor_nuevo }}">
                                            {{ Str::limit($log->valor_nuevo, 80) ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-10 text-center">
                        <div class="mx-auto w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="text-sm font-medium text-slate-500">No hay registros de cambios para este activo.</p>
                        <p class="text-xs text-slate-400 mt-1">El historial se actualiza automáticamente con cada modificación.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── TAB: Mantenimiento ── --}}
        <div id="tab-panel-mantenimiento" role="tabpanel" class="ficha-tab-panel pt-5 hidden" data-tab-panel="mantenimiento">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Mantenimientos</h3>
                        <p class="text-sm text-slate-500 mt-0.5">Todos los mantenimientos programados y ejecutados para este activo</p>
                    </div>
                    @php
                        $mPendientes = $activo->mantenimientos->whereIn('estado_mantenimiento', ['Pendiente', 'Programado', 'En ejecución'])->count();
                    @endphp
                    @if($mPendientes > 0)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            {{ $mPendientes }} pendiente{{ $mPendientes > 1 ? 's' : '' }}
                        </span>
                    @endif
                </div>

                @if($activo->mantenimientos->count())
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
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
                                        <td class="px-4 py-3 text-center text-sm text-slate-700 font-mono">{{ $mant->id_mantenimiento }}</td>
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
                                            <a href="{{ route('admin.mantenimiento.show', $mant->id_mantenimiento) }}" class="btn btn-xs btn-primary" title="Ver detalles">
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
                    <div class="p-10 text-center">
                        <div class="mx-auto w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <p class="text-sm font-medium text-slate-500">No se han registrado mantenimientos para este activo.</p>
                        <p class="text-xs text-slate-400 mt-1">Los mantenimientos programados y ejecutados aparecerán aquí.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── TAB: Incidentes ── --}}
        <div id="tab-panel-incidentes" role="tabpanel" class="ficha-tab-panel pt-5 hidden" data-tab-panel="incidentes">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Incidentes Reportados</h3>
                        <p class="text-sm text-slate-500 mt-0.5">Todos los incidentes registrados que afectan a este activo</p>
                    </div>
                    @php
                        $iAbiertos = $activo->incidentes->filter(fn($i) => !in_array($i->estado_incidente, ['Resuelto', 'Cerrado']))->count();
                    @endphp
                    @if($iAbiertos > 0)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                            {{ $iAbiertos }} abierto{{ $iAbiertos > 1 ? 's' : '' }}
                        </span>
                    @endif
                </div>

                @if($activo->incidentes->count())
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Descripción Falla</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Modo de Falla</th>
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
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-3 text-center text-sm text-slate-700 font-mono">{{ $inc->id_incidente }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-slate-700 font-mono whitespace-nowrap">{{ $inc->fecha_incidente ? \Carbon\Carbon::parse($inc->fecha_incidente)->format('d/m/Y') : '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700 max-w-xs truncate" title="{{ $inc->descripcion_falla }}">{{ Str::limit($inc->descripcion_falla, 60) ?: '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">{{ $inc->modoFalla->nombre_modo_falla ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">{{ $inc->usuarioReporta ? $inc->usuarioReporta->nombre . ' ' . ($inc->usuarioReporta->apellido ?? '') : '—' }}</td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $iBadge }}">{{ $inc->estado_incidente }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center whitespace-nowrap">
                                            <a href="{{ route('admin.incidentes.show', $inc->id_incidente) }}" class="btn btn-xs btn-primary" title="Ver detalles">
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
                    <div class="p-10 text-center">
                        <div class="mx-auto w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        </div>
                        <p class="text-sm font-medium text-slate-500">No se han reportado incidentes para este activo.</p>
                        <p class="text-xs text-slate-400 mt-1">Los incidentes registrados aparecerán aquí.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── TAB: Riesgo ── --}}
        <div id="tab-panel-riesgo" role="tabpanel" class="ficha-tab-panel pt-5 hidden" data-tab-panel="riesgo">
            {{-- Evaluaciones FMEA --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-5">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Evaluaciones FMEA</h3>
                        <p class="text-sm text-slate-500 mt-0.5">Análisis de modos de falla, efectos y criticidad registrados para este activo</p>
                    </div>
                    @php
                        $evalVigentes = $activo->evaluaciones->where('vigente', true)->count();
                    @endphp
                    @if($evalVigentes > 0)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                            {{ $evalVigentes }} vigente{{ $evalVigentes > 1 ? 's' : '' }}
                        </span>
                    @endif
                </div>

                @if($activo->evaluaciones->count())
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                                    <th class="px-3 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Modo de Falla</th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">S</th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">F</th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">MSR</th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Score</th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Nivel Riesgo</th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Vigente</th>
                                    <th class="px-3 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha</th>
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
                                    <tr class="hover:bg-slate-50/50 transition {{ $eval->vigente ? '' : 'opacity-60' }}">
                                        <td class="px-3 py-3 text-center text-sm text-slate-700 font-mono">{{ $eval->id_evaluacion }}</td>
                                        <td class="px-3 py-3 text-sm text-slate-700">{{ $eval->modoFalla->nombre_modo_falla ?? '—' }}</td>
                                        <td class="px-3 py-3 text-center text-sm font-semibold text-slate-800">{{ $eval->valor_severidad }}</td>
                                        <td class="px-3 py-3 text-center text-sm font-semibold text-slate-800">{{ $eval->valor_frecuencia }}</td>
                                        <td class="px-3 py-3 text-center text-sm font-semibold text-slate-800">{{ $eval->valor_msr }}</td>
                                        <td class="px-3 py-3 text-center text-sm font-bold text-slate-900">{{ number_format($eval->score_calculado, 2) }}</td>
                                        <td class="px-3 py-3 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $nrBadge }}">{{ $eval->nivel_riesgo }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-center whitespace-nowrap">
                                            @if($eval->vigente)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Vigente</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200">No vigente</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-center text-sm text-slate-700 font-mono whitespace-nowrap">{{ $eval->fecha_evaluacion ? \Carbon\Carbon::parse($eval->fecha_evaluacion)->format('d/m/Y') : '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-10 text-center">
                        <div class="mx-auto w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <p class="text-sm font-medium text-slate-500">No se han realizado evaluaciones FMEA para este activo.</p>
                        <p class="text-xs text-slate-400 mt-1">Las evaluaciones de riesgo aparecerán aquí.</p>
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
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-900">Planes de Tratamiento de Riesgo</h3>
                        <p class="text-sm text-slate-500 mt-0.5">Acciones preventivas y correctivas derivadas de las evaluaciones</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Evaluación</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Estrategia</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Acción Preventiva</th>
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
                                        <td class="px-4 py-3 text-center text-sm text-slate-700 font-mono">{{ $plan->id_tratamiento }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-700 whitespace-nowrap">
                                            #{{ $plan->_eval->id_evaluacion ?? '—' }}
                                            <span class="text-slate-400">—</span>
                                            {{ $plan->_eval->modoFalla->nombre_modo_falla ?? '' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-700">{{ $plan->estrategia ?? '—' }}</td>
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.ficha-tab');
    const panels = document.querySelectorAll('.ficha-tab-panel');

    function activateTab(key) {
        tabs.forEach(t => {
            const isActive = t.dataset.tab === key;
            t.setAttribute('aria-selected', isActive ? 'true' : 'false');
            t.classList.toggle('text-[#1D3557]', isActive);
            t.classList.toggle('text-slate-500', !isActive);
            t.classList.toggle('hover:text-slate-700', !isActive);
            t.classList.toggle('hover:bg-slate-50', !isActive);
            // Underline indicator
            if (isActive) {
                t.classList.add('after:absolute', 'after:bottom-0', 'after:left-0', 'after:right-0', 'after:h-0.5', 'after:bg-[#1D3557]', 'after:rounded-full');
            } else {
                t.classList.remove('after:absolute', 'after:bottom-0', 'after:left-0', 'after:right-0', 'after:h-0.5', 'after:bg-[#1D3557]', 'after:rounded-full');
            }
        });
        panels.forEach(p => {
            p.classList.toggle('hidden', p.dataset.tabPanel !== key);
        });
    }

    tabs.forEach(t => {
        t.addEventListener('click', () => {
            const key = t.dataset.tab;
            activateTab(key);
            history.replaceState(null, '', '#' + key);
        });
    });

    // Restore from URL hash
    const hash = window.location.hash.replace('#', '');
    if (hash && document.querySelector(`[data-tab-panel="${hash}"]`)) {
        activateTab(hash);
    }
});
</script>
@endpush
