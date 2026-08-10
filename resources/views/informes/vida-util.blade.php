@extends('layouts.principal')

@section('title', 'Vida Útil Remanente - Informes y Reportes')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Vida Útil Remanente</h2>
            <p class="text-xs text-slate-500">Porcentaje de vida útil consumida por activo, según la vida útil de su categoría</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.informes.vida-util') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Categoría</label>
                <select name="fk_id_categoria" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition">
                    <option value="">Todas las categorías</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id_categoria }}" {{ request('fk_id_categoria') == $categoria->id_categoria ? 'selected' : '' }}>{{ $categoria->nombre_categoria }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Ubicación</label>
                <select name="fk_id_ubicacion" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition">
                    <option value="">Todas las ubicaciones</option>
                    @foreach($ubicaciones as $ubicacion)
                        <option value="{{ $ubicacion->id_ubicacion }}" {{ request('fk_id_ubicacion') == $ubicacion->id_ubicacion ? 'selected' : '' }}>{{ $ubicacion->seccion }} - {{ $ubicacion->aula }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">Filtrar</button>
                <a href="{{ route('admin.informes.vida-util.pdf', request()->query()) }}" class="btn btn-accent">PDF</a>
                <a href="{{ route('admin.informes.vida-util.excel', request()->query()) }}" class="btn btn-success">Excel</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Activo</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Categoría</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Antigüedad</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Vida Útil</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Meses Restantes</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider w-56">% Consumido</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($activos as $activo)
                        @php
                            $vu = $activo->vida_util;
                            $critico = $vu['porcentaje_consumido'] >= 80 || $vu['meses_restantes'] <= 0;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition {{ $critico ? 'bg-rose-50/40' : '' }}">
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                {{ $activo->nombre_equipo }}
                                <span class="block text-[11px] font-mono text-slate-400 font-normal">{{ $activo->codigo_patrimonial }}</span>
                            </td>
                            <td class="px-6 py-4 text-center text-slate-700">{{ $activo->categoria->nombre_categoria ?? '-' }}</td>
                            <td class="px-6 py-4 text-center font-mono text-slate-700">{{ $vu['antiguedad_meses'] }}m</td>
                            <td class="px-6 py-4 text-center font-mono text-slate-700">{{ $vu['vida_util_meses'] }}m</td>
                            <td class="px-6 py-4 text-center font-mono font-bold {{ $vu['meses_restantes'] <= 0 ? 'text-rose-600' : 'text-slate-800' }}">
                                {{ $vu['meses_restantes'] }}m
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $critico ? 'bg-rose-500' : ($vu['porcentaje_consumido'] >= 60 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, $vu['porcentaje_consumido']) }}%"></div>
                                    </div>
                                    <span class="text-xs font-mono font-bold {{ $critico ? 'text-rose-700' : 'text-slate-700' }} w-12 text-right">{{ $vu['porcentaje_consumido'] }}%</span>
                                    @if($critico)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">⚠</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">No hay activos que coincidan con los filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
