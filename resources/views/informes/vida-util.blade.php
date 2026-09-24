@extends('layouts.principal')

@section('title', 'Vida Útil - Informes y Reportes')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Encabezado Principal --}}
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Vida Útil</h2>
            <p class="text-xs text-slate-500">Monitoreo de desgaste, tiempo de vida restante y sugerencias de reemplazo tecnológico</p>
        </div>
    </div>

    {{-- 1. Resumen General (Tarjetas KPI Compactas) --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        {{-- Total de activos --}}
        <div class="bg-white rounded-xl p-3.5 shadow-xs border border-slate-200 flex items-center justify-between hover:shadow-sm transition">
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider truncate">Total de activos</p>
                <p class="text-2xl font-bold font-mono text-slate-900 mt-0.5">{{ $resumenGeneral['total_activos'] }}</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
        </div>

        {{-- Sugerencia de reemplazo --}}
        <div class="bg-rose-50/40 rounded-xl p-3.5 shadow-xs border border-rose-200 flex items-center justify-between hover:shadow-sm transition">
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-rose-700 uppercase tracking-wider truncate">Sugerencia reemplazo</p>
                <p class="text-2xl font-bold font-mono text-rose-700 mt-0.5">{{ $resumenGeneral['sugerencia_reemplazo'] }}</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0 border border-rose-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
        </div>

        {{-- Vida útil superada --}}
        <div class="bg-red-50/40 rounded-xl p-3.5 shadow-xs border border-red-200 flex items-center justify-between hover:shadow-sm transition">
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-red-700 uppercase tracking-wider truncate">Vida útil superada</p>
                <p class="text-2xl font-bold font-mono text-red-700 mt-0.5">{{ $resumenGeneral['superaron_vida_util'] }}</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-red-100 text-red-700 flex items-center justify-center shrink-0 border border-red-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        {{-- Cumple en 1 año --}}
        <div class="bg-amber-50/40 rounded-xl p-3.5 shadow-xs border border-amber-200 flex items-center justify-between hover:shadow-sm transition">
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-amber-700 uppercase tracking-wider truncate">Cumple en 1 año</p>
                <p class="text-2xl font-bold font-mono text-amber-700 mt-0.5">{{ $resumenGeneral['cumplen_un_anio'] }}</p>
            </div>
            <div class="w-9 h-9 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 border border-amber-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- 2. Resumen por Categoría de Activo (Desplegable) --}}
    <details class="group bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden transition-all duration-200">
        <summary class="px-5 py-3.5 bg-slate-50/70 border-b border-slate-200 flex items-center justify-between cursor-pointer list-none [&::-webkit-details-marker]:hidden hover:bg-slate-100/70 transition select-none">
            <div class="flex items-center gap-2.5">
                <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100 shrink-0">
                    <svg class="h-4 w-4 transform transition-transform duration-200 group-open:rotate-90" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 inline-block">Resumen por Categoría</h3>
                    <span class="text-xs text-slate-500 ml-2 hidden sm:inline">— Desglose de activos y situación de vida útil</span>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-white text-slate-600 border border-slate-200 shadow-2xs">
                    {{ $resumenCategorias->count() }} categorías
                </span>
                <span class="text-xs font-medium text-indigo-600 group-open:hidden flex items-center gap-1">
                    Ver desglose
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </span>
                <span class="text-xs font-medium text-slate-500 hidden group-open:flex items-center gap-1">
                    Ocultar
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                </span>
            </div>
        </summary>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Categoría</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Vida útil superada</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Cumple en 1 año</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Sugerencia de reemplazo</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($resumenCategorias as $cat)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-3 font-semibold text-slate-900">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full {{ $cat['sugerencia_reemplazo'] > 0 ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                                    <span>{{ $cat['nombre'] }}</span>
                                    <span class="text-[11px] text-slate-400 font-normal">({{ $cat['vida_util_meses'] }}m)</span>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-center font-mono font-bold text-slate-800">{{ $cat['total'] }}</td>
                            <td class="px-6 py-3 text-center font-mono">
                                @if($cat['superada'] > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">
                                        {{ $cat['superada'] }}
                                    </span>
                                @else
                                    <span class="text-slate-400 font-medium">0</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center font-mono">
                                @if($cat['cumple_1_anio'] > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        {{ $cat['cumple_1_anio'] }}
                                    </span>
                                @else
                                    <span class="text-slate-400 font-medium">0</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center font-mono">
                                @if($cat['sugerencia_reemplazo'] > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        {{ $cat['sugerencia_reemplazo'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        0 (Al día)
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                No hay categorías registradas en el sistema.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($resumenCategorias->isNotEmpty())
                    <tfoot class="bg-slate-50 font-bold border-t-2 border-slate-200">
                        <tr>
                            <td class="px-6 py-2.5 text-slate-800 uppercase text-xs tracking-wider">Total General</td>
                            <td class="px-6 py-2.5 text-center font-mono text-slate-900">{{ $resumenCategorias->sum('total') }}</td>
                            <td class="px-6 py-2.5 text-center font-mono text-red-700">{{ $resumenCategorias->sum('superada') }}</td>
                            <td class="px-6 py-2.5 text-center font-mono text-amber-700">{{ $resumenCategorias->sum('cumple_1_anio') }}</td>
                            <td class="px-6 py-2.5 text-center font-mono text-rose-800">{{ $resumenCategorias->sum('sugerencia_reemplazo') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </details>

    {{-- 3. Filtros y Listado de Activos --}}
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
                        <option value="{{ $ubicacion->id_ubicacion }}" {{ request('fk_id_ubicacion') == $ubicacion->id_ubicacion ? 'selected' : '' }}>{{ $ubicacion->seccion }}{{ $ubicacion->aula ? ' - ' . $ubicacion->aula : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">Filtrar</button>
                @if(request()->filled('fk_id_categoria') || request()->filled('fk_id_ubicacion'))
                    <a href="{{ route('admin.informes.vida-util') }}" class="btn btn-secondary">Limpiar</a>
                @endif
                <a href="{{ route('admin.informes.vida-util.pdf', request()->query()) }}"
                   class="btn btn-secondary flex items-center gap-1.5" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exportar PDF
                </a>
                <a href="{{ route('admin.informes.vida-util.excel', request()->query()) }}"
                   class="btn btn-secondary flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exportar Excel
                </a>
            </div>
        </form>
    </div>

    {{-- Tabla de Activos --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/60 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Listado de Activos</h3>
                <p class="text-xs text-slate-500">
                    Mostrando {{ $activos->firstItem() ?? 0 }} - {{ $activos->lastItem() ?? 0 }} de {{ $activos->total() }} activos registrados
                </p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                10 por página
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Activo</th>
                        <th class="px-5 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Categoría</th>
                        <th class="px-5 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Ubicación</th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Antigüedad</th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Vida Útil</th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Meses Restantes</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider w-48">% Consumido</th>
                        <th class="px-5 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider whitespace-nowrap">Estado / Sugerencia</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($activos as $activo)
                        @php
                            $vu = $activo->vida_util;
                            $superada = ($vu['meses_restantes'] ?? 1) <= 0;
                            $cumple1Anio = !$superada && ($vu['meses_restantes'] ?? 0) <= 12;
                            $reemplazo = $superada || $cumple1Anio;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition {{ $superada ? 'bg-rose-50/30' : ($cumple1Anio ? 'bg-amber-50/20' : '') }}">
                            <td class="px-6 py-3.5">
                                <span class="font-semibold text-slate-900 block">{{ $activo->nombre_equipo }}</span>
                                <span class="text-[11px] font-mono text-slate-400 font-normal">{{ $activo->codigo_patrimonial }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-center text-slate-700 whitespace-nowrap">{{ $activo->categoria->nombre_categoria ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-center text-slate-600 text-xs whitespace-nowrap">
                                {{ $activo->ubicacion ? $activo->ubicacion->seccion . ($activo->ubicacion->aula ? ' - ' . $activo->ubicacion->aula : '') : '-' }}
                            </td>
                            <td class="px-4 py-3.5 text-center font-mono text-slate-700 whitespace-nowrap">{{ $vu['antiguedad_meses'] }}m</td>
                            <td class="px-4 py-3.5 text-center font-mono text-slate-700 whitespace-nowrap">{{ $vu['vida_util_meses'] }}m</td>
                            <td class="px-4 py-3.5 text-center font-mono font-bold whitespace-nowrap {{ $superada ? 'text-rose-600' : ($cumple1Anio ? 'text-amber-600' : 'text-slate-800') }}">
                                {{ $vu['meses_restantes'] }}m
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $superada ? 'bg-rose-500' : ($vu['porcentaje_consumido'] >= 75 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min(100, $vu['porcentaje_consumido']) }}%"></div>
                                    </div>
                                    <span class="text-xs font-mono font-bold {{ $superada ? 'text-rose-700' : 'text-slate-700' }} w-12 text-right">{{ $vu['porcentaje_consumido'] }}%</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                @if($superada)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        ⚠ Superada (Reemplazo)
                                    </span>
                                @elseif($cumple1Anio)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        ⏳ ≤ 1 año (Reemplazo)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        ✓ En tiempo
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                No hay activos que coincidan con los filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Paginación (10 activos por página) --}}
    <div class="mt-4">
        {{ $activos->links() }}
    </div>

</div>
@endsection
