@extends('layouts.principal')

@section('title','Riesgos - Plan de Tratamiento')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition shadow-sm" aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Plan de tratamiento</h2>
                <p class="text-xs text-slate-500">Define estrategias y acciones preventivas para cada evaluación (ISO 31000)</p>
            </div>
        </div>

        <button type="button" onclick="toggleFormTratamiento()" id="btnToggleTratamiento" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition shadow-md shadow-blue-200 self-start sm:self-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Registrar Plan de Tratamiento</span>
        </button>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 flex items-center gap-3 shadow-sm transition text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Evaluaciones críticas sin tratamiento --}}
    @if($evaluacionesSinTratamiento->isNotEmpty())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-900 shadow-sm space-y-3">
            <div class="flex items-center gap-2 font-bold text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                {{ $evaluacionesSinTratamiento->count() }} {{ $evaluacionesSinTratamiento->count() === 1 ? 'evaluación crítica no tiene' : 'evaluaciones críticas no tienen' }} ningún tratamiento registrado
            </div>
            <div class="overflow-x-auto rounded-lg border border-rose-200 bg-white">
                <table class="min-w-full divide-y divide-rose-100 text-sm">
                    <thead class="bg-rose-50/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-rose-700 uppercase">Activo</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-rose-700 uppercase">Nivel</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-rose-700 uppercase">Score</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-rose-700 uppercase">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-50">
                        @foreach($evaluacionesSinTratamiento as $ev)
                            <tr>
                                <td class="px-4 py-2.5 text-slate-800 font-medium">
                                    {{ $ev->activo->nombre_equipo ?? '-' }}
                                    <span class="block text-[11px] font-mono text-slate-400">{{ $ev->activo->codigo_patrimonial ?? '' }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $ev->nivel_riesgo === 'Crítico' ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-orange-100 text-orange-800 border border-orange-200' }}">
                                        {{ $ev->nivel_riesgo }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-center font-mono font-bold text-slate-800">{{ number_format($ev->score_calculado, 2) }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <button type="button" onclick="crearTratamientoPara({{ $ev->id_evaluacion }})" class="inline-flex items-center gap-1.5 bg-rose-600 hover:bg-rose-700 text-white px-3 py-1 rounded-lg transition text-xs font-semibold">
                                        Crear tratamiento
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Formulario para Registrar Tratamiento (Oculto inicialmente) --}}
    <div class="hidden bg-white rounded-2xl p-6 shadow-sm border border-slate-200 max-w-6xl mx-auto transition-all duration-300" id="formTratamientoCard">
        <h3 class="text-base font-bold text-slate-900 mb-4">Registrar Plan de Tratamiento</h3>
        <form method="POST" action="{{ route('admin.riesgos.tratamiento.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Evaluación de Riesgo <span class="text-rose-500">*</span></label>
                <select name="fk_id_evaluacion" id="selectEvaluacionTratamiento" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" data-searchable data-search-placeholder="Buscar evaluación..." required>
                    <option value="">Seleccione una evaluación</option>
                    @foreach($evaluaciones as $evaluacion)
                        <option value="{{ $evaluacion->id_evaluacion }}">#{{ $evaluacion->id_evaluacion }} - {{ $evaluacion->activo->nombre_equipo ?? 'Activo' }} - {{ $evaluacion->nivel_riesgo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Estrategia (ISO 31000) <span class="text-rose-500">*</span></label>
                <select name="estrategia" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" required>
                    <option value="">-- Seleccionar estrategia --</option>
                    @foreach($estrategias as $estrategia)
                        <option value="{{ $estrategia }}" {{ old('estrategia') == $estrategia ? 'selected' : '' }}>{{ $estrategia }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Estado del Tratamiento <span class="text-rose-500">*</span></label>
                <select name="estado_tratamiento" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" required>
                    @foreach($estados as $estado)
                        <option value="{{ $estado }}" {{ old('estado_tratamiento', 'Pendiente') == $estado ? 'selected' : '' }}>{{ $estado }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Acción Preventiva / Mitigante <span class="text-rose-500">*</span></label>
                <textarea name="accion_preventiva" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" required>{{ old('accion_preventiva') }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Fecha de Compromiso</label>
                <input name="fecha_compromiso" type="date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" value="{{ old('fecha_compromiso') }}" />
            </div>
            <div class="md:col-span-2 flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="toggleFormTratamiento()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl text-xs transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition shadow-sm">
                    Guardar Tratamiento
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Evaluación</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estrategia</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Origen</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Compromiso</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tratamientos as $tratamiento)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700">#{{ $tratamiento->evaluacion->id_evaluacion ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $tratamiento->evaluacion->activo->nombre_equipo ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $tratamiento->estrategia }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $tratamiento->estado_tratamiento === 'Completado' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : ($tratamiento->estado_tratamiento === 'En Proceso' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-700 border border-slate-200') }}">
                                    {{ $tratamiento->estado_tratamiento }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                @if($tratamiento->origen === 'automatico')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">⚙ Automático</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-50 text-slate-600 border border-slate-200">Manual</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $tratamiento->fecha_compromiso ? \Carbon\Carbon::parse($tratamiento->fecha_compromiso)->format('d/m/Y') : '-' }}</td>
                            <td class="px-6 py-4 text-sm text-center">
                                @if($tratamiento->estado_tratamiento !== 'Completado')
                                    <button type="button"
                                            onclick="openEstadoModal({{ $tratamiento->id_tratamiento }}, '{{ $tratamiento->estado_tratamiento }}')"
                                            class="inline-flex items-center gap-1 bg-slate-800 hover:bg-slate-900 text-white px-3 py-1 rounded-lg transition text-xs font-semibold">
                                        Actualizar estado
                                    </button>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">No hay planes de tratamiento registrados todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $tratamientos->links() }}</div>
</div>

<!-- Modal para actualizar estado del tratamiento -->
<div id="estadoModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5 border border-slate-100 transform transition-all scale-95 opacity-0" id="estadoModalCard">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Actualizar Estado del Tratamiento</h3>
            <button type="button" onclick="closeEstadoModal()" class="text-slate-400 hover:text-slate-600 transition">✕</button>
        </div>

        <form id="estadoForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Estado <span class="text-rose-500">*</span></label>
                    <select id="estado_tratamiento_input" name="estado_tratamiento" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        @foreach($estados as $estado)
                            <option value="{{ $estado }}">{{ $estado }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-amber-700 mt-1.5 hidden" id="avisoCompletado">
                        Al marcar "Completado" se ejecutará automáticamente una nueva evaluación de riesgo del activo asociado.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-5 mt-4 border-t border-slate-100">
                <button type="button" onclick="closeEstadoModal()" class="px-4 py-2.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition shadow-sm">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleFormTratamiento() {
        var card = document.getElementById('formTratamientoCard');
        var btn = document.getElementById('btnToggleTratamiento');
        if (!card) return;

        var isHidden = card.classList.contains('hidden');
        if (isHidden) {
            card.classList.remove('hidden');
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (btn) {
                btn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Cerrar Formulario</span>
                `;
            }
        } else {
            card.classList.add('hidden');
            if (btn) {
                btn.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Registrar Plan de Tratamiento</span>
                `;
            }
        }
    }

    function crearTratamientoPara(idEvaluacion) {
        var card = document.getElementById('formTratamientoCard');
        if (card && card.classList.contains('hidden')) {
            toggleFormTratamiento();
        }
        var select = document.getElementById('selectEvaluacionTratamiento');
        if (select) {
            select.value = idEvaluacion;
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Enlace directo desde Informes > Evaluaciones Sin Tratamiento (?crear=ID)
    (function preseleccionarDesdeQuery() {
        var params = new URLSearchParams(window.location.search);
        var idEvaluacion = params.get('crear');
        if (idEvaluacion) {
            crearTratamientoPara(idEvaluacion);
        }
    })();

    @if($errors->any() || old('fk_id_evaluacion'))
        (function autoAbrirFormulario() {
            var card = document.getElementById('formTratamientoCard');
            if (card && card.classList.contains('hidden')) {
                toggleFormTratamiento();
            }
        })();
    @endif

    function openEstadoModal(id, estadoActual) {
        var form = document.getElementById('estadoForm');
        form.action = '/admin/riesgos/plan-tratamiento/' + id;

        var select = document.getElementById('estado_tratamiento_input');
        select.value = estadoActual;
        toggleAvisoCompletado();

        var modal = document.getElementById('estadoModal');
        var card = document.getElementById('estadoModalCard');
        modal.classList.remove('hidden');
        setTimeout(function () {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeEstadoModal() {
        var modal = document.getElementById('estadoModal');
        var card = document.getElementById('estadoModalCard');
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(function () {
            modal.classList.add('hidden');
        }, 150);
    }

    function toggleAvisoCompletado() {
        var select = document.getElementById('estado_tratamiento_input');
        var aviso = document.getElementById('avisoCompletado');
        aviso.classList.toggle('hidden', select.value !== 'Completado');
    }

    (function initPlanTratamiento() {
        var select = document.getElementById('estado_tratamiento_input');
        if (!select) return;
        select.removeEventListener('change', toggleAvisoCompletado);
        select.addEventListener('change', toggleAvisoCompletado);
    })();
</script>
@endsection
