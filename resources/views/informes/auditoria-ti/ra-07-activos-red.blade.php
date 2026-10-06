@extends('layouts.principal')

@section('title', 'RA-07 Activos de Red por Ubicación')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <h2 class="text-2xl font-bold text-slate-900">RA-07 &middot; Activos de Red por Ubicación</h2>
    </div>

    @include('informes.auditoria-ti._encabezado', [
        'titulo' => 'Activos de Red por Ubicación',
        'requerimiento' => 'Req. 20 (parcial)',
        'criterio' => config('auditoria.criterios')['7.1'],
        'filtros' => $filtros,
    ])

    @if(!$datos['configurado'])
        <div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 text-sm text-amber-800">
            No se ha configurado la lista de categorías de red (<code>config('auditoria.categorias_red')</code>). Este
            reporte quedará vacío hasta que se indiquen los IDs de categoría correspondientes a equipos de red.
        </div>
    @else
        <div class="flex justify-end gap-2">
            <a href="{{ route('admin.informes.auditoria-ti.ra07.pdf', $filtros) }}" class="btn btn-secondary" target="_blank">Exportar PDF</a>
            <a href="{{ route('admin.informes.auditoria-ti.ra07.excel', $filtros) }}" class="btn btn-secondary">Exportar Excel</a>
        </div>

        @foreach($datos['por_ubicacion'] as $ubicacion => $activos)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200">
                    <h3 class="text-sm font-bold text-slate-900">{{ $ubicacion }}</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Código</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Categoría</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Estado Operativo</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($activos as $a)
                            <tr>
                                <td class="px-4 py-2.5 font-mono text-xs">{{ $a->codigo_patrimonial }}</td>
                                <td class="px-4 py-2.5">{{ $a->categoria->nombre_categoria ?? '-' }}</td>
                                <td class="px-4 py-2.5">{{ $a->estado_operativo }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        @if($datos['activos']->isEmpty())
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 px-6 py-8 text-center text-slate-400">
                No hay activos registrados en las categorías de red configuradas.
            </div>
        @endif
    @endif

</div>
@endsection
