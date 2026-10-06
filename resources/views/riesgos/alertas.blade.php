@extends('layouts.principal')

@section('title', 'Alertas - Gestión de Riesgos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Alertas de Riesgo</h2>
                <p class="text-sm text-slate-500">Alertas generadas automáticamente por el motor FMEA a partir de las evaluaciones de riesgo</p>
            </div>
        </div>
    </div>

    {{-- Barra de Búsqueda --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.riesgos.alertas') }}" class="flex flex-col sm:flex-row gap-3">
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
                    placeholder="Buscar por activo, código patrimonial o tipo de alerta..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                >
            </div>
            
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">
                    Buscar
                </button>

                @if(request('buscar'))
                    <a href="{{ route('admin.riesgos.alertas') }}" class="btn btn-secondary">
                        ✕ Limpiar
                    </a>
                @endif
            </div>
        </form>

        @if(request('buscar'))
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                <span>Filtro activo para: <strong class="text-slate-800">"{{ request('buscar') }}"</strong></span>
                <span class="text-slate-300">•</span>
                <span>Resultados encontrados: <strong class="text-slate-800">{{ $alertas->total() }}</strong></span>
            </div>
        @endif
    </div>

    {{-- Tabla de Alertas --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden space-y-4">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Listado de Alertas Generadas</h3>
                <p class="text-xs text-slate-500 mt-0.5">Alertas clasificadas por nivel de riesgo y estado de notificación</p>
            </div>
            <span class="text-xs text-slate-400">Página {{ $alertas->currentPage() }} de {{ $alertas->lastPage() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-16 px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha Generación</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Activo Tecnológico</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Tipo de Alerta</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Estado Notificación</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($alertas as $alerta)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="w-16 px-6 py-4 text-center text-sm text-slate-700 font-mono">{{ $alerta->id_alerta }}</td>
                            <td class="px-6 py-4 text-center text-xs font-mono text-slate-600 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($alerta->fecha_generacion)->format('d/m/Y H:i') }} hs
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-slate-900">
                                {{ $alerta->evaluacion->activo->nombre_equipo ?? '-' }}
                                <span class="block text-[11px] font-mono text-slate-400 font-normal">
                                    {{ $alerta->evaluacion->activo->codigo_patrimonial ?? '' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                @if($alerta->tipo_alerta === 'Roja')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        🔴 Alerta Roja
                                    </span>
                                @elseif($alerta->tipo_alerta === 'Naranja')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-800 border border-orange-200">
                                        🟠 Alerta Naranja
                                    </span>
                                @elseif($alerta->tipo_alerta === 'Amarilla')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        🟡 Alerta Amarilla
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        🟢 Alerta Verde
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                @if($alerta->notificacion_enviada)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        ✓ Enviada
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        ⏱ Pendiente
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 text-sm">
                                @if(request('buscar'))
                                    No se encontraron alertas que coincidan con la búsqueda.
                                @else
                                    No hay alertas registradas todavía.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($alertas->hasPages())
            <div class="px-6 py-3 border-t border-slate-100">
                {{ $alertas->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
