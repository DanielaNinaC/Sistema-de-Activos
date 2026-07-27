@extends('layouts.principal')

@section('title','Riesgos - Riesgo')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold">Riesgo</h2>
            <p class="text-sm text-gray-500">Resumen de niveles de riesgo y alertas vinculadas</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded shadow">Evaluaciones: <strong>{{ $evaluaciones->total() }}</strong></div>
        <div class="bg-white p-4 rounded shadow">Alertas: <strong>{{ $alertas->count() }}</strong></div>
        <div class="bg-white p-4 rounded shadow">Riesgo alto: <strong>{{ $evaluaciones->where('nivel_riesgo', 'Alto')->count() }}</strong></div>
        <div class="bg-white p-4 rounded shadow">Riesgo crítico: <strong>{{ $evaluaciones->where('nivel_riesgo', 'Crítico')->count() }}</strong></div>
    </div>

    <form method="GET" action="{{ route('admin.riesgos.riesgo') }}" class="flex items-center gap-2 max-w-md">
        <div class="relative flex-1">
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por activo, nivel de riesgo, modo de falla..."
                class="w-full border border-gray-300 bg-white pl-10 pr-4 py-2.5 text-sm rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition" />
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <button type="submit" class="px-4 py-2.5 bg-slate-800 text-white text-sm rounded-xl hover:bg-slate-700 transition">Buscar</button>
        @if(request('buscar'))
            <a href="{{ route('admin.riesgos.riesgo') }}" class="px-3 py-2.5 text-sm text-gray-600 hover:text-gray-800 transition">Limpiar</a>
        @endif
    </form>

    <div class="bg-white rounded shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold">Evaluaciones con mayor score</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nivel</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($evaluaciones as $evaluacion)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $evaluacion->score_calculado }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $evaluacion->nivel_riesgo }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $evaluacion->activo->nombre_equipo ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $evaluacion->modoFalla->nombre_modo_falla ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ \Carbon\Carbon::parse($evaluacion->fecha_evaluacion)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No hay evaluaciones para mostrar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold">Alertas recientes</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enviada</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($alertas as $alerta)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ \Carbon\Carbon::parse($alerta->fecha_generacion)->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $alerta->evaluacion->activo->nombre_equipo ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $alerta->tipo_alerta }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $alerta->notificacion_enviada ? 'Sí' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No hay alertas registradas todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $evaluaciones->links() }}</div>
</div>
@endsection
