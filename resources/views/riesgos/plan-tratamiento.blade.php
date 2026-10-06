@extends('layouts.principal')

@section('title','Riesgos - Plan de Tratamiento')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Plan de tratamiento</h2>
                <p class="text-xs text-slate-500">Define estrategias y acciones preventivas para cada evaluación (ISO 31000)</p>
            </div>
        </div>

        <button type="button" onclick="toggleFormTratamiento()" id="btnToggleTratamiento" class="btn btn-primary self-start sm:self-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>Registrar Plan de Tratamiento</span>
        </button>
    </div>

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
                                    <button type="button" onclick="crearTratamientoPara({{ $ev->id_evaluacion }})" class="btn btn-sm btn-danger">
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

    {{-- Formulario para Registrar/Revisar Tratamiento (Oculto inicialmente) --}}
    @php
        $modoRevision = isset($tratamientoParaRevisar) && $tratamientoParaRevisar !== null;
    @endphp
    <div class="hidden bg-white rounded-2xl p-6 shadow-sm border border-slate-200 max-w-6xl mx-auto transition-all duration-300" id="formTratamientoCard">
        <h3 class="text-base font-bold text-slate-900 mb-1">
            {{ $modoRevision ? 'Revisar Sugerencia de Tratamiento' : 'Registrar Plan de Tratamiento' }}
        </h3>

        @if($modoRevision)
            <div class="flex items-start gap-2 bg-blue-50 border border-blue-100 rounded-xl p-3 my-3 text-xs text-blue-800">
                <span>⚙</span>
                <span>
                    Este es un borrador generado automáticamente por el motor de riesgos. "Pendiente" significa <strong>sugerido, todavía sin revisar</strong> —
                    no es un tratamiento decidido. Confirma o ajusta la estrategia y la acción, y guarda para dejarlo revisado; no se crea un registro nuevo.
                </span>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.riesgos.tratamiento.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Evaluación de Riesgo <span class="text-rose-500">*</span></label>

                @if($modoRevision)
                    <input type="hidden" name="fk_id_evaluacion" value="{{ $tratamientoParaRevisar->fk_id_evaluacion }}">
                    <div class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-700">
                        #{{ $tratamientoParaRevisar->fk_id_evaluacion }} - {{ $tratamientoParaRevisar->evaluacion->activo->nombre_equipo ?? 'Activo' }} - {{ $tratamientoParaRevisar->evaluacion->nivel_riesgo ?? '' }}
                    </div>
                @else
                    <select name="fk_id_evaluacion" id="selectEvaluacionTratamiento" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" data-searchable data-search-placeholder="Buscar evaluación..." required>
                        <option value="">Seleccione una evaluación</option>
                        @foreach($evaluaciones as $evaluacion)
                            <option value="{{ $evaluacion->id_evaluacion }}" data-nivel="{{ $evaluacion->nivel_riesgo }}" {{ (string) old('fk_id_evaluacion') === (string) $evaluacion->id_evaluacion ? 'selected' : '' }}>#{{ $evaluacion->id_evaluacion }} - {{ $evaluacion->activo->nombre_equipo ?? 'Activo' }} - {{ $evaluacion->nivel_riesgo }}</option>
                        @endforeach
                    </select>
                @endif

                {{-- FASE 1: la sugerencia vive en una tarjeta propia, no una línea suelta --}}
                <div class="hidden mt-2 flex items-start gap-2 bg-blue-50 border border-blue-100 rounded-xl p-3 text-xs text-blue-800" id="cajaSugerencia">
                    <span>💡</span>
                    <span>Sugerencia del sistema para este nivel de riesgo: <strong id="sugerenciaTexto"></strong> — puedes aceptarla o cambiarla; quedará registrada como "lo sugerido" para el historial.</span>
                </div>
            </div>
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Estrategia (ISO 31000) <span class="text-rose-500">*</span></label>
                    <span class="hidden text-[10px] font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-2 py-0.5" id="labelEstrategiaModificada">
                        modificado respecto a la sugerencia
                    </span>
                </div>
                <select name="estrategia" id="selectEstrategiaTratamiento" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" required>
                    <option value="">-- Seleccionar estrategia --</option>
                    @foreach($estrategias as $estrategia)
                        <option value="{{ $estrategia }}" {{ old('estrategia', $tratamientoParaRevisar->estrategia ?? '') == $estrategia ? 'selected' : '' }}>{{ $estrategia }}</option>
                    @endforeach
                </select>
                {{-- FASE 2: aviso en vivo de la acción automática al guardar --}}
                <p class="text-[11px] text-slate-500 mt-1.5" id="avisoAccionAutomatica"></p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Estado del Tratamiento <span class="text-rose-500">*</span></label>
                <select name="estado_tratamiento" id="selectEstadoTratamientoForm" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" required>
                    @foreach($estados as $estado)
                        <option value="{{ $estado }}" {{ old('estado_tratamiento', $tratamientoParaRevisar->estado_tratamiento ?? 'Pendiente') == $estado ? 'selected' : '' }}>{{ $estado }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Acción a Realizar <span class="text-rose-500">*</span></label>
                    <button type="button" class="hidden text-[11px] font-semibold text-blue-700 hover:text-blue-900 underline" id="linkRestablecerSugerencia">
                        Restablecer sugerencia
                    </button>
                </div>
                <textarea name="accion_preventiva" id="textareaAccionPreventiva" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" required>{{ old('accion_preventiva', $tratamientoParaRevisar->accion_preventiva ?? '') }}</textarea>
            </div>
            <div class="md:col-span-2 hidden" id="campoObservacionCierreForm">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">¿Qué se hizo realmente? <span class="text-rose-500">*</span></label>
                <textarea name="observacion_cierre" id="observacionCierreFormInput" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition">{{ old('observacion_cierre', $tratamientoParaRevisar->observacion_cierre ?? '') }}</textarea>
                <p class="text-[11px] text-slate-500 mt-1.5">Esta estrategia no genera un Mantenimiento, así que esta es la única constancia de qué se hizo realmente.</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Fecha de Compromiso</label>
                <input name="fecha_compromiso" type="date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" value="{{ old('fecha_compromiso', $tratamientoParaRevisar->fecha_compromiso ?? '') }}" />
                {{-- FASE 5 --}}
                <p class="text-[11px] text-slate-500 mt-1.5">Si se deja vacío, el mantenimiento (si aplica) se programará automáticamente 7 días después de guardar.</p>
            </div>
            <div>
                {{-- FASE 3: solo lectura, informativo --}}
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Responsable</label>
                <div class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-700">
                    {{ trim((auth()->user()->nombre ?? '') . ' ' . (auth()->user()->apellido ?? '')) ?: 'Usuario actual' }}
                </div>
                <p class="text-[11px] text-slate-500 mt-1.5">Reasignable después desde el módulo de Mantenimiento.</p>
            </div>
            <div class="md:col-span-2 flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="toggleFormTratamiento()" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    {{ $modoRevision ? 'Confirmar Tratamiento' : 'Guardar Tratamiento' }}
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
                                @php
                                    // Sin columna nueva: si nunca se guardó desde el
                                    // formulario de revisión, updated_at == created_at
                                    // (ver ProcesoRiesgoController::tratamientoStore()).
                                    $esSugeridoSinRevisar = $tratamiento->origen === 'automatico'
                                        && $tratamiento->estado_tratamiento === 'Pendiente'
                                        && $tratamiento->created_at->eq($tratamiento->updated_at);
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $tratamiento->estado_tratamiento === 'Completado' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : ($tratamiento->estado_tratamiento === 'En Proceso' ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-slate-100 text-slate-700 border border-slate-200') }}">
                                    {{ $esSugeridoSinRevisar ? 'Sugerido — sin revisar' : $tratamiento->estado_tratamiento }}
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
                                    <div class="flex items-center justify-center gap-1.5">
                                        @if($esSugeridoSinRevisar)
                                            <a href="{{ route('admin.riesgos.tratamiento') }}?crear={{ $tratamiento->fk_id_evaluacion }}#formTratamientoCard" class="btn btn-xs btn-danger">
                                                Revisar sugerencia
                                            </a>
                                        @endif
                                        <button type="button"
                                                onclick="openEstadoModal({{ $tratamiento->id_tratamiento }}, '{{ $tratamiento->estado_tratamiento }}', '{{ $tratamiento->estrategia }}')"
                                                class="btn btn-xs btn-dark">
                                            Actualizar estado
                                        </button>
                                    </div>
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

                <div class="hidden" id="campoObservacionCierre">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">¿Qué se hizo realmente? <span class="text-rose-500">*</span></label>
                    <textarea name="observacion_cierre" id="observacion_cierre_input" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" placeholder="Ej. Se transfirió el riesgo mediante póliza de seguro contratada el 10/09..."></textarea>
                    <p class="text-[11px] text-slate-500 mt-1.5">
                        Esta estrategia no genera un Mantenimiento, así que esta es la única constancia de qué se hizo realmente.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-5 mt-4 border-t border-slate-100">
                <button type="button" onclick="closeEstadoModal()" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
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

    @if($errors->any() || old('fk_id_evaluacion') || $modoRevision)
        (function autoAbrirFormulario() {
            var card = document.getElementById('formTratamientoCard');
            if (card && card.classList.contains('hidden')) {
                toggleFormTratamiento();
            }
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        })();
    @endif

    var ESTRATEGIAS_SIN_MANTENIMIENTO = @json(\App\Support\Catalogo::estrategiasSinMantenimiento());

    function openEstadoModal(id, estadoActual, estrategia) {
        var form = document.getElementById('estadoForm');
        form.action = '/admin/riesgos/plan-tratamiento/' + id;
        form.dataset.estrategia = estrategia;

        var select = document.getElementById('estado_tratamiento_input');
        select.value = estadoActual;
        document.getElementById('observacion_cierre_input').value = '';
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
        var form = document.getElementById('estadoForm');
        var campoObs = document.getElementById('campoObservacionCierre');
        var inputObs = document.getElementById('observacion_cierre_input');

        var esCompletado = select.value === 'Completado';
        var estrategia = form.dataset.estrategia || '';
        var requiereObservacion = esCompletado && ESTRATEGIAS_SIN_MANTENIMIENTO.indexOf(estrategia) !== -1;

        aviso.classList.toggle('hidden', !esCompletado);
        campoObs.classList.toggle('hidden', !requiereObservacion);
        inputObs.required = requiereObservacion;
    }

    (function initPlanTratamiento() {
        var select = document.getElementById('estado_tratamiento_input');
        if (!select) return;
        select.removeEventListener('change', toggleAvisoCompletado);
        select.addEventListener('change', toggleAvisoCompletado);
    })();

    // ===== Mismo toggle de "¿Qué se hizo realmente?" pero en el formulario
    // grande de Registrar/Revisar (aquí la estrategia SÍ es editable) =====
    (function () {
        function toggleObservacionCierreForm() {
            var estadoSel = document.getElementById('selectEstadoTratamientoForm');
            var estrategiaSel = document.getElementById('selectEstrategiaTratamiento');
            var campo = document.getElementById('campoObservacionCierreForm');
            var input = document.getElementById('observacionCierreFormInput');
            if (!estadoSel || !estrategiaSel || !campo || !input) return;

            var requiere = estadoSel.value === 'Completado' && ESTRATEGIAS_SIN_MANTENIMIENTO.indexOf(estrategiaSel.value) !== -1;
            campo.classList.toggle('hidden', !requiere);
            input.required = requiere;
        }

        function init() {
            var estadoSel = document.getElementById('selectEstadoTratamientoForm');
            var estrategiaSel = document.getElementById('selectEstrategiaTratamiento');
            if (!estadoSel || !estrategiaSel) return;

            estadoSel.removeEventListener('change', toggleObservacionCierreForm);
            estadoSel.addEventListener('change', toggleObservacionCierreForm);
            estrategiaSel.removeEventListener('change', toggleObservacionCierreForm);
            estrategiaSel.addEventListener('change', toggleObservacionCierreForm);

            toggleObservacionCierreForm();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
        document.addEventListener('turbo:load', init);
    })();

    // ===== Sugerencia: mostrarla/autocompletar, detectar diferencias y avisar
    // qué se disparará al guardar (Fases 1 y 2) =====
    (function () {
        var MAPA_SUGERIDO = @json($mapaSugerido);
        // En modo revisión la sugerencia ya quedó congelada en el registro
        // (no depende de ningún select); en modo creación se recalcula cada
        // vez que cambia la evaluación elegida.
        var sugerenciaActual = @if($modoRevision && $tratamientoParaRevisar->estrategia_sugerida) @json(['estrategia' => $tratamientoParaRevisar->estrategia_sugerida, 'accion_preventiva' => $tratamientoParaRevisar->accion_sugerida]) @else null @endif;

        var TEXTO_MITIGAR = 'Al guardar, se generará automáticamente una orden de mantenimiento programada vinculada a esta evaluación.';
        var TEXTO_SIN_MANTENIMIENTO = 'Esta estrategia no genera una orden de mantenimiento; el cierre del tratamiento se registrará manualmente.';

        function actualizarCajaSugerencia() {
            var select = document.getElementById('selectEvaluacionTratamiento');
            var caja = document.getElementById('cajaSugerencia');
            var texto = document.getElementById('sugerenciaTexto');
            if (!select || !caja) return;

            var opcion = select.options[select.selectedIndex];
            var nivel = opcion ? opcion.getAttribute('data-nivel') : null;
            var sugerencia = nivel ? MAPA_SUGERIDO[nivel] : null;

            sugerenciaActual = sugerencia || null;

            if (!sugerencia) {
                caja.classList.add('hidden');
                actualizarIndicadoresDiferencia();
                return;
            }

            caja.classList.remove('hidden');
            texto.textContent = sugerencia.estrategia + ' — ' + sugerencia.accion_preventiva;

            var selectEstrategia = document.getElementById('selectEstrategiaTratamiento');
            var textareaAccion = document.getElementById('textareaAccionPreventiva');

            // Se autocompleta salvo que el usuario ya haya editado el campo a
            // mano (marcado por 'autofilled' !== 'true'): así, cambiar de
            // evaluación reemplaza la sugerencia anterior, pero nunca pisa
            // algo que el usuario escribió él mismo.
            if (!selectEstrategia.value || selectEstrategia.dataset.autofilled === 'true') {
                selectEstrategia.value = sugerencia.estrategia;
                selectEstrategia.dataset.autofilled = 'true';
            }
            if (!textareaAccion.value || textareaAccion.dataset.autofilled === 'true') {
                textareaAccion.value = sugerencia.accion_preventiva;
                textareaAccion.dataset.autofilled = 'true';
            }

            actualizarIndicadoresDiferencia();
            actualizarAvisoAccionAutomatica();
        }

        // FASE 1: "Restablecer sugerencia" (acción) y "modificado respecto a
        // la sugerencia" (estrategia) — puramente informativos/de UI, no
        // envían nada al servidor por sí solos.
        function actualizarIndicadoresDiferencia() {
            var linkRestablecer = document.getElementById('linkRestablecerSugerencia');
            var labelModificado = document.getElementById('labelEstrategiaModificada');
            var textareaAccion = document.getElementById('textareaAccionPreventiva');
            var selectEstrategia = document.getElementById('selectEstrategiaTratamiento');
            if (!linkRestablecer || !labelModificado || !textareaAccion || !selectEstrategia) return;

            if (!sugerenciaActual) {
                linkRestablecer.classList.add('hidden');
                labelModificado.classList.add('hidden');
                return;
            }

            var accionDifiere = textareaAccion.value.trim() !== (sugerenciaActual.accion_preventiva || '').trim();
            linkRestablecer.classList.toggle('hidden', !accionDifiere);

            var estrategiaDifiere = selectEstrategia.value !== sugerenciaActual.estrategia;
            labelModificado.classList.toggle('hidden', !estrategiaDifiere);
        }

        // FASE 2: qué va a pasar al guardar, según la estrategia elegida.
        function actualizarAvisoAccionAutomatica() {
            var selectEstrategia = document.getElementById('selectEstrategiaTratamiento');
            var aviso = document.getElementById('avisoAccionAutomatica');
            if (!selectEstrategia || !aviso) return;

            if (selectEstrategia.value === 'Mitigar') {
                aviso.textContent = TEXTO_MITIGAR;
            } else if (selectEstrategia.value) {
                aviso.textContent = TEXTO_SIN_MANTENIMIENTO;
            } else {
                aviso.textContent = '';
            }
        }

        function restablecerSugerencia() {
            if (!sugerenciaActual) return;
            var textareaAccion = document.getElementById('textareaAccionPreventiva');
            textareaAccion.value = sugerenciaActual.accion_preventiva;
            textareaAccion.dataset.autofilled = 'true';
            actualizarIndicadoresDiferencia();
        }

        function marcarComoEditadoManualmente(e) {
            e.target.dataset.autofilled = 'false';
            actualizarIndicadoresDiferencia();
        }

        function init() {
            var select = document.getElementById('selectEvaluacionTratamiento');
            var selectEstrategia = document.getElementById('selectEstrategiaTratamiento');
            var textareaAccion = document.getElementById('textareaAccionPreventiva');
            var linkRestablecer = document.getElementById('linkRestablecerSugerencia');

            if (select) {
                select.removeEventListener('change', actualizarCajaSugerencia);
                select.addEventListener('change', actualizarCajaSugerencia);
            }

            [selectEstrategia, textareaAccion].forEach(function (campo) {
                if (!campo) return;
                campo.removeEventListener('input', marcarComoEditadoManualmente);
                campo.addEventListener('input', marcarComoEditadoManualmente);
            });

            if (selectEstrategia) {
                // El <select> de Estrategia se cambia con el mouse (evento
                // 'change'), no escribiendo texto ('input'): se necesitan
                // ambos listeners para que la etiqueta "modificado respecto a
                // la sugerencia" y el aviso de Fase 2 reaccionen siempre.
                selectEstrategia.removeEventListener('change', marcarComoEditadoManualmente);
                selectEstrategia.addEventListener('change', marcarComoEditadoManualmente);
                selectEstrategia.removeEventListener('change', actualizarAvisoAccionAutomatica);
                selectEstrategia.addEventListener('change', actualizarAvisoAccionAutomatica);
            }
            if (linkRestablecer) {
                linkRestablecer.removeEventListener('click', restablecerSugerencia);
                linkRestablecer.addEventListener('click', restablecerSugerencia);
            }

            if (select) {
                actualizarCajaSugerencia();
            } else {
                actualizarIndicadoresDiferencia();
            }
            actualizarAvisoAccionAutomatica();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
        document.addEventListener('turbo:load', init);
    })();
</script>
@endsection
