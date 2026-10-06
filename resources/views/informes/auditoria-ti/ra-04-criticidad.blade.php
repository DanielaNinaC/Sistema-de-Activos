@extends('layouts.principal')

@section('title', 'RA-04 Clasificación de Activos por Criticidad')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <h2 class="text-2xl font-bold text-slate-900">RA-04 &middot; Clasificación de Activos por Criticidad</h2>
    </div>

    @include('informes.auditoria-ti._encabezado', [
        'titulo' => 'Clasificación de Activos por Criticidad',
        'requerimiento' => 'Req. 7 (parcial)',
        'criterio' => config('auditoria.criterios')['7.1'],
        'filtros' => $filtros,
    ])

    <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-100 rounded-xl px-4 py-2">
        Clasificación de criticidad de activos tecnológicos; no incluye la clasificación de la información por confidencialidad.
    </p>

    <div class="flex justify-end gap-2">
        <a href="{{ route('admin.informes.auditoria-ti.ra04.pdf', $filtros) }}" class="btn btn-secondary" target="_blank">Exportar PDF</a>
        <a href="{{ route('admin.informes.auditoria-ti.ra04.excel', $filtros) }}" class="btn btn-secondary">Exportar Excel</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200">
                <h3 class="text-sm font-bold text-slate-900">Por Nivel de Dependencia (categoría)</h3>
            </div>
            <table class="min-w-full text-sm divide-y divide-slate-100">
                <tbody>
                    @foreach($datos['porDependencia'] as $nivel => $total)
                        @php $pct = $datos['total'] > 0 ? round(($total / $datos['total']) * 100, 1) : 0; @endphp
                        <tr>
                            <td class="px-6 py-2.5 font-semibold text-slate-700">{{ $nivel }}</td>
                            <td class="px-6 py-2.5 text-right font-mono">{{ $total }} ({{ $pct }}%)</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200">
                <h3 class="text-sm font-bold text-slate-900">Por Nivel de Riesgo (evaluación vigente)</h3>
            </div>
            <table class="min-w-full text-sm divide-y divide-slate-100">
                <tbody>
                    @foreach($datos['porNivelRiesgo'] as $nivel => $total)
                        @php $pct = $datos['total'] > 0 ? round(($total / $datos['total']) * 100, 1) : 0; @endphp
                        <tr>
                            <td class="px-6 py-2.5 font-semibold text-slate-700">{{ $nivel }}</td>
                            <td class="px-6 py-2.5 text-right font-mono">{{ $total }} ({{ $pct }}%)</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200">
            <h3 class="text-sm font-bold text-slate-900">Cruce Dependencia &times; Nivel de Riesgo</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Nivel de Dependencia</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Nivel de Riesgo</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold text-slate-600 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($datos['cruce'] as $dependencia => $porNivel)
                        @foreach($porNivel as $nivel => $total)
                            <tr>
                                <td class="px-4 py-2.5">{{ $dependencia }}</td>
                                <td class="px-4 py-2.5">{{ $nivel }}</td>
                                <td class="px-4 py-2.5 text-right font-mono">{{ $total }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-slate-400">Sin activos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
