@extends('layouts.principal')

@section('title', 'Reporte de Activos por Categoría y Estado - Informes y Reportes')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Cabecera --}}
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Reporte de Activos por Categoría y Estado</h2>
            <p class="text-xs text-slate-500">Distribución consolidada de activos tecnológicos según su categoría y condición actual (Óptimo, Regular, Obsoleto)</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.informes.estado-activos') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                {{-- Categoría --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Categoría</label>
                    <select name="fk_id_categoria" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition">
                        <option value="">Todas las categorías</option>
                        @foreach($categorias as $cat)
                            <option value="{{ $cat->id_categoria }}" {{ request('fk_id_categoria') == $cat->id_categoria ? 'selected' : '' }}>
                                {{ $cat->nombre_categoria }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Estado --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Estado</label>
                    <select name="estado" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition">
                        <option value="">Todos los estados</option>
                        @foreach($estados as $est)
                            <option value="{{ $est }}" {{ request('estado') == $est ? 'selected' : '' }}>
                                {{ $est }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Ubicación --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Ubicación</label>
                    <select name="fk_id_ubicacion" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition">
                        <option value="">Todas las ubicaciones</option>
                        @foreach($ubicaciones as $ub)
                            <option value="{{ $ub->id_ubicacion }}" {{ request('fk_id_ubicacion') == $ub->id_ubicacion ? 'selected' : '' }}>
                                {{ $ub->seccion }}{{ $ub->aula ? ' - ' . $ub->aula : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Fecha Inicio --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Adquisición Desde</label>
                    <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                </div>

                {{-- Fecha Fin --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Adquisición Hasta</label>
                    <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-slate-100">
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn btn-dark">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filtrar
                    </button>
                    <a href="{{ route('admin.informes.estado-activos') }}" class="btn btn-outline">Limpiar</a>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.informes.estado-activos.pdf', request()->query()) }}"
                       class="btn btn-secondary flex items-center gap-1.5" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar PDF
                    </a>
                    <a href="{{ route('admin.informes.estado-activos.excel', request()->query()) }}"
                       class="btn btn-secondary flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar Excel
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Indicadores / Resumen Superior --}}
    @php
        $totales = $datos['totales'];
        $totalG = max(1, $totales['total']);
        $pctOptimo = round(($totales['optimo'] / $totalG) * 100, 1);
        $pctRegular = round(($totales['regular'] / $totalG) * 100, 1);
        $pctObsoleto = round(($totales['obsoleto'] / $totalG) * 100, 1);
    @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Total de Activos</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-emerald-700 uppercase tracking-wider">Estado Óptimo</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-amber-700 uppercase tracking-wider">Estado Regular</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-rose-700 uppercase tracking-wider">Estado Obsoleto</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="px-6 py-3.5">
                        <span class="text-xl font-extrabold text-slate-900">{{ number_format($totales['total']) }}</span>
                        <span class="text-xs text-slate-400 ml-1">activos</span>
                    </td>
                    <td class="px-6 py-3.5">
                        <span class="text-xl font-extrabold text-emerald-700">{{ number_format($totales['optimo']) }}</span>
                        <span class="text-xs text-emerald-600 font-medium ml-1">({{ $totales['total'] > 0 ? $pctOptimo : 0 }}%)</span>
                    </td>
                    <td class="px-6 py-3.5">
                        <span class="text-xl font-extrabold text-amber-700">{{ number_format($totales['regular']) }}</span>
                        <span class="text-xs text-amber-600 font-medium ml-1">({{ $totales['total'] > 0 ? $pctRegular : 0 }}%)</span>
                    </td>
                    <td class="px-6 py-3.5">
                        <span class="text-xl font-extrabold text-rose-700">{{ number_format($totales['obsoleto']) }}</span>
                        <span class="text-xs text-rose-600 font-medium ml-1">({{ $totales['total'] > 0 ? $pctObsoleto : 0 }}%)</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Gráfico de Barras por Categoría --}}
    @php
        $alturaGrafico = max(320, count($datos['chart']['labels']) * 44);
    @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <div class="border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900">Distribución de Estados por Categoría</h3>
            <p class="text-xs text-slate-500">Comparación de la cantidad de activos en estado Óptimo, Regular y Obsoleto por categoría</p>
        </div>

        @if($totales['total'] === 0)
            <div class="py-12 text-center text-slate-500">
                No hay activos registrados que coincidan con los filtros seleccionados.
            </div>
        @else
            <div class="w-full relative" style="height: {{ $alturaGrafico }}px;">
                <canvas id="chartEstadoActivos"></canvas>
            </div>
        @endif
    </div>

    {{-- Tabla Resumen --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Tabla Resumen por Categoría</h3>
                <p class="text-xs text-slate-500">Detalle consolidado de cantidades por categoría y estado actual</p>
            </div>
            <span class="text-xs font-medium text-slate-400">
                {{ count($datos['filas']) }} {{ count($datos['filas']) === 1 ? 'categoría' : 'categorías' }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider w-16">#</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Categoría</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-emerald-700 uppercase tracking-wider">Óptimo</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-amber-700 uppercase tracking-wider">Regular</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-rose-700 uppercase tracking-wider">Obsoleto</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-900 uppercase tracking-wider">Total</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse($datos['filas'] as $fila)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-3.5 text-center text-xs font-medium text-slate-400">
                                {{ $fila['numero'] }}
                            </td>
                            <td class="px-6 py-3.5 font-semibold text-slate-900">
                                {{ $fila['categoria'] }}
                            </td>
                            <td class="px-6 py-3.5 text-center font-mono">
                                @if($fila['optimo'] > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        {{ $fila['optimo'] }}
                                    </span>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-center font-mono">
                                @if($fila['regular'] > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                        {{ $fila['regular'] }}
                                    </span>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-center font-mono">
                                @if($fila['obsoleto'] > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                        {{ $fila['obsoleto'] }}
                                    </span>
                                @else
                                    <span class="text-slate-400">0</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-center font-mono font-bold text-slate-900">
                                {{ $fila['total'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                No hay registros que coincidan con los filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($datos['filas']) > 0)
                    <tfoot class="bg-slate-100/80 border-t-2 border-slate-300 font-bold text-slate-900">
                        <tr>
                            <td colspan="2" class="px-6 py-4 text-right uppercase tracking-wider text-xs font-extrabold text-slate-700">
                                Total General
                            </td>
                            <td class="px-6 py-4 text-center font-mono text-emerald-800 text-base">
                                {{ $totales['optimo'] }}
                            </td>
                            <td class="px-6 py-4 text-center font-mono text-amber-800 text-base">
                                {{ $totales['regular'] }}
                            </td>
                            <td class="px-6 py-4 text-center font-mono text-rose-800 text-base">
                                {{ $totales['obsoleto'] }}
                            </td>
                            <td class="px-6 py-4 text-center font-mono text-slate-900 text-base font-extrabold">
                                {{ $totales['total'] }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>

{{-- Chart.js Script --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    function initChartEstadoActivos() {
        var canvas = document.getElementById('chartEstadoActivos');
        if (!canvas) return;

        function renderizar() {
            if (typeof Chart === 'undefined') return;

            var chartExistente = Chart.getChart(canvas);
            if (chartExistente) {
                chartExistente.destroy();
            }

            var labels = @json($datos['chart']['labels']);
            var dataOptimo = @json($datos['chart']['optimo']);
            var dataRegular = @json($datos['chart']['regular']);
            var dataObsoleto = @json($datos['chart']['obsoleto']);

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Óptimo',
                            data: dataOptimo,
                            backgroundColor: '#3b82f6',
                            borderRadius: 3,
                            barPercentage: 0.7,
                            categoryPercentage: 0.8
                        },
                        {
                            label: 'Regular',
                            data: dataRegular,
                            backgroundColor: '#22c55e',
                            borderRadius: 3,
                            barPercentage: 0.7,
                            categoryPercentage: 0.8
                        },
                        {
                            label: 'Obsoleto',
                            data: dataObsoleto,
                            backgroundColor: '#f97316',
                            borderRadius: 3,
                            barPercentage: 0.7,
                            categoryPercentage: 0.8
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 20,
                                color: '#334155',
                                font: { size: 12, weight: '600' }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                footer: function (items) {
                                    var sum = 0;
                                    items.forEach(function (item) { sum += item.parsed.x; });
                                    return 'Total en categoría: ' + sum;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: false,
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: { color: '#64748b', precision: 0, font: { size: 11 } }
                        },
                        y: {
                            stacked: false,
                            grid: { display: false },
                            ticks: { color: '#334155', font: { size: 12, weight: '500' } }
                        }
                    }
                }
            });
        }

        if (typeof Chart !== 'undefined') {
            renderizar();
        } else {
            var intentos = 0;
            var timer = setInterval(function () {
                intentos++;
                if (typeof Chart !== 'undefined') {
                    clearInterval(timer);
                    renderizar();
                } else if (intentos > 40) {
                    clearInterval(timer);
                }
            }, 100);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChartEstadoActivos);
    } else {
        initChartEstadoActivos();
    }
    document.addEventListener('turbo:load', initChartEstadoActivos);
    document.addEventListener('page:load', initChartEstadoActivos);
})();
</script>
@endsection
