@extends('layouts.principal')

@section('title', 'Detalles del Mantenimiento #' . $mantenimiento->id_mantenimiento)

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Encabezado con Botón de Regreso y Acciones --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('admin.mantenimiento.historial') }}" 
               class="btn btn-outline btn-icon" 
               title="Volver atrás">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    Detalle del Mantenimiento <span class="font-mono text-blue-600">#{{ $mantenimiento->id_mantenimiento }}</span>
                </h2>
                <p class="text-xs text-slate-500">
                    Programado para el {{ \Carbon\Carbon::parse($mantenimiento->fecha_programada)->format('d/m/Y') }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            @if($mantenimiento->estaRealizado())
                <span class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Intervención Realizada ✓
                </span>
            @else
                <button type="button" 
                        onclick="openRealizarModal({{ $mantenimiento->id_mantenimiento }}, '{{ addslashes($mantenimiento->activo->nombre_equipo ?? '') }}', '{{ addslashes($mantenimiento->activo->codigo_patrimonial ?? '') }}', '{{ addslashes($mantenimiento->tipo_mantenimiento) }}', '{{ \Carbon\Carbon::parse($mantenimiento->fecha_programada)->format('d/m/Y') }}')" 
                        class="btn btn-primary bg-emerald-600 hover:bg-emerald-700 text-white flex items-center gap-1.5 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Registrar Realización
                </button>
                <button type="button" 
                        onclick="openUpdateModal({{ $mantenimiento->id_mantenimiento }}, '{{ addslashes($mantenimiento->estado_mantenimiento) }}', '{{ addslashes($mantenimiento->descripcion_trabajo) }}')" 
                        class="btn btn-secondary">
                    Editar Estado
                </button>
            @endif
            <a href="{{ route('admin.mantenimiento.historial') }}" 
               class="btn btn-secondary">
                Ver Historial
            </a>
            <a href="{{ route('admin.mantenimiento.cronograma') }}" 
               class="btn btn-secondary">
                Ver Cronograma
            </a>
        </div>
    </div>

    {{-- Banner de Estado General --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">
                    ID
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Estado del Mantenimiento</span>
                    @php
                        $st = strtolower(trim($mantenimiento->estado_mantenimiento ?? ''));
                        $badgeStyle = match(true) {
                            str_contains($st, 'finalizado') || str_contains($st, 'completado') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            str_contains($st, 'ejecución') => 'bg-blue-100 text-blue-800 border-blue-200',
                            str_contains($st, 'pendiente') => 'bg-rose-100 text-rose-800 border-rose-200',
                            default => 'bg-amber-100 text-amber-800 border-amber-200',
                        };
                    @endphp
                    <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold border {{ $badgeStyle }} mt-1">
                        {{ $mantenimiento->estado_mantenimiento }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-6 text-xs text-slate-600">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Origen</span>
                    @php
                        $origenNombre = $mantenimiento->origenMantenimiento->nombre ?? 'Preventivo';
                        $origenBadge = match($mantenimiento->fk_id_origen) {
                            2 => 'bg-amber-100 text-amber-800 border-amber-200',
                            3 => 'bg-rose-100 text-rose-800 border-rose-200',
                            default => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $origenBadge }} mt-0.5">
                        {{ $origenNombre }}
                    </span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Tipo</span>
                    <span class="font-semibold text-slate-800 text-sm">{{ $mantenimiento->tipo_mantenimiento }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Fecha Real de Ejecución</span>
                    <span class="font-mono font-semibold text-slate-800 text-sm">
                        {{ $mantenimiento->fecha_ejecucion ? \Carbon\Carbon::parse($mantenimiento->fecha_ejecucion)->format('d/m/Y') : 'Pendiente' }}
                    </span>
                </div>

                @if($mantenimiento->evidencia_pdf)
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-semibold mb-0.5">Documento Adjunto</span>
                        <a href="{{ Storage::url($mantenimiento->evidencia_pdf) }}" target="_blank" 
                           class="btn btn-xs btn-dark">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V7.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 1H7a2 2 0 00-2 2v16a2 2 0 002 2z" />
                            </svg>
                            Ver PDF
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Grilla de Tarjetas Informativas --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Tarjeta 1: Activo Tecnológico --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 pb-3 border-b border-slate-100 flex items-center justify-between">
                <span>Activo Tecnológico Atendido</span>
                <span class="text-xs text-blue-600 font-mono">ID: {{ $mantenimiento->activo->id_activo ?? '-' }}</span>
            </h3>

            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-xs text-slate-400 block font-medium">Nombre del Equipo</span>
                    <span class="font-bold text-slate-900 text-base">{{ $mantenimiento->activo->nombre_equipo ?? '-' }}</span>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                    <div>
                        <span class="text-xs text-slate-400 block font-medium">Código Patrimonial</span>
                        <span class="font-mono font-semibold text-slate-800">{{ $mantenimiento->activo->codigo_patrimonial ?? 'Sin código' }}</span>
                    </div>

                    <div>
                        <span class="text-xs text-slate-400 block font-medium">Categoría</span>
                        <span class="font-semibold text-slate-800">{{ $mantenimiento->activo->categoria->nombre_categoria ?? '-' }}</span>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <span class="text-xs text-slate-400 block font-medium">Ubicación Física</span>
                    <span class="font-semibold text-slate-800">
                        {{ $mantenimiento->activo->ubicacion->seccion ?? '-' }} 
                        {{ $mantenimiento->activo->ubicacion->aula ? '('.$mantenimiento->activo->ubicacion->aula.')' : '' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Tarjeta 2: Responsable / Técnico --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 pb-3 border-b border-slate-100 flex items-center justify-between">
                <span>Responsable de la Atención</span>
                <span class="text-xs text-slate-400">Técnico Asignado</span>
            </h3>

            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-xs text-slate-400 block font-medium">Nombre y Apellidos</span>
                    <span class="font-bold text-slate-900 text-base">
                        {{ $mantenimiento->usuarioAsignado ? $mantenimiento->usuarioAsignado->nombre . ' ' . ($mantenimiento->usuarioAsignado->apellido ?? '') : 'No asignado' }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                    <div>
                        <span class="text-xs text-slate-400 block font-medium">Correo Electrónico</span>
                        <span class="font-mono text-slate-800 text-xs">{{ $mantenimiento->usuarioAsignado->correo ?? '-' }}</span>
                    </div>

                    <div>
                        <span class="text-xs text-slate-400 block font-medium">Rol del Sistema</span>
                        <span class="font-semibold text-slate-800">{{ $mantenimiento->usuarioAsignado->rol->nombre_rol ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tarjeta 3: Contexto de Origen Vinculado --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-4 md:col-span-2">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 pb-3 border-b border-slate-100">
                Detalle del Origen y Vinculación
            </h3>

            @if($mantenimiento->fk_id_origen === 3 && $mantenimiento->incidente)
                <div class="bg-rose-50 border border-rose-200 rounded-xl p-4 space-y-2 text-rose-900">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs uppercase tracking-wider text-rose-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-rose-500 inline-block"></span>
                            Origen: Incidente Reportado #{{ $mantenimiento->incidente->id_incidente }}
                        </span>
                        <a href="{{ route('admin.incidentes.show', $mantenimiento->incidente->id_incidente) }}" class="text-xs font-bold text-rose-700 hover:underline">
                            Ver Incidente →
                        </a>
                    </div>
                    <p class="text-xs text-rose-800 leading-relaxed">
                        <strong>Descripción de la falla reportada:</strong> {{ $mantenimiento->incidente->descripcion_falla }}
                    </p>
                    <div class="text-[11px] text-rose-700 pt-1">
                        Reportado por: <strong>{{ $mantenimiento->incidente->usuarioReporta->nombre ?? 'Usuario' }}</strong> el {{ \Carbon\Carbon::parse($mantenimiento->incidente->fecha_incidente)->format('d/m/Y H:i') }} hs
                    </div>
                </div>
            @elseif($mantenimiento->fk_id_origen === 2 && $mantenimiento->evaluacionOrigen)
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 space-y-2 text-amber-900">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-xs uppercase tracking-wider text-amber-700 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
                            Origen: Evaluación de Riesgo FMEA #{{ $mantenimiento->evaluacionOrigen->id_evaluacion }}
                        </span>
                        <span class="font-bold text-xs bg-amber-200 px-2.5 py-0.5 rounded-full">Nivel de Riesgo: {{ $mantenimiento->evaluacionOrigen->nivel_riesgo }}</span>
                    </div>
                    <div class="text-xs text-amber-800">
                        Score calculado: <strong>{{ $mantenimiento->evaluacionOrigen->score_calculado }}</strong> | Evaluación realizada el {{ \Carbon\Carbon::parse($mantenimiento->evaluacionOrigen->fecha_evaluacion)->format('d/m/Y') }}
                    </div>
                </div>
            @else
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-emerald-900 space-y-1">
                    <span class="font-bold text-xs uppercase tracking-wider text-emerald-700 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                        Origen: Mantenimiento Preventivo Periódico
                    </span>
                    <p class="text-xs text-emerald-800">
                        Atención técnica planificada por rutina preventiva regular del sistema.
                    </p>
                </div>
            @endif
        </div>

        {{-- Tarjeta 4: Evidencia de la Intervención y Actividades Realizadas --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-4 md:col-span-2">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Evidencia de la Intervención Realizada
                </h3>
                @if($mantenimiento->estaRealizado())
                    <span class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
                        Intervención Cerrada
                    </span>
                @else
                    <span class="text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-full">
                        Pendiente de Ejecución
                    </span>
                @endif
            </div>

            {{-- 1. Actividades realizadas (solo si seleccionó tareas) --}}
            @if($mantenimiento->actividades->count() > 0)
                <div class="space-y-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-600 block">
                        Actividades realizadas ({{ $mantenimiento->actividades->count() }})
                    </span>
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="w-full text-left text-xs divide-y divide-slate-100">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-2.5 font-bold text-slate-600 uppercase tracking-wider text-[11px]">
                                        Actividades realizadas
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($mantenimiento->actividades as $act)
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-4 py-2.5 text-slate-800 flex items-center gap-2.5">
                                            <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center text-xs shrink-0">✓</span>
                                            <span class="font-medium">{{ $act->tarea }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- 2. Observaciones / Detalle --}}
            <div class="space-y-1.5 pt-1">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-600 block">
                    Observaciones / Detalle:
                </span>
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-xs text-slate-800 leading-relaxed whitespace-pre-line">
                    @if($mantenimiento->descripcion_trabajo)
                        {{ $mantenimiento->descripcion_trabajo }}
                    @else
                        <span class="text-slate-500 italic">Observaciones: Sin observaciones adicionales.</span>
                    @endif
                </div>
            </div>

            {{-- 3. Trazabilidad de Responsable y Fechas si ya fue realizado --}}
            @if($mantenimiento->estaRealizado())
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-slate-100 text-xs">
                    <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/70">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Fecha y Hora de Realización:</span>
                        <span class="font-mono font-bold text-slate-800">
                            {{ $mantenimiento->fecha_hora_realizacion ? $mantenimiento->fecha_hora_realizacion->format('d/m/Y H:i') . ' hs' : ($mantenimiento->fecha_ejecucion ? \Carbon\Carbon::parse($mantenimiento->fecha_ejecucion)->format('d/m/Y') : '—') }}
                        </span>
                    </div>
                    <div class="bg-slate-50/80 p-3 rounded-xl border border-slate-200/70">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Responsable de Cierre / Realización:</span>
                        <span class="font-bold text-slate-800">
                            {{ $mantenimiento->usuarioFinaliza ? $mantenimiento->usuarioFinaliza->nombre . ' ' . ($mantenimiento->usuarioFinaliza->apellido ?? '') : ($mantenimiento->usuarioAsignado ? $mantenimiento->usuarioAsignado->nombre . ' ' . ($mantenimiento->usuarioAsignado->apellido ?? '') : '—') }}
                        </span>
                    </div>
                </div>
            @endif
        </div>

    </div>

</div>

<!-- Modal para Editar Estado del Mantenimiento -->
<div id="updateModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 border border-slate-100 transform transition-all scale-95 opacity-0" id="updateModalCard">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Editar Estado de Mantenimiento</h3>
            <button type="button" onclick="closeUpdateModal()" class="text-slate-400 hover:text-slate-600 transition">✕</button>
        </div>

        <form id="updateForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Estado del Mantenimiento <span class="text-rose-500">*</span></label>
                    <select id="update_estado_mantenimiento" name="estado_mantenimiento" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        @foreach(\App\Support\Catalogo::nombres('estados_mantenimiento') as $estado)
                            <option value="{{ $estado }}">{{ $estado }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-amber-700 mt-1.5 hidden" id="avisoFinalizado">
                        Para marcar como "Realizado" o "Finalizado", el sistema abrirá la ventana de registro de evidencia con el checklist de tareas.
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Descripción / Observaciones del trabajo</label>
                    <textarea id="update_descripcion_trabajo" name="descripcion_trabajo" rows="4" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-5 mt-4 border-t border-slate-100">
                <button type="button" onclick="closeUpdateModal()" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal para Registrar Mantenimiento Realizado con Checklist --}}
@include('mantenimiento.partials.modal-realizar')

<script>
    var currentMantenimientoId = {{ $mantenimiento->id_mantenimiento }};
    var currentActivoNombre = '{{ addslashes($mantenimiento->activo->nombre_equipo ?? "") }}';
    var currentCodigoPat = '{{ addslashes($mantenimiento->activo->codigo_patrimonial ?? "") }}';
    var currentTipo = '{{ addslashes($mantenimiento->tipo_mantenimiento ?? "") }}';
    var currentFechaProg = '{{ \Carbon\Carbon::parse($mantenimiento->fecha_programada)->format("d/m/Y") }}';

    function checkEstadoSeleccionado() {
        var select = document.getElementById('update_estado_mantenimiento');
        var aviso = document.getElementById('avisoFinalizado');
        if (!select) return;

        var val = select.value;
        var esCierre = (val === 'Realizado' || val === 'Finalizado');
        if (aviso) aviso.classList.toggle('hidden', !esCierre);

        if (esCierre) {
            // Abrir directamente la ventana modal de evidencia con checklist
            closeUpdateModal();
            openRealizarModal(currentMantenimientoId, currentActivoNombre, currentCodigoPat, currentTipo, currentFechaProg);
        }
    }

    function openUpdateModal(id, estado, descripcion) {
        currentMantenimientoId = id;
        var form = document.getElementById('updateForm');
        form.action = '/admin/mantenimiento/' + id;

        document.getElementById('update_estado_mantenimiento').value = estado;
        document.getElementById('update_descripcion_trabajo').value = descripcion;

        var modal = document.getElementById('updateModal');
        var card = document.getElementById('updateModalCard');
        modal.classList.remove('hidden');
        setTimeout(function () {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    (function initEstadoListener() {
        var select = document.getElementById('update_estado_mantenimiento');
        if (!select) return;
        select.removeEventListener('change', checkEstadoSeleccionado);
        select.addEventListener('change', checkEstadoSeleccionado);
    })();

    function closeUpdateModal() {
        var modal = document.getElementById('updateModal');
        var card = document.getElementById('updateModalCard');
        if (modal && card) {
            card.classList.remove('scale-100', 'opacity-100');
            card.classList.add('scale-95', 'opacity-0');
            setTimeout(function () {
                modal.classList.add('hidden');
            }, 150);
        }
    }
</script>
@endsection
