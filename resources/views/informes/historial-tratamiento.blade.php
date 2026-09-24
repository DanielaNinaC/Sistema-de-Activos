@extends('layouts.principal')

@section('title', 'Historial de Tratamiento - Informes y Reportes')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Cabecera --}}
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Historial de Tratamiento</h2>
            <p class="text-xs text-slate-500">Compara, por cada evaluación de riesgo del activo, qué se sugirió tratar contra qué se hizo realmente</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.informes.historial-tratamiento') }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Activo *</label>
                    <select name="fk_id_activo" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" required>
                        <option value="">Seleccione un activo</option>
                        @foreach($activos as $act)
                            <option value="{{ $act->id_activo }}" {{ (string) request('fk_id_activo') === (string) $act->id_activo ? 'selected' : '' }}>
                                {{ $act->codigo_patrimonial }} — {{ $act->nombre_equipo }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Evaluado Desde</label>
                    <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Evaluado Hasta</label>
                    <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                </div>
            </div>
            <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                <button type="submit" class="btn btn-dark">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Consultar
                </button>
                <a href="{{ route('admin.informes.historial-tratamiento') }}" class="btn btn-outline">Limpiar</a>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    @if(!request()->filled('fk_id_activo'))
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center text-slate-500">
            Selecciona un activo para ver su historial de tratamiento.
        </div>
    @else
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Sugerido vs. Ejecutado</h3>
                    <p class="text-xs text-slate-500">Una fila por cada evaluación histórica de este activo, de la más reciente a la más antigua</p>
                </div>
                <span class="text-xs font-medium text-slate-400">
                    {{ count($filas) }} {{ count($filas) === 1 ? 'evaluación' : 'evaluaciones' }}
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha Evaluación</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Nivel</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Modo de Falla</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Estrategia Sugerida</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Acción Sugerida</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Estrategia Aplicada</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Resultado Real</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @forelse($filas as $fila)
                            <tr class="hover:bg-slate-50/70 transition align-top">
                                <td class="px-4 py-3 text-slate-700 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($fila['fecha_evaluacion'])->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $colores = ['Crítico' => 'bg-rose-100 text-rose-800', 'Alto' => 'bg-orange-100 text-orange-800', 'Medio' => 'bg-amber-100 text-amber-800', 'Bajo' => 'bg-emerald-100 text-emerald-800'];
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $colores[$fila['nivel_riesgo']] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ $fila['nivel_riesgo'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $fila['modo_falla'] }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $fila['estrategia_sugerida'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-500 max-w-xs" title="{{ $fila['accion_sugerida'] }}">{{ $fila['accion_sugerida'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-700 font-semibold">{{ $fila['estrategia_aplicada'] ?? '—' }}</td>
                                <td class="px-4 py-3 max-w-sm">
                                    @if($fila['resultado_real'] === 'En ejecución')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">En ejecución</span>
                                    @elseif($fila['resultado_real'] === 'Sin tratamiento registrado')
                                        <span class="text-slate-400 italic text-xs">Sin tratamiento registrado</span>
                                    @else
                                        <span class="text-slate-700">{{ $fila['resultado_real'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                    Este activo no tiene evaluaciones de riesgo registradas todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
