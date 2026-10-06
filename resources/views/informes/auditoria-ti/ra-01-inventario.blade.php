@extends('layouts.principal')

@section('title', 'RA-01 Inventario General de Activos Tecnológicos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900">RA-01 &middot; Inventario General de Activos Tecnológicos</h2>
        </div>
    </div>

    @include('informes.auditoria-ti._encabezado', [
        'titulo' => 'Inventario General de Activos Tecnológicos',
        'requerimiento' => 'Req. 5',
        'criterio' => config('auditoria.criterios')['7.1'],
        'filtros' => $filtros,
    ])

    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.informes.auditoria-ti.ra01') }}" class="flex flex-col sm:flex-row flex-wrap gap-3 sm:items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Fecha de Corte</label>
                <input type="date" name="fecha_corte" value="{{ $filtros['fecha_corte'] }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Categoría</label>
                <select name="fk_id_categoria" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800">
                    <option value="">Todas</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id_categoria }}" {{ $filtros['fk_id_categoria'] == $cat->id_categoria ? 'selected' : '' }}>{{ $cat->nombre_categoria }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Ubicación</label>
                <select name="fk_id_ubicacion" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800">
                    <option value="">Todas</option>
                    @foreach($ubicaciones as $ub)
                        <option value="{{ $ub->id_ubicacion }}" {{ $filtros['fk_id_ubicacion'] == $ub->id_ubicacion ? 'selected' : '' }}>{{ $ub->seccion }}{{ $ub->aula ? ' - ' . $ub->aula : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">Filtrar</button>
                <a href="{{ route('admin.informes.auditoria-ti.ra01.pdf', $filtros) }}" class="btn btn-secondary" target="_blank">Exportar PDF</a>
                <a href="{{ route('admin.informes.auditoria-ti.ra01.excel', $filtros) }}" class="btn btn-secondary">Exportar Excel</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 text-center">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Total Activos</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ $datos['total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 text-center">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Sin Ubicación</p>
            <p class="text-2xl font-extrabold {{ $datos['alertas']['sin_ubicacion'] > 0 ? 'text-rose-700' : 'text-slate-900' }}">{{ $datos['alertas']['sin_ubicacion'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 text-center">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Vida Útil Vencida</p>
            <p class="text-2xl font-extrabold {{ $datos['alertas']['vida_util_vencida'] > 0 ? 'text-amber-700' : 'text-slate-900' }}">{{ $datos['alertas']['vida_util_vencida'] }}</p>
        </div>
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200 text-center">
            <p class="text-xs text-slate-500 uppercase tracking-wider">Categorías</p>
            <p class="text-2xl font-extrabold text-slate-900">{{ $datos['por_categoria']->count() }}</p>
        </div>
    </div>

    <p class="text-[11px] text-slate-400 -mt-2">
        No existe en el sistema un campo de "responsable del activo"; ese indicador se omite del reporte (ver anotación en la entrega del módulo).
    </p>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200">
            <h3 class="text-sm font-bold text-slate-900">Listado de Activos (a la fecha de corte)</h3>
        </div>
        <div class="overflow-x-auto max-h-[520px] overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50 sticky top-0">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Código</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Categoría</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Ubicación</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Estado Operativo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Etapa Ciclo de Vida</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Adquisición</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Meses Restantes</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($datos['activos'] as $a)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 font-mono text-xs">{{ $a->codigo_patrimonial }}</td>
                            <td class="px-4 py-3">{{ $a->categoria->nombre_categoria ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $a->ubicacion ? trim($a->ubicacion->seccion . ' ' . ($a->ubicacion->aula ?? '')) : 'Sin ubicación' }}</td>
                            <td class="px-4 py-3">{{ $a->estado_operativo }}</td>
                            <td class="px-4 py-3">{{ $a->etapa_ciclo_vida }}</td>
                            <td class="px-4 py-3">{{ $a->fecha_adquisicion ? \Carbon\Carbon::parse($a->fecha_adquisicion)->format('d/m/Y') : '-' }}</td>
                            <td class="px-4 py-3 text-center font-mono {{ $a->vida_util['meses_restantes'] <= 0 ? 'text-rose-700 font-bold' : '' }}">{{ $a->vida_util['meses_restantes'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Sin activos que coincidan con los filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
