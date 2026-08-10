@extends('layouts.principal')

@section('title', 'Equipos: ' . $categoria->nombre_categoria)

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.activos.categorias') }}" class="btn btn-outline btn-icon" aria-label="Volver a categorías" title="Volver a Categorías">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">{{ $categoria->nombre_categoria }}</h2>
                <p class="text-sm text-slate-500">Listado de equipos registrados en esta categoría</p>
            </div>
        </div>

        <div class="flex items-center gap-3 self-start sm:self-auto">
            @if($categoria->vida_util_meses)
                @php
                    $anios = floor($categoria->vida_util_meses / 12);
                    $mesesRestantes = $categoria->vida_util_meses % 12;
                    $vidaUtilTexto = '';
                    if ($anios > 0 && $mesesRestantes > 0) {
                        $vidaUtilTexto = $anios . ($anios == 1 ? ' año' : ' años') . ' y ' . $mesesRestantes . ($mesesRestantes == 1 ? ' mes' : ' meses');
                    } elseif ($anios > 0) {
                        $vidaUtilTexto = $anios . ($anios == 1 ? ' año' : ' años');
                    } else {
                        $vidaUtilTexto = $mesesRestantes . ($mesesRestantes == 1 ? ' mes' : ' meses');
                    }
                @endphp
                <span class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-700 text-xs font-medium px-3 py-1.5 rounded-lg border border-slate-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Vida útil: {{ $vidaUtilTexto }}
                </span>
            @endif
            <span class="inline-flex items-center gap-1.5 bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1.5 rounded-lg border border-blue-200">
                {{ $activos->total() }} {{ $activos->total() == 1 ? 'equipo' : 'equipos' }}
            </span>
        </div>
    </div>

    {{-- Barra de Búsqueda --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.activos.categorias.equipos', $categoria->id_categoria) }}" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    type="text" 
                    name="buscar" 
                    value="{{ $buscar ?? '' }}" 
                    placeholder="Buscar por código, equipo, marca, modelo, ubicación..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                >
            </div>
            
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">
                    Buscar
                </button>

                @if(!empty($buscar))
                    <a href="{{ route('admin.activos.categorias.equipos', $categoria->id_categoria) }}" class="btn btn-secondary">
                        ✕ Limpiar
                    </a>
                @endif
            </div>
        </form>

        @if(!empty($buscar))
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                <span>Filtro activo para: <strong class="text-slate-800">"{{ $buscar }}"</strong></span>
                <span class="text-slate-300">•</span>
                <span>Resultados encontrados: <strong class="text-slate-800">{{ $activos->total() }}</strong></span>
            </div>
        @endif
    </div>

    {{-- Tabla de Equipos --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-16 px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Código</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Equipo</th>
                        <th class="px-2 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider max-w-[100px]">Ubicación</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Criticidad</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($activos as $activo)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="w-16 px-3 py-4 text-center text-sm text-slate-700 font-mono whitespace-nowrap">{{ $activo->id_activo }}</td>
                            <td class="px-6 py-4 text-center text-sm font-mono font-semibold text-slate-800 whitespace-nowrap">{{ $activo->codigo_patrimonial }}</td>
                            <td class="px-6 py-4 text-center text-sm font-semibold text-slate-900 whitespace-nowrap">
                                <div>{{ $activo->nombre_equipo }}</div>
                                @if($activo->marca || $activo->modelo)
                                    <div class="text-xs text-slate-400 font-normal">{{ $activo->marca }} {{ $activo->modelo }}</div>
                                @endif
                            </td>
                            <td class="px-2 py-3 text-center text-sm text-slate-600 max-w-[100px] leading-tight">
                                <div class="font-medium text-slate-800 truncate" title="{{ $activo->ubicacion->seccion ?? '-' }}">{{ $activo->ubicacion->seccion ?? '-' }}</div>
                                @if(!empty($activo->ubicacion->aula))
                                    <div class="text-xs text-slate-400 truncate" title="{{ $activo->ubicacion->aula }}">{{ $activo->ubicacion->aula }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center text-sm whitespace-nowrap">
                                @php
                                    $estadoStr = strtolower(trim($activo->estado_operativo ?? ''));
                                    $estadoColor = match(true) {
                                        str_contains($estadoStr, 'operativo') && !str_contains($estadoStr, 'inoperativo') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        str_contains($estadoStr, 'mantenimiento') => 'bg-amber-100 text-amber-800 border-amber-200',
                                        str_contains($estadoStr, 'inoperativo') || str_contains($estadoStr, 'baja') => 'bg-rose-100 text-rose-800 border-rose-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $estadoColor }}">
                                    {{ $activo->estado_operativo }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm whitespace-nowrap">
                                @php
                                    $critStr = strtolower(trim($activo->criticidad_institucional ?? ''));
                                    $critColor = match(true) {
                                        str_contains($critStr, 'alta') || str_contains($critStr, 'alto') => 'bg-rose-100 text-rose-800 border-rose-200',
                                        str_contains($critStr, 'media') || str_contains($critStr, 'medio') => 'bg-amber-100 text-amber-800 border-amber-200',
                                        str_contains($critStr, 'baja') || str_contains($critStr, 'bajo') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $critColor }}">
                                    {{ $activo->criticidad_institucional ?? '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('admin.activos.show', $activo->id_activo) }}" class="btn btn-sm btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Ver
                                    </a>
                                    <a href="{{ route('admin.activos.edit', $activo->id_activo) }}" class="btn btn-sm btn-warning">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Editar
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                @if(!empty($buscar))
                                    No se encontraron equipos que coincidan con la búsqueda.
                                @else
                                    No hay equipos registrados en esta categoría.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $activos->links() }}
    </div>
</div>
@endsection
