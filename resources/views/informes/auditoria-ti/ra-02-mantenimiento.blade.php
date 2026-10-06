@extends('layouts.principal')

@section('title', 'RA-02 Plan y Ejecución de Mantenimiento')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <h2 class="text-2xl font-bold text-slate-900">RA-02 &middot; Plan y Ejecución de Mantenimiento</h2>
    </div>

    @include('informes.auditoria-ti._encabezado', [
        'titulo' => 'Plan y Ejecución de Mantenimiento — Gestión ' . $filtros['gestion'],
        'requerimiento' => 'Req. 18',
        'criterio' => config('auditoria.criterios')['7.3'],
        'filtros' => $filtros,
    ])

    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.informes.auditoria-ti.ra02') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Gestión (Año)</label>
                <input type="number" name="gestion" value="{{ $filtros['gestion'] }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm w-28" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Fecha de Corte</label>
                <input type="date" name="fecha_corte" value="{{ $filtros['fecha_corte'] }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Categoría</label>
                <select name="fk_id_categoria" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm">
                    <option value="">Todas</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id_categoria }}" {{ $filtros['fk_id_categoria'] == $cat->id_categoria ? 'selected' : '' }}>{{ $cat->nombre_categoria }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">Filtrar</button>
                <a href="{{ route('admin.informes.auditoria-ti.ra02.pdf', $filtros) }}" class="btn btn-secondary" target="_blank">Exportar PDF</a>
                <a href="{{ route('admin.informes.auditoria-ti.ra02.excel', $filtros) }}" class="btn btn-secondary">Exportar Excel</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 text-center">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Programados</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ $datos['programados'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 text-center">
            <p class="text-xs text-slate-500 uppercase tracking-wider">% Cumplimiento</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ $datos['cumplimiento_pct'] ?? 'N/A' }}{{ $datos['cumplimiento_pct'] !== null ? '%' : '' }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 text-center">
            <p class="text-xs text-slate-500 uppercase tracking-wider">% Preventivo</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ $datos['preventivo_pct'] ?? 'N/A' }}{{ $datos['preventivo_pct'] !== null ? '%' : '' }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 text-center">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Vencidos</p>
            <p class="text-2xl font-extrabold {{ $datos['vencidos']->count() > 0 ? 'text-rose-700' : 'text-slate-900' }}">{{ $datos['vencidos']->count() }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200">
            <h3 class="text-sm font-bold text-slate-900">Cronograma Mensual — Programados vs. Ejecutados</h3>
        </div>
        <div class="w-full relative p-4" style="height: 320px;">
            <canvas id="chartCronograma"></canvas>
        </div>
    </div>

    @if($datos['vencidos']->isNotEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-rose-200 overflow-hidden">
            <div class="px-6 py-3.5 bg-rose-50 border-b border-rose-100">
                <h3 class="text-sm font-bold text-rose-800">Mantenimientos Vencidos (programados, no ejecutados, fecha ya pasada)</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Activo</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Tipo</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Fecha Programada</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($datos['vencidos'] as $m)
                        <tr>
                            <td class="px-4 py-2.5">{{ $m->activo->nombre_equipo ?? '-' }}</td>
                            <td class="px-4 py-2.5">{{ $m->tipo_mantenimiento }}</td>
                            <td class="px-4 py-2.5">{{ \Carbon\Carbon::parse($m->fecha_programada)->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200">
            <h3 class="text-sm font-bold text-slate-900">Detalle por Activo</h3>
        </div>
        <div class="overflow-x-auto max-h-[480px] overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50 sticky top-0">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Activo</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Tipo</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Programado</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Ejecutado</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($datos['mantenimientos'] as $m)
                        <tr>
                            <td class="px-4 py-2.5">{{ $m->activo->nombre_equipo ?? '-' }}</td>
                            <td class="px-4 py-2.5">{{ $m->tipo_mantenimiento }}</td>
                            <td class="px-4 py-2.5">{{ \Carbon\Carbon::parse($m->fecha_programada)->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5">{{ $m->fecha_ejecucion ? \Carbon\Carbon::parse($m->fecha_ejecucion)->format('d/m/Y') : 'No ejecutado' }}</td>
                            <td class="px-4 py-2.5">{{ $m->estado_mantenimiento }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Sin mantenimientos en la gestión seleccionada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    function initChart() {
        var canvas = document.getElementById('chartCronograma');
        if (!canvas || typeof Chart === 'undefined') return;
        var existente = Chart.getChart(canvas);
        if (existente) existente.destroy();

        var cronograma = @json($datos['cronograma']);
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: cronograma.map(function (c) { return c.mes; }),
                datasets: [
                    { label: 'Programados', data: cronograma.map(function (c) { return c.programados; }), backgroundColor: '#94a3b8', borderRadius: 3 },
                    { label: 'Ejecutados', data: cronograma.map(function (c) { return c.ejecutados; }), backgroundColor: '#3b82f6', borderRadius: 3 },
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChart);
    } else {
        initChart();
    }
    document.addEventListener('turbo:load', initChart);
})();
</script>
@endsection
