@extends('layouts.principal')

@section('title', 'Evolución de Score - Informes y Reportes')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Evolución del Score de Riesgo</h2>
            <p class="text-xs text-slate-500">Historial de score por activo, con una línea por modo de falla y el score global</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.informes.evolucion-score') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Activo</label>
                <select name="fk_id_activo" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" data-searchable data-search-placeholder="Buscar activo por nombre o código..." required>
                    <option value="">Seleccione un activo</option>
                    @foreach($activos as $activo)
                        <option value="{{ $activo->id_activo }}" {{ optional($activoSeleccionado)->id_activo == $activo->id_activo ? 'selected' : '' }}>{{ $activo->codigo_patrimonial }} - {{ $activo->nombre_equipo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Desde</label>
                <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Hasta</label>
                <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-primary">Ver Evolución</button>
                @if($activoSeleccionado)
                    <a href="{{ route('admin.informes.evolucion-score.excel', request()->query()) }}" class="btn btn-success">Excel</a>
                @endif
            </div>
        </form>
    </div>

    @if($activoSeleccionado && $datos)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-sm font-bold text-slate-900 mb-1">{{ $activoSeleccionado->nombre_equipo }} <span class="text-slate-400 font-mono text-xs">{{ $activoSeleccionado->codigo_patrimonial }}</span></h3>
            @if(empty($datos['tabla']))
                <p class="text-sm text-slate-500 py-8 text-center">Este activo no tiene evaluaciones registradas en el rango seleccionado.</p>
            @else
                <canvas id="chartEvolucion" height="100"></canvas>
            @endif
        </div>

        @if(!empty($datos['tabla']))
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Modo de Falla</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Score</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Nivel</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($datos['tabla'] as $fila)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-6 py-3 font-mono text-xs text-slate-600">{{ $fila['fecha'] }}</td>
                                    <td class="px-6 py-3 text-slate-800">{{ $fila['modo_falla'] }}</td>
                                    <td class="px-6 py-3 text-center font-mono font-bold text-slate-900">{{ number_format($fila['score'], 2) }}</td>
                                    <td class="px-6 py-3 text-center text-slate-700">{{ $fila['nivel_riesgo'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    function initEvolucionScore() {
        if (typeof Chart === 'undefined') return;
        var el = document.getElementById('chartEvolucion');
        if (!el) return;

        var existente = Chart.getChart(el);
        if (existente) existente.destroy();

        var porModo = @json($datos['por_modo'] ?? []);
        var scoreGlobal = @json($datos['score_global'] ?? []);

        // Eje X compartido: todas las fechas distintas, ordenadas (sin adaptador de fechas de Chart.js)
        var fechasSet = {};
        Object.keys(porModo).forEach(function (modo) {
            porModo[modo].forEach(function (p) { fechasSet[p.fecha] = true; });
        });
        Object.keys(scoreGlobal).forEach(function (f) { fechasSet[f] = true; });
        var etiquetas = Object.keys(fechasSet).sort();

        var coloresLinea = ['#3b82f6', '#a855f7', '#f97316', '#14b8a6', '#ec4899', '#84cc16'];
        var datasets = [];
        var i = 0;

        Object.keys(porModo).forEach(function (modo) {
            var porFecha = {};
            porModo[modo].forEach(function (p) { porFecha[p.fecha] = p.score; });
            datasets.push({
                label: modo,
                data: etiquetas.map(function (f) { return porFecha.hasOwnProperty(f) ? porFecha[f] : null; }),
                borderColor: coloresLinea[i % coloresLinea.length],
                backgroundColor: coloresLinea[i % coloresLinea.length],
                spanGaps: true,
                tension: 0.2
            });
            i++;
        });

        datasets.push({
            label: 'Score Global (máximo)',
            data: etiquetas.map(function (f) { return scoreGlobal.hasOwnProperty(f) ? scoreGlobal[f] : null; }),
            borderColor: '#0f172a',
            backgroundColor: '#0f172a',
            borderWidth: 3,
            borderDash: [6, 3],
            spanGaps: true,
            tension: 0.2
        });

        new Chart(el, {
            type: 'line',
            data: { labels: etiquetas, datasets: datasets },
            options: {
                scales: {
                    x: { title: { display: true, text: 'Fecha' } },
                    y: { beginAtZero: true, max: 10, title: { display: true, text: 'Score' } }
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEvolucionScore);
    } else {
        initEvolucionScore();
    }
    document.addEventListener('turbo:load', initEvolucionScore);
})();
</script>
@endsection
