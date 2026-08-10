@extends('layouts.principal')

@section('title', 'Detalles del Activo - ' . $activo->nombre_equipo)

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.activos.inventario') }}" class="btn btn-outline btn-icon" aria-label="Volver al inventario" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">{{ $activo->nombre_equipo }}</h2>
                <p class="text-sm text-slate-500">Código Patrimonial: <span class="font-mono font-bold text-slate-800">{{ $activo->codigo_patrimonial }}</span></p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.activos.edit', $activo->id_activo) }}" class="btn btn-warning">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar Activo
            </a>
            <a href="{{ route('admin.activos.inventario') }}" class="btn btn-secondary">
                Volver al Inventario
            </a>
        </div>
    </div>

    {{-- Details Card --}}
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
                    $estadoStr = strtolower(trim($activo->estado_operativo ?? ''));
                    $estadoColor = match(true) {
                        str_contains($estadoStr, 'operativo') && !str_contains($estadoStr, 'inoperativo') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        str_contains($estadoStr, 'mantenimiento') => 'bg-amber-100 text-amber-800 border-amber-200',
                        str_contains($estadoStr, 'inoperativo') || str_contains($estadoStr, 'baja') => 'bg-rose-100 text-rose-800 border-rose-200',
                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                    };
                @endphp
                <span class="inline-flex items-center px-3 py-1 mt-1 rounded-full text-xs font-bold border {{ $estadoColor }}">
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
                        str_contains($critStr, 'alta') || str_contains($critStr, 'alto') => 'bg-rose-100 text-rose-800 border-rose-200',
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
@endsection
