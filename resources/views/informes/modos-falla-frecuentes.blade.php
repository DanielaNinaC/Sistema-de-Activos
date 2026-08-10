@extends('layouts.principal')

@section('title', 'Modos de Falla Frecuentes - Informes y Reportes')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Modos de Falla Más Frecuentes</h2>
            <p class="text-xs text-slate-500">Cruce entre el motor FMEA (evaluaciones) y los incidentes reales reportados</p>
        </div>
    </div>

    {{-- Reporte principal: evaluaciones --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <div>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-blue-50 text-blue-700 border border-blue-100">Motor FMEA</span>
            <h3 class="text-base font-bold text-slate-900 mt-1">Modos de Falla por Evaluaciones de Riesgo</h3>
            <p class="text-xs text-slate-500">Ordenado por cantidad de veces que resultó en nivel Alto o Crítico</p>
        </div>

        @if($porEvaluacion->isEmpty())
            <p class="text-sm text-slate-500 py-6 text-center">Aún no hay evaluaciones registradas.</p>
        @else
            <canvas id="chartPorEvaluacion" height="90"></canvas>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5">Modo de Falla</th>
                            <th class="px-4 py-2.5 text-center">Total Evaluaciones</th>
                            <th class="px-4 py-2.5 text-center">Score Promedio</th>
                            <th class="px-4 py-2.5 text-center">Veces Alto/Crítico</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($porEvaluacion as $fila)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-2.5 font-semibold text-slate-800">{{ $fila->modo_falla }}</td>
                                <td class="px-4 py-2.5 text-center font-mono text-slate-700">{{ $fila->total_evaluaciones }}</td>
                                <td class="px-4 py-2.5 text-center font-mono text-slate-700">{{ number_format($fila->promedio_score, 2) }}</td>
                                <td class="px-4 py-2.5 text-center font-mono font-bold text-rose-600">{{ $fila->veces_criticas }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Reporte complementario: incidentes reales --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <div>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-amber-50 text-amber-700 border border-amber-100">Incidentes Reales</span>
            <h3 class="text-base font-bold text-slate-900 mt-1">Modos de Falla por Incidentes Reportados</h3>
            <p class="text-xs text-slate-500">Los incidentes registrados sin modo de falla asociado caen en "Sin clasificar"</p>
        </div>

        @if($porIncidente->isEmpty())
            <p class="text-sm text-slate-500 py-6 text-center">Aún no hay incidentes registrados.</p>
        @else
            <canvas id="chartPorIncidente" height="90"></canvas>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5">Modo de Falla</th>
                            <th class="px-4 py-2.5 text-center">Total Incidentes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($porIncidente as $fila)
                            <tr class="hover:bg-slate-50/60 {{ $fila->modo_falla === 'Sin clasificar' ? 'italic text-slate-400' : '' }}">
                                <td class="px-4 py-2.5 font-semibold {{ $fila->modo_falla === 'Sin clasificar' ? 'text-slate-400' : 'text-slate-800' }}">{{ $fila->modo_falla }}</td>
                                <td class="px-4 py-2.5 text-center font-mono text-slate-700">{{ $fila->total_incidentes }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    function initModosFrecuentes() {
        if (typeof Chart === 'undefined') return;

        var elEval = document.getElementById('chartPorEvaluacion');
        if (elEval) {
            var existenteEval = Chart.getChart(elEval);
            if (existenteEval) existenteEval.destroy();

            new Chart(elEval, {
                type: 'bar',
                data: {
                    labels: @json($porEvaluacion->pluck('modo_falla')),
                    datasets: [{
                        label: 'Veces Alto/Crítico',
                        data: @json($porEvaluacion->pluck('veces_criticas')),
                        backgroundColor: '#ef4444'
                    }, {
                        label: 'Total Evaluaciones',
                        data: @json($porEvaluacion->pluck('total_evaluaciones')),
                        backgroundColor: '#93c5fd'
                    }]
                },
                options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
            });
        }

        var elInc = document.getElementById('chartPorIncidente');
        if (elInc) {
            var existenteInc = Chart.getChart(elInc);
            if (existenteInc) existenteInc.destroy();

            new Chart(elInc, {
                type: 'bar',
                data: {
                    labels: @json($porIncidente->pluck('modo_falla')),
                    datasets: [{
                        label: 'Incidentes',
                        data: @json($porIncidente->pluck('total_incidentes')),
                        backgroundColor: '#f59e0b'
                    }]
                },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModosFrecuentes);
    } else {
        initModosFrecuentes();
    }
    document.addEventListener('turbo:load', initModosFrecuentes);
})();
</script>
@endsection
