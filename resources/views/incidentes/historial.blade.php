@extends('layouts.principal')

@section('title', 'Historial de Incidentes - Activos Tecnológicos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">Historial de Incidentes</h2>
                <p class="text-sm text-slate-500">Activos que han presentado incidentes; abre uno para ver su historial completo</p>
            </div>
        </div>

        <a href="{{ route('admin.incidentes.registrar') }}" class="btn btn-primary self-start sm:self-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Registrar Nuevo Incidente
        </a>
    </div>

    {{-- Barra de Búsqueda --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.incidentes.historial') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    type="text"
                    name="buscar"
                    value="{{ request('buscar') }}"
                    placeholder="Buscar por activo, código patrimonial, categoría o ubicación..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                >
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">
                    Buscar
                </button>

                @if(request('buscar'))
                    <a href="{{ route('admin.incidentes.historial') }}" class="btn btn-secondary">
                        ✕ Limpiar
                    </a>
                @endif
            </div>
        </form>

        @if(request('buscar'))
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                <span>Filtro activo para: <strong class="text-slate-800">"{{ request('buscar') }}"</strong></span>
                <span class="text-slate-300">•</span>
                <span>Activos encontrados: <strong class="text-slate-800">{{ $activos->total() }}</strong></span>
            </div>
        @endif
    </div>

    {{-- Tabla: un renglón por activo --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Activo Tecnológico</th>
                        <th class="px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Categoría</th>
                        <th class="px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Ubicación</th>
                        <th class="px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Incidentes</th>
                        <th class="px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Sin Resolver</th>
                        <th class="w-28 px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Último</th>
                        <th class="px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($activos as $activo)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-3 py-4 text-left text-sm">
                                <div class="font-semibold text-slate-900">{{ $activo->nombre_equipo }}</div>
                                @if($activo->codigo_patrimonial)
                                    <div class="text-xs text-slate-400 font-mono">{{ $activo->codigo_patrimonial }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-4 text-center text-sm text-slate-700 whitespace-nowrap">
                                {{ $activo->categoria->nombre_categoria ?? '-' }}
                            </td>
                            <td class="px-3 py-4 text-center text-sm text-slate-700 whitespace-nowrap">
                                {{ $activo->ubicacion ? $activo->ubicacion->seccion . ($activo->ubicacion->aula ? ' - ' . $activo->ubicacion->aula : '') : '-' }}
                            </td>
                            <td class="px-3 py-4 text-center text-sm whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $activo->incidentes_count }}
                                </span>
                            </td>
                            <td class="px-3 py-4 text-center text-sm whitespace-nowrap">
                                @if($activo->incidentes_abiertos_count > 0)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        {{ $activo->incidentes_abiertos_count }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Al día
                                    </span>
                                @endif
                            </td>
                            <td class="w-28 px-3 py-4 text-center text-sm whitespace-nowrap">
                                @if($activo->incidentes_max_fecha_incidente)
                                    <div class="font-semibold text-slate-900 font-mono text-sm">{{ \Carbon\Carbon::parse($activo->incidentes_max_fecha_incidente)->format('d/m/Y') }}</div>
                                    <div class="text-xs text-slate-500 font-mono">{{ \Carbon\Carbon::parse($activo->incidentes_max_fecha_incidente)->format('H:i') }} hs</div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-4 text-center text-sm whitespace-nowrap">
                                <a href="{{ route('admin.incidentes.historial.activo', $activo->id_activo) }}"
                                   class="btn btn-xs btn-primary"
                                   title="Ver todos los incidentes registrados para este activo">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Ver Incidentes
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                @if(request('buscar'))
                                    No se encontraron activos que coincidan con la búsqueda.
                                @else
                                    Ningún activo tiene incidentes registrados.
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
