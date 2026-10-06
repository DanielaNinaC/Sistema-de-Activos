@extends('layouts.principal')

@section('title', 'RA-03 Registro de Incidentes de la Gestión')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <h2 class="text-2xl font-bold text-slate-900">RA-03 &middot; Registro de Incidentes de la Gestión</h2>
    </div>

    @include('informes.auditoria-ti._encabezado', [
        'titulo' => 'Registro de Incidentes — Gestión ' . $filtros['gestion'],
        'requerimiento' => 'Req. 21',
        'criterio' => config('auditoria.criterios')['7.4'],
        'filtros' => $filtros,
    ])

    <p class="text-[11px] text-slate-400">
        El sistema no cuenta con una columna de "tipo de incidente"; ese filtro se omite del reporte (ver anotación en la entrega del módulo).
    </p>

    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.informes.auditoria-ti.ra03') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Gestión (Año)</label>
                <input type="number" name="gestion" value="{{ $filtros['gestion'] }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm w-28" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Fecha de Corte</label>
                <input type="date" name="fecha_corte" value="{{ $filtros['fecha_corte'] }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" />
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">Filtrar</button>
                <a href="{{ route('admin.informes.auditoria-ti.ra03.pdf', $filtros) }}" class="btn btn-secondary" target="_blank">Exportar PDF</a>
                <a href="{{ route('admin.informes.auditoria-ti.ra03.excel', $filtros) }}" class="btn btn-secondary">Exportar Excel</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 text-center">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Total Incidentes</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ $datos['total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 text-center">
            <p class="text-xs text-slate-500 uppercase tracking-wider">TMR (días)</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ $datos['tmr_dias'] ?? 'N/A' }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 text-center">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Abiertos a la Fecha de Corte</p>
            <p class="text-2xl font-extrabold {{ $datos['abiertos_a_corte'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">{{ $datos['abiertos_a_corte'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 text-center">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Categorías Afectadas</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ $datos['por_categoria']->count() }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200">
            <h3 class="text-sm font-bold text-slate-900">Detalle de Incidentes</h3>
        </div>
        <div class="overflow-x-auto max-h-[520px] overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50 sticky top-0">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Activo</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Categoría</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Apertura</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Cierre</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($datos['incidentes'] as $i)
                        <tr>
                            <td class="px-4 py-2.5">{{ $i->activo->nombre_equipo ?? '-' }}</td>
                            <td class="px-4 py-2.5">{{ $i->activo->categoria->nombre_categoria ?? '-' }}</td>
                            <td class="px-4 py-2.5">{{ \Carbon\Carbon::parse($i->fecha_incidente)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2.5">{{ $i->fecha_cierre ? \Carbon\Carbon::parse($i->fecha_cierre)->format('d/m/Y H:i') : '—' }}</td>
                            <td class="px-4 py-2.5">{{ $i->estado_incidente }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">Sin incidentes en la gestión seleccionada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
