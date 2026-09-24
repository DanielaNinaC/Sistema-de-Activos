@extends('layouts.principal')

@section('title', 'Evaluaciones Sin Tratamiento - Informes y Reportes')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Evaluaciones Críticas Sin Tratamiento</h2>
                <p class="text-xs text-slate-500">Evaluaciones vigentes en nivel Alto o Crítico que no tienen ningún plan de tratamiento asociado</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.informes.sin-tratamiento.pdf') }}"
               class="btn btn-secondary flex items-center gap-1.5" target="_blank">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Exportar PDF
            </a>
            <a href="{{ route('admin.informes.sin-tratamiento.excel') }}"
               class="btn btn-secondary flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Exportar Excel
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Activo</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Modo de Falla</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Nivel</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha Evaluación</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Días sin Tratamiento</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Acción</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($evaluaciones as $ev)
                        @php
                            $dias = $ev->dias_sin_tratamiento;
                            $urgencia = $dias >= 15 ? ['bg-rose-100 text-rose-800 border-rose-200', '🔴 Urgente'] : ($dias >= 7 ? ['bg-orange-100 text-orange-800 border-orange-200', '🟠 Alta'] : ['bg-amber-100 text-amber-800 border-amber-200', '🟡 Moderada']);
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                {{ $ev->activo->nombre_equipo ?? '-' }}
                                <span class="block text-[11px] font-mono text-slate-400 font-normal">{{ $ev->activo->codigo_patrimonial ?? '' }}</span>
                            </td>
                            <td class="px-6 py-4 text-slate-700">{{ $ev->modoFalla->nombre_modo_falla ?? '-' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $ev->nivel_riesgo === 'Crítico' ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-orange-100 text-orange-800 border border-orange-200' }}">
                                    {{ $ev->nivel_riesgo }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center font-mono text-xs text-slate-600">{{ \Carbon\Carbon::parse($ev->fecha_evaluacion)->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $urgencia[0] }}">
                                    {{ $urgencia[1] }} · {{ $dias }} {{ $dias === 1 ? 'día' : 'días' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('admin.riesgos.tratamiento', ['crear' => $ev->id_evaluacion]) }}" class="btn btn-sm btn-primary">
                                    Crear tratamiento
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <span class="text-emerald-600 font-semibold">✓ No hay evaluaciones críticas pendientes de tratamiento.</span>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
