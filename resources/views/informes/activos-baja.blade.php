@extends('layouts.principal')

@section('title', 'Reporte de Activos Dados de Baja - Informes y Reportes')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Cabecera --}}
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Reporte de Activos Dados de Baja</h2>
            <p class="text-xs text-slate-500">Trazabilidad de los activos tecnológicos retirados formalmente del servicio (estado operativo "De Baja")</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.informes.activos-baja') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                {{-- Buscar por código --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Código de Activo</label>
                    <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Ej. AT-0001" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                </div>

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

                {{-- Motivo de baja --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Motivo de Baja</label>
                    <select name="motivo_baja" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition">
                        <option value="">Todos los motivos</option>
                        @foreach($motivos as $motivo)
                            <option value="{{ $motivo }}" {{ request('motivo_baja') == $motivo ? 'selected' : '' }}>{{ $motivo }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Fecha Inicio --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Baja Desde</label>
                    <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                </div>

                {{-- Fecha Fin --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Baja Hasta</label>
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
                    <a href="{{ route('admin.informes.activos-baja') }}" class="btn btn-outline">Limpiar</a>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.informes.activos-baja.pdf', request()->query()) }}"
                       class="btn btn-secondary flex items-center gap-1.5" target="_blank">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar PDF
                    </a>
                    <a href="{{ route('admin.informes.activos-baja.excel', request()->query()) }}"
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

    {{-- Resumen --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Total --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr><th class="px-5 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Total de Activos Dados de Baja</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="px-5 py-4">
                            <span class="text-2xl font-extrabold text-slate-900">{{ $resumen['total'] }}</span>
                            <span class="text-xs text-slate-400 ml-1">
                                @if($resumen['total'] > 0)
                                    ({{ $resumen['con_motivo_registrado'] }} con motivo registrado)
                                @endif
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Por categoría --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Bajas por Categoría</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Cant.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($resumen['por_categoria'] as $categoria => $cantidad)
                        <tr>
                            <td class="px-5 py-2 text-slate-700">{{ $categoria }}</td>
                            <td class="px-5 py-2 text-right font-mono font-semibold text-slate-900">{{ $cantidad }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-5 py-3 text-slate-400 text-xs">Sin bajas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Por motivo --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Bajas por Motivo</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Cant.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($resumen['por_motivo'] as $motivo => $cantidad)
                        <tr>
                            <td class="px-5 py-2 {{ $motivo === 'No especificado' ? 'text-slate-400 italic' : 'text-slate-700' }}">{{ $motivo }}</td>
                            <td class="px-5 py-2 text-right font-mono font-semibold text-slate-900">{{ $cantidad }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-5 py-3 text-slate-400 text-xs">Sin bajas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Gráfico simple (secundario) de bajas por categoría --}}
    @if($resumen['total'] > 0 && $resumen['por_categoria']->count() > 1)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <h3 class="text-sm font-bold text-slate-900 mb-1">Bajas por Categoría</h3>
            <p class="text-xs text-slate-500 mb-3">Vista rápida de apoyo — la tabla detallada de abajo es la fuente completa del reporte.</p>
            <div class="w-full" style="height: {{ max(140, $resumen['por_categoria']->count() * 32) }}px;">
                <canvas id="chartBajasCategoria"></canvas>
            </div>
        </div>
    @endif

    {{-- Tabla Detallada --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Detalle de Activos Dados de Baja</h3>
                <p class="text-xs text-slate-500">Ordenado por fecha de baja, del más reciente al más antiguo</p>
            </div>
            <span class="text-xs font-medium text-slate-400">
                {{ count($activos) }} {{ count($activos) === 1 ? 'registro' : 'registros' }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Código</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Categoría</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Marca / Modelo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Última Ubicación</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Estado Previo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha de Baja</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Motivo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Responsable</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Observaciones</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Detalle</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse($activos as $activo)
                        <tr class="hover:bg-slate-50/70 transition align-top">
                            <td class="px-4 py-3 font-mono font-semibold text-slate-900">{{ $activo->codigo_patrimonial }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $activo->categoria->nombre_categoria ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ trim(($activo->marca ?? '') . ' ' . ($activo->modelo ?? '')) ?: '-' }}</td>
                            <td class="px-4 py-3 text-slate-700">
                                @if($activo->ubicacion)
                                    {{ $activo->ubicacion->seccion }}{{ $activo->ubicacion->aula ? ' - ' . $activo->ubicacion->aula : '' }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-700">{{ $activo->estado_previo_baja ?? 'No especificado' }}</td>
                            <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                {{ $activo->fecha_baja ? $activo->fecha_baja->format('d/m/Y') : 'No especificado' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($activo->motivo_baja)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                        {{ $activo->motivo_baja }}
                                    </span>
                                @else
                                    <span class="text-slate-400 italic text-xs">No especificado</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                {{ $activo->usuarioBaja ? trim($activo->usuarioBaja->nombre . ' ' . ($activo->usuarioBaja->apellido ?? '')) : 'No especificado' }}
                            </td>
                            <td class="px-4 py-3 text-slate-500 max-w-xs truncate" title="{{ $activo->observaciones }}">
                                {{ $activo->observaciones ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('admin.activos.show', $activo->id_activo) }}" class="btn btn-outline btn-xs" title="Ver detalle del activo">
                                    Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-slate-500">
                                No hay activos dados de baja que coincidan con los filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@if($resumen['total'] > 0 && $resumen['por_categoria']->count() > 1)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    function initChartBajasCategoria() {
        var canvas = document.getElementById('chartBajasCategoria');
        if (!canvas) return;

        function renderizar() {
            if (typeof Chart === 'undefined') return;

            var chartExistente = Chart.getChart(canvas);
            if (chartExistente) chartExistente.destroy();

            var etiquetas = @json($resumen['por_categoria']->keys());
            var valores = @json($resumen['por_categoria']->values());

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: etiquetas,
                    datasets: [{
                        label: 'Bajas',
                        data: valores,
                        backgroundColor: '#f97316',
                        borderRadius: 3,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0, color: '#64748b', font: { size: 11 } }, grid: { color: '#f1f5f9' } },
                        y: { ticks: { color: '#334155', font: { size: 12 } }, grid: { display: false } }
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
        document.addEventListener('DOMContentLoaded', initChartBajasCategoria);
    } else {
        initChartBajasCategoria();
    }
    document.addEventListener('turbo:load', initChartBajasCategoria);
})();
</script>
@endif
@endsection
