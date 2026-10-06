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
            <button type="button" 
                    onclick="openUpdateModal({{ $mantenimiento->id_mantenimiento }}, '{{ addslashes($mantenimiento->estado_mantenimiento) }}', '{{ addslashes($mantenimiento->descripcion_trabajo) }}')" 
                    class="btn btn-warning">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar Estado
            </button>
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

        {{-- Tarjeta 4: Descripción del Trabajo u Observaciones --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-4 md:col-span-2">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-700 pb-3 border-b border-slate-100">
                Descripción del Trabajo Técnico Realizado u Observaciones
            </h3>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-800 leading-relaxed whitespace-pre-line">
                {{ $mantenimiento->descripcion_trabajo ?: 'Sin observaciones o descripción detallada adicional.' }}
            </div>
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
                        Al marcar "Finalizado" este mantenimiento podrá afectar la próxima evaluación de riesgo del activo (el desgaste se recalcula desde esta fecha).
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

<script>
    function toggleAvisoFinalizado() {
        var select = document.getElementById('update_estado_mantenimiento');
        var aviso = document.getElementById('avisoFinalizado');
        if (select && aviso) aviso.classList.toggle('hidden', select.value !== 'Finalizado');
    }

    function openUpdateModal(id, estado, descripcion) {
        var form = document.getElementById('updateForm');
        form.action = '/admin/mantenimiento/' + id;

        document.getElementById('update_estado_mantenimiento').value = estado;
        document.getElementById('update_descripcion_trabajo').value = descripcion;
        toggleAvisoFinalizado();

        var modal = document.getElementById('updateModal');
        var card = document.getElementById('updateModalCard');
        modal.classList.remove('hidden');
        setTimeout(function () {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    (function initAvisoFinalizado() {
        var select = document.getElementById('update_estado_mantenimiento');
        if (!select) return;
        select.removeEventListener('change', toggleAvisoFinalizado);
        select.addEventListener('change', toggleAvisoFinalizado);
    })();

    function closeUpdateModal() {
        var modal = document.getElementById('updateModal');
        var card = document.getElementById('updateModalCard');
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(function () {
            modal.classList.add('hidden');
        }, 150);
    }
</script>
@endsection
