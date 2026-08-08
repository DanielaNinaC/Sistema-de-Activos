@extends('layouts.principal')

@section('title', 'Evaluaciones Sin Tratamiento - Informes y Reportes')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition shadow-sm" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Evaluaciones Críticas Sin Tratamiento</h2>
            <p class="text-xs text-slate-500">Evaluaciones vigentes en nivel Alto o Crítico que no tienen ningún plan de tratamiento asociado</p>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <a href="{{ route('admin.informes.sin-tratamiento.pdf') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-xs transition shadow-sm">PDF</a>
            <a href="{{ route('admin.informes.sin-tratamiento.excel') }}" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-xs transition shadow-sm">Excel</a>
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
                                <a href="{{ route('admin.riesgos.tratamiento', ['crear' => $ev->id_evaluacion]) }}" class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-xl transition text-xs font-semibold shadow-sm">
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
