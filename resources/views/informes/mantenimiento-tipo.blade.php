@extends('layouts.principal')

@section('title', 'Mantenimiento Preventivo vs. Correctivo - Informes y Reportes')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Mantenimiento Preventivo vs. Correctivo</h2>
            <p class="text-xs text-slate-500">Proporción y tendencia mensual del tipo de mantenimiento ejecutado</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.informes.mantenimiento-tipo') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Desde</label>
                <input type="date" name="fecha_inicio" value="{{ $fechaInicio }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Hasta</label>
                <input type="date" name="fecha_fin" value="{{ $fechaFin }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
            </div>
            <div class="flex-1">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Categoría</label>
                <select name="fk_id_categoria" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition">
                    <option value="">Todas las categorías</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id_categoria }}" {{ request('fk_id_categoria') == $categoria->id_categoria ? 'selected' : '' }}>{{ $categoria->nombre_categoria }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-dark">Filtrar</button>
        </form>
    </div>

    @if(array_sum($porTipo->toArray()) === 0)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center text-slate-500">
            No hay mantenimientos ejecutados (con fecha de ejecución) en el período seleccionado.
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-bold text-slate-900 mb-4">Proporción del Período</h3>
                <canvas id="chartTipoPie" height="220"></canvas>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-bold text-slate-900 mb-4">Tendencia Mensual</h3>
                <canvas id="chartTipoBarras" height="220"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Tipo de Mantenimiento</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Total Ejecutados</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">% del Período</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @php $totalPeriodo = array_sum($porTipo->toArray()); @endphp
                        @foreach($porTipo as $tipo => $total)
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-3 font-semibold text-slate-800">{{ $tipo }}</td>
                                <td class="px-6 py-3 text-center font-mono text-slate-700">{{ $total }}</td>
                                <td class="px-6 py-3 text-center font-mono text-slate-700">{{ $totalPeriodo > 0 ? number_format(($total / $totalPeriodo) * 100, 1) : 0 }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    function initMantenimientoTipo() {
        if (typeof Chart === 'undefined') return;

        var colores = { 'Preventivo': '#22c55e', 'Correctivo': '#ef4444', 'Programado': '#3b82f6', 'Inspección': '#a855f7' };
        var paleta = ['#22c55e', '#ef4444', '#3b82f6', '#a855f7', '#f97316', '#14b8a6'];

        var elPie = document.getElementById('chartTipoPie');
        if (elPie) {
            var existentePie = Chart.getChart(elPie);
            if (existentePie) existentePie.destroy();

            var porTipo = @json($porTipo);
            var labels = Object.keys(porTipo);

            new Chart(elPie, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: labels.map(function (l) { return porTipo[l]; }),
                        backgroundColor: labels.map(function (l, i) { return colores[l] || paleta[i % paleta.length]; })
                    }]
                },
                options: { plugins: { legend: { position: 'bottom' } } }
            });
        }

        var elBarras = document.getElementById('chartTipoBarras');
        if (elBarras) {
            var existenteBarras = Chart.getChart(elBarras);
            if (existenteBarras) existenteBarras.destroy();

            var tipos = @json($tipos);
            var tendencia = @json($tendenciaMensual);

            new Chart(elBarras, {
                type: 'bar',
                data: {
                    labels: @json($etiquetasMensuales),
                    datasets: tipos.map(function (tipo, i) {
                        return {
                            label: tipo,
                            data: tendencia[tipo],
                            backgroundColor: colores[tipo] || paleta[i % paleta.length]
                        };
                    })
                },
                options: {
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
                    }
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMantenimientoTipo);
    } else {
        initMantenimientoTipo();
    }
    document.addEventListener('turbo:load', initMantenimientoTipo);
})();
</script>
@endsection
