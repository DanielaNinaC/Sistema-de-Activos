@extends('layouts.principal')

@section('title', 'Auditoría Consolidada - Informes y Reportes')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition shadow-sm" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Reportes</h2>
            <p class="text-xs text-slate-500">Resumen ejecutivo del sistema de gestión de activos y riesgos — documento formal para Dirección</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.informes.auditoria') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Período Desde <span class="text-rose-500">*</span></label>
                <input type="date" name="fecha_inicio" value="{{ $fechaInicio }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" required />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Período Hasta <span class="text-rose-500">*</span></label>
                <input type="date" name="fecha_fin" value="{{ $fechaFin }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" required />
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-sm transition shadow-sm">Generar Resumen</button>
                <a href="{{ route('admin.informes.auditoria.pdf', ['fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]) }}" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-sm transition shadow-md shadow-indigo-200 inline-flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Generar PDF
                </a>
            </div>
        </form>
        <p class="text-[11px] text-slate-400 mt-2">Período: {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</p>
    </div>

    {{-- Tabla Resumen de Indicadores Consolidados --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50/60">
            <div>
                <h3 class="text-base font-bold text-slate-900">Resumen General de Indicadores del Sistema</h3>
                <p class="text-xs text-slate-500">Métricas consolidadas de activos, mantenimientos e indicadores de riesgo</p>
            </div>
            <span class="text-xs font-semibold px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full border border-indigo-100">Informe Ejecutivo</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Indicador / Métrica</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Valor Registrado</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Estado / Diagnóstico</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4 font-semibold text-slate-800">Total Activos Registrados</td>
                        <td class="px-6 py-4 text-center font-mono font-bold text-slate-900 text-base">{{ $resumen['total_activos'] }}</td>
                        <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Inventario General</span></td>
                    </tr>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4 font-semibold text-slate-800">Incidentes Reportados en el Período</td>
                        <td class="px-6 py-4 text-center font-mono font-bold text-slate-900 text-base">{{ $resumen['total_incidentes'] }}</td>
                        <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">Registrados</span></td>
                    </tr>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4 font-semibold text-slate-800">Mantenimientos Ejecutados</td>
                        <td class="px-6 py-4 text-center font-mono font-bold text-slate-900 text-base">{{ $resumen['total_mantenimientos'] }}</td>
                        <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">Ejecutados</span></td>
                    </tr>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4 font-semibold text-slate-800">Vida Útil Consumida Promedio</td>
                        <td class="px-6 py-4 text-center font-mono font-bold text-slate-900 text-base">{{ $resumen['promedio_vida_util_consumida'] }}%</td>
                        <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">Promedio global</span></td>
                    </tr>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4 font-semibold text-slate-800">Tratamientos de Riesgo Pendientes</td>
                        <td class="px-6 py-4 text-center font-mono font-bold text-amber-700 text-base">{{ $resumen['tratamientos_pendientes'] }}</td>
                        <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">En Curso / Pendientes</span></td>
                    </tr>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4 font-semibold text-slate-800">Tratamientos de Riesgo Vencidos</td>
                        <td class="px-6 py-4 text-center font-mono font-bold text-rose-700 text-base">{{ $resumen['tratamientos_vencidos'] }}</td>
                        <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">⚠ Vencidos</span></td>
                    </tr>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4 font-semibold text-slate-800">Activos en Nivel de Riesgo Crítico</td>
                        <td class="px-6 py-4 text-center font-mono font-bold text-rose-700 text-base">{{ $resumen['distribucion_riesgo']['Crítico'] ?? 0 }}</td>
                        <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">🔴 Riesgo Crítico</span></td>
                    </tr>
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4 font-semibold text-slate-800">Activos en Nivel de Riesgo Alto</td>
                        <td class="px-6 py-4 text-center font-mono font-bold text-orange-700 text-base">{{ $resumen['distribucion_riesgo']['Alto'] ?? 0 }}</td>
                        <td class="px-6 py-4"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-800 border border-orange-200">🟠 Riesgo Alto</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tablas detalladas por sección --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200">
                <h3 class="text-sm font-bold text-slate-900">Distribución de Activos por Nivel de Riesgo</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Nivel de Riesgo</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Total Activos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($resumen['distribucion_riesgo'] as $nivel => $total)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-3 font-semibold text-slate-700">{{ $nivel }}</td>
                            <td class="px-6 py-3 text-right font-mono font-bold text-slate-900">{{ $total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200">
                <h3 class="text-sm font-bold text-slate-900">Mantenimientos por Tipo (período)</h3>
            </div>
            @if($resumen['mantenimientos_por_tipo']->isEmpty())
                <p class="text-sm text-slate-400 text-center py-6">Sin mantenimientos ejecutados en el período.</p>
            @else
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Tipo de Mantenimiento</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">Total Ejecutados</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($resumen['mantenimientos_por_tipo'] as $tipo => $total)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-3 font-semibold text-slate-700">{{ $tipo }}</td>
                                <td class="px-6 py-3 text-right font-mono font-bold text-slate-900">{{ $total }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</div>
@endsection
