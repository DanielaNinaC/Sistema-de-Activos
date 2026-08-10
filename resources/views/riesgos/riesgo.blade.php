@extends('layouts.principal')

@section('title', 'Historial de Riesgos - Evaluación FMEA')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Encabezado Principal --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Historial de Riesgos</h2>
                <p class="text-sm text-slate-500">Registro histórico completo de evaluaciones de riesgo FMEA y cálculo de scores AHP</p>
            </div>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            <a href="{{ route('admin.riesgos.evaluacion') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Motor de Evaluación FMEA
            </a>
        </div>
    </div>

    {{-- Tarjetas de Métricas Resumen Compactas (Centradas) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex flex-col items-center justify-center text-center">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Total Evaluaciones</span>
            <span class="text-xl font-bold font-mono text-slate-900 mt-0.5 block">{{ $totalEvaluaciones }}</span>
        </div>

        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex flex-col items-center justify-center text-center">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Alertas Generadas</span>
            <span class="text-xl font-bold font-mono text-slate-900 mt-0.5 block">{{ $totalAlertas }}</span>
        </div>

        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex flex-col items-center justify-center text-center">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Riesgo Alto</span>
            <span class="text-xl font-bold font-mono text-orange-600 mt-0.5 block">{{ $totalAlto }}</span>
        </div>

        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs flex flex-col items-center justify-center text-center">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Riesgo Crítico</span>
            <span class="text-xl font-bold font-mono text-rose-600 mt-0.5 block">{{ $totalCritico }}</span>
        </div>
    </div>

    {{-- Activos que nunca podrán evaluarse por falta de modos de falla en su categoría --}}
    @if($activosSinModos->isNotEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-900 shadow-sm text-sm space-y-2">
            <div class="flex items-center gap-2 font-bold">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                {{ $activosSinModos->count() }} {{ $activosSinModos->count() === 1 ? 'activo no aparece' : 'activos no aparecen' }} en este historial: sin modos de falla definidos
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach($activosSinModos as $act)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white border border-amber-200 text-amber-800">
                        {{ $act->codigo_patrimonial ?? $act->nombre_equipo }} — {{ $act->categoria->nombre_categoria ?? 'Sin categoría' }}
                    </span>
                @endforeach
            </div>
            <p class="text-xs text-amber-700">Registra al menos un modo de falla en la categoría correspondiente (<a href="{{ route('admin.riesgos.modos') }}" class="underline font-semibold">Modos de Falla</a>) para que estos activos puedan evaluarse.</p>
        </div>
    @endif

    {{-- Barra de Búsqueda --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.riesgos.riesgo') }}" class="flex flex-col sm:flex-row gap-3">
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
                    placeholder="Buscar por activo, código patrimonial, modo de falla, nivel o score..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                >
            </div>
            
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">
                    Buscar
                </button>

                @if(request('buscar'))
                    <a href="{{ route('admin.riesgos.riesgo') }}" class="btn btn-secondary">
                        ✕ Limpiar
                    </a>
                @endif
            </div>
        </form>

        @if(request('buscar'))
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                <span>Filtro activo para: <strong class="text-slate-800">"{{ request('buscar') }}"</strong></span>
                <span class="text-slate-300">•</span>
                <span>Resultados encontrados: <strong class="text-slate-800">{{ $evaluaciones->total() }}</strong></span>
            </div>
        @endif
    </div>

    {{-- Tabla de Historial de Evaluaciones de Riesgo --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden space-y-4">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Registro Histórico de Evaluaciones FMEA</h3>
                <p class="text-xs text-slate-500 mt-0.5">Parámetros inferidos (S, F, MSR) y niveles de riesgo semafóricos</p>
            </div>
            <span class="text-xs text-slate-400">Página {{ $evaluaciones->currentPage() }} de {{ $evaluaciones->lastPage() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Activo Tecnológico</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Modo de Falla</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Severidad (S)</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Frecuencia (F)</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">MSR (Control)</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Score AHP</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Nivel de Riesgo</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($evaluaciones as $evaluacion)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 text-center text-xs font-mono text-slate-600 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($evaluacion->fecha_evaluacion)->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-slate-900">
                                {{ $evaluacion->activo->nombre_equipo ?? '-' }}
                                <span class="block text-[11px] font-mono text-slate-400 font-normal">
                                    {{ $evaluacion->activo->codigo_patrimonial ?? '' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-left text-slate-800 font-medium">
                                {{ $evaluacion->modoFalla->nombre_modo_falla ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-center font-mono font-bold text-rose-600">{{ $evaluacion->valor_severidad }}</td>
                            <td class="px-6 py-4 text-center font-mono font-bold text-amber-600">{{ $evaluacion->valor_frecuencia }}</td>
                            <td class="px-6 py-4 text-center font-mono font-bold text-blue-600">{{ $evaluacion->valor_msr }}</td>
                            <td class="px-6 py-4 text-center font-mono font-bold text-slate-900 text-base">
                                {{ number_format($evaluacion->score_calculado, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                @if($evaluacion->nivel_riesgo === 'Crítico')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        🔴 Crítico
                                    </span>
                                @elseif($evaluacion->nivel_riesgo === 'Alto')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-800 border border-orange-200">
                                        🟠 Alto
                                    </span>
                                @elseif($evaluacion->nivel_riesgo === 'Medio')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        🟡 Medio
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        🟢 Bajo
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500 text-sm">
                                @if(request('buscar'))
                                    No se encontraron evaluaciones de riesgo que coincidan con la búsqueda.
                                @else
                                    No hay evaluaciones de riesgo registradas en el historial.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($evaluaciones->hasPages())
            <div class="px-6 py-3 border-t border-slate-100">
                {{ $evaluaciones->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
