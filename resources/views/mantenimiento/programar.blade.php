@extends('layouts.principal')

@section('title', 'Registrar Mantenimiento')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('admin.mantenimiento.cronograma') }}" 
               class="btn btn-outline btn-icon" 
               aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Registrar Mantenimiento</h2>
                <p class="text-xs text-slate-500">Programa y registra la atención técnica preventiva o correctiva de un activo</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.mantenimiento.cronograma') }}" class="btn btn-secondary">
                Ver Cronograma
            </a>
            <a href="{{ route('admin.mantenimiento.historial') }}" class="btn btn-secondary">
                Ver Historial
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center gap-2 text-sm shadow-sm">
            <span class="font-bold">✓</span> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 space-y-1 shadow-sm">
            <div class="font-semibold text-sm">Por favor corrige los siguientes errores:</div>
            <ul class="list-disc list-inside text-xs space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Banner Informativo del Origen Vinculado (si vino desde Incidente o Evaluación) --}}
    @if($preIncidenteId)
        <div class="bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-2xl flex items-center justify-between text-xs shadow-sm">
            <div class="flex items-center gap-2 font-medium">
                <span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span>
                <span>Mantenimiento autovinculado al <strong>Incidente #{{ $preIncidenteId }}</strong> (Origen: Incidente).</span>
            </div>
            <span class="font-bold text-[11px] bg-rose-200/80 px-2 py-0.5 rounded-full">Origen Automático</span>
        </div>
    @elseif($preEvaluacionId)
        <div class="bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 rounded-2xl flex items-center justify-between text-xs shadow-sm">
            <div class="flex items-center gap-2 font-medium">
                <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
                <span>Mantenimiento autovinculado a la <strong>Evaluación de Riesgo #{{ $preEvaluacionId }}</strong> (Origen: Evaluación de riesgos).</span>
            </div>
            <span class="font-bold text-[11px] bg-amber-200/80 px-2 py-0.5 rounded-full">Origen Automático</span>
        </div>
    @else
        <div class="bg-slate-50 border border-slate-200 text-slate-700 px-4 py-2.5 rounded-2xl flex items-center justify-between text-xs">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                <span>Origen detectado automáticamente: <strong>Preventivo</strong> (se actualizará a Evaluación o Incidente si selecciona uno abajo).</span>
            </div>
        </div>
    @endif

    {{-- Formulario --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sm:p-8">
        <form method="POST" action="{{ route('admin.mantenimiento.programar.store') }}" enctype="multipart/form-data" class="space-y-6" id="formMantenimiento">
            @csrf

            <div class="border-b border-slate-100 pb-4 mb-2">
                <h3 class="text-lg font-bold text-slate-900">Formulario de Registro de Mantenimiento</h3>
                <p class="text-xs text-slate-500">Complete los datos requeridos. El origen del mantenimiento se asignará automáticamente según la vinculación.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                {{-- Activo Tecnológico --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Activo Tecnológico <span class="text-rose-500">*</span>
                    </label>
                    <select name="fk_id_activo" id="selectActivo" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" data-searchable data-search-placeholder="Buscar activo..." required>
                        <option value="">-- Seleccionar Activo --</option>
                        @foreach($activos as $activo)
                            <option value="{{ $activo->id_activo }}" {{ old('fk_id_activo', $preActivoId) == $activo->id_activo ? 'selected' : '' }}>
                                {{ $activo->codigo_patrimonial ? '['.$activo->codigo_patrimonial.'] - ' : '' }}{{ $activo->nombre_equipo }} (ID: {{ $activo->id_activo }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Técnico / Usuario Asignado --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Técnico / Usuario Asignado <span class="text-rose-500">*</span>
                    </label>
                    <select name="fk_id_usuario_asignado" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        <option value="">-- Seleccionar Responsable --</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id_usuario }}" {{ old('fk_id_usuario_asignado') == $usuario->id_usuario ? 'selected' : '' }}>
                                {{ $usuario->nombre }} {{ $usuario->apellido ?? '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Vinculación opcional a Incidente o Evaluación (si no venía prellenado) --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Vincular a Incidente <span class="text-slate-400 font-normal">(Opcional)</span>
                    </label>
                    <select name="fk_id_incidente" id="selectIncidente" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" data-searchable data-search-placeholder="Buscar incidente...">
                        <option value="">-- Sin Incidente (Origen Preventivo / Evaluación) --</option>
                        @foreach($incidentes as $inc)
                            <option value="{{ $inc->id_incidente }}" 
                                    data-activo="{{ $inc->fk_id_activo }}"
                                    {{ old('fk_id_incidente', $preIncidenteId) == $inc->id_incidente ? 'selected' : '' }}>
                                Incidente #{{ $inc->id_incidente }} - {{ $inc->activo->nombre_equipo ?? 'Activo' }} ({{ $inc->estado_incidente }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Vincular a Evaluación de Riesgo FMEA <span class="text-slate-400 font-normal">(Opcional)</span>
                    </label>
                    <select name="id_evaluacion_origen" id="selectEvaluacion" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" data-searchable data-search-placeholder="Buscar evaluación...">
                        <option value="">-- Sin Evaluación FMEA --</option>
                        @foreach($evaluaciones as $evaluacion)
                            <option value="{{ $evaluacion->id_evaluacion }}" 
                                    data-activo="{{ $evaluacion->fk_id_activo }}"
                                    {{ old('id_evaluacion_origen', $preEvaluacionId) == $evaluacion->id_evaluacion ? 'selected' : '' }}>
                                Evaluación #{{ $evaluacion->id_evaluacion }} - {{ $evaluacion->activo->nombre_equipo ?? 'Activo' }} (Riesgo: {{ $evaluacion->nivel_riesgo }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tipo de Mantenimiento --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Tipo de Mantenimiento <span class="text-rose-500">*</span>
                    </label>
                    <select name="tipo_mantenimiento" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        <option value="Preventivo" {{ old('tipo_mantenimiento') == 'Preventivo' ? 'selected' : '' }}>Preventivo</option>
                        <option value="Correctivo" {{ old('tipo_mantenimiento') == 'Correctivo' ? 'selected' : '' }}>Correctivo</option>
                        <option value="Programado" {{ old('tipo_mantenimiento') == 'Programado' ? 'selected' : '' }}>Programado</option>
                        <option value="Inspección" {{ old('tipo_mantenimiento') == 'Inspección' ? 'selected' : '' }}>Inspección / Evaluación Técnica</option>
                    </select>
                </div>

                {{-- Estado --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Estado Inicial <span class="text-rose-500">*</span>
                    </label>
                    <select name="estado_mantenimiento" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        <option value="Programado" {{ old('estado_mantenimiento') == 'Programado' ? 'selected' : '' }}>Programado</option>
                        <option value="Pendiente" {{ old('estado_mantenimiento') == 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                        <option value="En ejecución" {{ old('estado_mantenimiento') == 'En ejecución' ? 'selected' : '' }}>En ejecución</option>
                        <option value="Finalizado" {{ old('estado_mantenimiento') == 'Finalizado' ? 'selected' : '' }}>Finalizado</option>
                    </select>
                </div>

                {{-- Fecha Programada --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Fecha Programada <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="fecha_programada" value="{{ old('fecha_programada', date('Y-m-d')) }}" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required />
                </div>

                {{-- Fecha de Ejecución --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Fecha Real de Ejecución <span class="text-slate-400 font-normal">(Opcional)</span>
                    </label>
                    <input type="date" name="fecha_ejecucion" value="{{ old('fecha_ejecucion') }}" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" />
                </div>

                {{-- Evidencia PDF --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Evidencia Adjunta (PDF) <span class="text-slate-400 font-normal">(Opcional)</span>
                    </label>
                    <input type="file" name="evidencia_pdf" accept="application/pdf" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs text-slate-700 focus:outline-none focus:border-blue-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition" />
                </div>

                {{-- Descripción --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Descripción del Trabajo / Observaciones
                    </label>
                    <textarea name="descripcion_trabajo" rows="4" 
                              placeholder="Detalle el alcance del mantenimiento, repuestos necesarios, tareas preventivas a ejecutar o procedimiento técnico..." 
                              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition leading-relaxed">{{ old('descripcion_trabajo') }}</textarea>
                </div>

            </div>

            {{-- Botones --}}
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                <a href="{{ route('admin.mantenimiento.cronograma') }}" 
                   class="btn btn-secondary">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    Guardar Mantenimiento
                </button>
            </div>

        </form>
    </div>

</div>

<script>
(function () {
    function initProgramarMantenimiento() {
        var selectActivo = document.getElementById('selectActivo');
        var selectEvaluacion = document.getElementById('selectEvaluacion');
        var selectIncidente = document.getElementById('selectIncidente');

        if (!selectActivo || !selectEvaluacion || !selectIncidente) return;

        function setValue(select, value) {
            select.value = value || '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Mutual exclusion: if incident is chosen, clear evaluation; if evaluation is chosen, clear incident
        selectIncidente.addEventListener('change', function() {
            var selected = this.options[this.selectedIndex];
            if (this.value) {
                setValue(selectEvaluacion, '');
                if (selected.dataset.activo) {
                    setValue(selectActivo, selected.dataset.activo);
                }
            }
        });

        selectEvaluacion.addEventListener('change', function() {
            var selected = this.options[this.selectedIndex];
            if (this.value) {
                setValue(selectIncidente, '');
                if (selected.dataset.activo) {
                    setValue(selectActivo, selected.dataset.activo);
                }
            }
        });

        // Auto-fill active on load if pre-selected
        if (selectIncidente.value) {
            var selInc = selectIncidente.options[selectIncidente.selectedIndex];
            if (selInc && selInc.dataset.activo) {
                setValue(selectActivo, selInc.dataset.activo);
            }
        } else if (selectEvaluacion.value) {
            var selEval = selectEvaluacion.options[selectEvaluacion.selectedIndex];
            if (selEval && selEval.dataset.activo) {
                setValue(selectActivo, selEval.dataset.activo);
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initProgramarMantenimiento);
    } else {
        initProgramarMantenimiento();
    }
})();
</script>
@endsection
