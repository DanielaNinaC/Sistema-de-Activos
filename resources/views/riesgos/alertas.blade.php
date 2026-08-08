@extends('layouts.principal')

@section('title', 'Alertas - Gestión de Riesgos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Encabezado con Botón de Registro --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition shadow-sm" aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Alertas de Riesgo</h2>
                <p class="text-sm text-slate-500">Gestiona las alertas automáticas y manuales vinculadas a las evaluaciones FMEA</p>
            </div>
        </div>

        <button onclick="toggleRegistroForm()" id="btnToggleForm" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition shadow-md shadow-blue-200 self-start sm:self-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Registrar Alerta
        </button>
    </div>

    {{-- Alerta de Éxito --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 flex items-center gap-3 shadow-sm transition text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Errores de Validación --}}
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm text-xs space-y-1">
            <div class="font-bold">Error en los datos ingresados:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Formulario de Registro (colapsable) --}}
    <div id="registroForm" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden transition-all duration-300" style="display: none;">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
            <h3 class="text-base font-bold text-slate-900">Nueva Alerta de Riesgo</h3>
            <p class="text-xs text-slate-500 mt-0.5">Asocie una alerta a una evaluación de riesgo FMEA</p>
        </div>

        <form method="POST" action="{{ route('admin.riesgos.alertas.store') }}" class="p-6 space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Evaluación FMEA Origen <span class="text-rose-500">*</span></label>
                    <select name="fk_id_evaluacion" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" data-searchable data-search-placeholder="Buscar evaluación..." required>
                        <option value="">-- Seleccionar Evaluación --</option>
                        @foreach($evaluaciones as $evaluacion)
                            <option value="{{ $evaluacion->id_evaluacion }}" {{ old('fk_id_evaluacion') == $evaluacion->id_evaluacion ? 'selected' : '' }}>
                                [#{{ $evaluacion->id_evaluacion }}] {{ $evaluacion->activo->nombre_equipo ?? 'Activo' }} ({{ $evaluacion->activo->codigo_patrimonial ?? '' }}) — Nivel: {{ $evaluacion->nivel_riesgo }} (Score: {{ $evaluacion->score_calculado }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Tipo de Alerta <span class="text-rose-500">*</span></label>
                    <select name="tipo_alerta" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        <option value="">-- Seleccionar Tipo --</option>
                        <option value="Roja" {{ old('tipo_alerta') == 'Roja' ? 'selected' : '' }}>🔴 Alerta Roja (Crítico)</option>
                        <option value="Naranja" {{ old('tipo_alerta') == 'Naranja' ? 'selected' : '' }}>🟠 Alerta Naranja (Alto)</option>
                        <option value="Amarilla" {{ old('tipo_alerta') == 'Amarilla' ? 'selected' : '' }}>🟡 Alerta Amarilla (Medio)</option>
                        <option value="Verde" {{ old('tipo_alerta') == 'Verde' ? 'selected' : '' }}>🟢 Alerta Verde (Bajo)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Fecha y Hora de Generación <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="fecha_generacion" value="{{ old('fecha_generacion', date('Y-m-d\TH:i')) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition font-mono" required />
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" id="notificacion_enviada" name="notificacion_enviada" value="1" {{ old('notificacion_enviada') ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                <label for="notificacion_enviada" class="text-xs font-medium text-slate-700">Marcar como notificación enviada al equipo técnico</label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="toggleRegistroForm()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl text-xs transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition shadow-sm flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Guardar Alerta
                </button>
            </div>
        </form>
    </div>

    {{-- Barra de Búsqueda --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.riesgos.alertas') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    type="text" 
                    name="buscar" 
                    value="{{ request('buscar') }}" 
                    placeholder="Buscar por activo, código patrimonial o tipo de alerta..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                >
            </div>
            
            <div class="flex items-center gap-2">
                <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-sm transition shadow-sm flex items-center gap-2">
                    Buscar
                </button>

                @if(request('buscar'))
                    <a href="{{ route('admin.riesgos.alertas') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl text-sm transition flex items-center gap-1">
                        ✕ Limpiar
                    </a>
                @endif
            </div>
        </form>

        @if(request('buscar'))
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                <span>Filtro activo para: <strong class="text-slate-800">"{{ request('buscar') }}"</strong></span>
                <span class="text-slate-300">•</span>
                <span>Resultados encontrados: <strong class="text-slate-800">{{ $alertas->total() }}</strong></span>
            </div>
        @endif
    </div>

    {{-- Tabla de Alertas --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden space-y-4">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Listado de Alertas Generadas</h3>
                <p class="text-xs text-slate-500 mt-0.5">Alertas clasificadas por nivel de riesgo y estado de notificación</p>
            </div>
            <span class="text-xs text-slate-400">Página {{ $alertas->currentPage() }} de {{ $alertas->lastPage() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-16 px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha Generación</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Activo Tecnológico</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Tipo de Alerta</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Estado Notificación</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($alertas as $alerta)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="w-16 px-6 py-4 text-center text-sm text-slate-700 font-mono">{{ $alerta->id_alerta }}</td>
                            <td class="px-6 py-4 text-center text-xs font-mono text-slate-600 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($alerta->fecha_generacion)->format('d/m/Y H:i') }} hs
                            </td>
                            <td class="px-6 py-4 text-center font-semibold text-slate-900">
                                {{ $alerta->evaluacion->activo->nombre_equipo ?? '-' }}
                                <span class="block text-[11px] font-mono text-slate-400 font-normal">
                                    {{ $alerta->evaluacion->activo->codigo_patrimonial ?? '' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                @if($alerta->tipo_alerta === 'Roja')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        🔴 Alerta Roja
                                    </span>
                                @elseif($alerta->tipo_alerta === 'Naranja')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-800 border border-orange-200">
                                        🟠 Alerta Naranja
                                    </span>
                                @elseif($alerta->tipo_alerta === 'Amarilla')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        🟡 Alerta Amarilla
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        🟢 Alerta Verde
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                @if($alerta->notificacion_enviada)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        ✓ Enviada
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        ⏱ Pendiente
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500 text-sm">
                                @if(request('buscar'))
                                    No se encontraron alertas que coincidan con la búsqueda.
                                @else
                                    No hay alertas registradas todavía.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($alertas->hasPages())
            <div class="px-6 py-3 border-t border-slate-100">
                {{ $alertas->links() }}
            </div>
        @endif
    </div>

</div>

<script>
    function toggleRegistroForm() {
        var form = document.getElementById('registroForm');
        var btn = document.getElementById('btnToggleForm');
        if (form.style.display === 'none') {
            form.style.display = 'block';
            btn.style.display = 'none';
        } else {
            form.style.display = 'none';
            btn.style.display = 'inline-flex';
        }
    }

    @if($errors->any())
        document.addEventListener('DOMContentLoaded', function() {
            toggleRegistroForm();
        });
    @endif
</script>
@endsection
