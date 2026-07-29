@extends('layouts.principal')

@section('title', 'Detalles del Incidente #' . $incidente->id_incidente)

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Encabezado con Botón de Regreso y Acciones --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('admin.incidentes.historial') }}" 
               class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition shadow-sm" 
               title="Volver atrás">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    Detalle del Incidente <span class="font-mono text-blue-600">#{{ $incidente->id_incidente }}</span>
                </h2>
                <p class="text-xs text-slate-500">
                    Reportado el {{ \Carbon\Carbon::parse($incidente->fecha_incidente)->format('d/m/Y') }} a las {{ \Carbon\Carbon::parse($incidente->fecha_incidente)->format('H:i') }} hs
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <button type="button" 
                    onclick="openUpdateModal({{ $incidente->id_incidente }}, '{{ addslashes($incidente->estado_incidente) }}', '{{ addslashes($incidente->descripcion_falla) }}')" 
                    class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition shadow-md shadow-amber-100">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar Estado
            </button>
            <a href="{{ route('admin.incidentes.historial') }}" 
               class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-4 py-2.5 rounded-xl transition">
                Historial
            </a>
            <a href="{{ route('admin.incidentes.seguimiento') }}" 
               class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-4 py-2.5 rounded-xl transition">
                Seguimiento
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between text-sm shadow-sm">
            <div class="flex items-center gap-2">
                <span class="font-bold">✓</span> {{ session('success') }}
            </div>
        </div>
    @endif

    {{-- Banner Resumen y Línea de Tiempo de Seguimiento --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-5">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xl shadow-inner">
                    📋
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Estado de Atención Actual</span>
                    @php
                        $st = strtolower(trim($incidente->estado_incidente ?? ''));
                        $badgeStyle = match(true) {
                            str_contains($st, 'resuelto') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            str_contains($st, 'cerrado') => 'bg-slate-100 text-slate-800 border-slate-300',
                            str_contains($st, 'reportado') => 'bg-rose-100 text-rose-800 border-rose-200',
                            str_contains($st, 'proceso') => 'bg-blue-100 text-blue-800 border-blue-200',
                            str_contains($st, 'espera') => 'bg-amber-100 text-amber-800 border-amber-200',
                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                        };
                    @endphp
                    <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold border {{ $badgeStyle }} mt-1">
                        {{ $incidente->estado_incidente }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-6 text-xs text-slate-600">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-semibold">Fecha de Registro</span>
                    <span class="font-mono font-semibold text-slate-800 text-sm">{{ \Carbon\Carbon::parse($incidente->fecha_incidente)->format('d/m/Y H:i') }} hs</span>
                </div>
            </div>
        </div>

        {{-- Visual Stepper / Línea del Tiempo --}}
        <div>
            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">Línea de Seguimiento del Proceso</h4>
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                @php
                    $isReportado = str_contains($st, 'reportado');
                    $isProceso = str_contains($st, 'proceso');
                    $isEspera = str_contains($st, 'espera');
                    $isResuelto = str_contains($st, 'resuelto') || str_contains($st, 'cerrado');
                @endphp
                <div class="p-4 rounded-xl border {{ $isReportado ? 'bg-rose-50 border-rose-200 text-rose-900 shadow-sm font-bold' : 'bg-slate-50 border-slate-200 text-slate-600' }}">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs uppercase tracking-wider">1. Reportado</span>
                        <span class="text-xs">📌</span>
                    </div>
                    <p class="text-[11px] opacity-80">Incidente registrado en el sistema</p>
                </div>

                <div class="p-4 rounded-xl border {{ $isProceso ? 'bg-blue-600 border-blue-600 text-white shadow-md font-bold' : ($isEspera || $isResuelto ? 'bg-blue-50 border-blue-200 text-blue-900' : 'bg-slate-50 border-slate-200 text-slate-400') }}">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs uppercase tracking-wider">2. En Proceso</span>
                        <span class="text-xs">⚙️</span>
                    </div>
                    <p class="text-[11px] opacity-80">En atención por servicio técnico</p>
                </div>

                <div class="p-4 rounded-xl border {{ $isEspera ? 'bg-amber-500 border-amber-500 text-white shadow-md font-bold' : ($isResuelto ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-slate-50 border-slate-200 text-slate-400') }}">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs uppercase tracking-wider">3. En Espera</span>
                        <span class="text-xs">⏳</span>
                    </div>
                    <p class="text-[11px] opacity-80">Repuestos o evaluación en espera</p>
                </div>

                <div class="p-4 rounded-xl border {{ $isResuelto ? 'bg-emerald-600 border-emerald-600 text-white shadow-md font-bold' : 'bg-slate-50 border-slate-200 text-slate-400' }}">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs uppercase tracking-wider">4. Resuelto / Cerrado</span>
                        <span class="text-xs">✅</span>
                    </div>
                    <p class="text-[11px] opacity-80">Incidente solucionado y archivado</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Grid de Información Detallada --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Columna Principal: Falla y Descripción --}}
        <div class="lg:col-span-2 space-y-6">
            <!-- Card Descripción de Falla -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        📝 Descripción de la Falla y Observaciones
                    </h3>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-800 leading-relaxed whitespace-pre-wrap min-h-[120px]">
                    {{ $incidente->descripcion_falla ?? 'No se registraron detalles adicionales de la falla.' }}
                </div>
            </div>

            <!-- Card Activo Tecnológico Afectado -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        💻 Activo Tecnológico Afectado
                    </h3>
                    @if($incidente->activo)
                        <a href="{{ route('admin.activos.show', $incidente->activo->id_activo) }}" class="text-xs text-blue-600 hover:text-blue-700 font-semibold transition flex items-center gap-1">
                            Ver ficha de activo &#8594;
                        </a>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Nombre del Equipo</span>
                        <span class="font-bold text-slate-900 text-base">{{ $incidente->activo->nombre_equipo ?? '-' }}</span>
                    </div>

                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Código Patrimonial</span>
                        <span class="font-mono font-semibold text-slate-800 text-base">{{ $incidente->activo->codigo_patrimonial ?? '-' }}</span>
                    </div>

                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Marca y Modelo</span>
                        <span class="text-slate-700 font-medium">
                            {{ trim(($incidente->activo->marca ?? '') . ' ' . ($incidente->activo->modelo ?? '')) ?: 'No especificado' }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Categoría</span>
                        <span class="text-slate-700 font-medium">{{ $incidente->activo->categoria->nombre_categoria ?? '-' }}</span>
                    </div>

                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Ubicación Física</span>
                        <span class="text-slate-700 font-medium">
                            {{ $incidente->activo->ubicacion->seccion ?? '-' }} {{ !empty($incidente->activo->ubicacion->aula) ? ' ('.$incidente->activo->ubicacion->aula.')' : '' }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Estado Operativo Actual</span>
                        @php
                            $estAct = strtolower(trim($incidente->activo->estado_operativo ?? ''));
                            $estActColor = match(true) {
                                str_contains($estAct, 'operativo') && !str_contains($estAct, 'inoperativo') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                str_contains($estAct, 'mantenimiento') => 'bg-amber-100 text-amber-800 border-amber-200',
                                str_contains($estAct, 'inoperativo') || str_contains($estAct, 'baja') => 'bg-rose-100 text-rose-800 border-rose-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 mt-1 rounded-full text-xs font-bold border {{ $estActColor }}">
                            {{ $incidente->activo->estado_operativo ?? '-' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Columna Lateral: Información del Usuario & Resumen --}}
        <div class="space-y-6">

            <!-- Card Datos del Reportante -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h3 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                    👤 Usuario que Reporta
                </h3>

                <div class="space-y-3 text-sm">
                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Nombre Completo</span>
                        <span class="font-bold text-slate-900">
                            {{ $incidente->usuarioReporta ? $incidente->usuarioReporta->nombre . ' ' . ($incidente->usuarioReporta->apellido ?? '') : '-' }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Rol / Cargo</span>
                        <span class="text-slate-700 font-medium">
                            {{ $incidente->usuarioReporta->rol->nombre_rol ?? 'Sin rol asignado' }}
                        </span>
                    </div>

                    @if($incidente->usuarioReporta && $incidente->usuarioReporta->email)
                        <div>
                            <span class="block text-xs font-medium text-slate-400 uppercase tracking-wider">Correo Electrónico</span>
                            <span class="text-slate-700 text-xs font-mono">{{ $incidente->usuarioReporta->email }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Card Resumen Técnico del Caso -->
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl text-white p-6 space-y-4 shadow-md">
                <h4 class="text-sm font-bold uppercase tracking-wider border-b border-slate-700/60 pb-2 text-slate-300">
                    ℹ️ Ficha Resumen
                </h4>
                <div class="space-y-2.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-400">ID Ficha:</span>
                        <span class="font-mono font-bold text-blue-400">#INC-{{ sprintf('%04d', $incidente->id_incidente) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Estado:</span>
                        <span class="font-semibold text-amber-300">{{ $incidente->estado_incidente }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Días transcurridos:</span>
                        <span class="font-mono text-slate-200">
                            {{ \Carbon\Carbon::parse($incidente->fecha_incidente)->diffInDays(now()) }} días
                        </span>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<!-- Modal para Editar Estado del Incidente -->
<div id="updateModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 border border-slate-100 transform transition-all scale-95 opacity-0" id="updateModalCard">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Editar Estado de Incidente</h3>
            <button type="button" onclick="closeUpdateModal()" class="text-slate-400 hover:text-slate-600 transition">✕</button>
        </div>

        <form id="updateForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Estado del Incidente <span class="text-rose-500">*</span></label>
                    <select id="update_estado_incidente" name="estado_incidente" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        <option value="Reportado">Reportado</option>
                        <option value="En proceso">En proceso</option>
                        <option value="En espera">En espera</option>
                        <option value="Resuelto">Resuelto</option>
                        <option value="Cerrado">Cerrado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Descripción de la falla / Notas de actualización</label>
                    <textarea id="update_descripcion_falla" name="descripcion_falla" rows="4" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-5 mt-4 border-t border-slate-100">
                <button type="button" onclick="closeUpdateModal()" class="px-4 py-2.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openUpdateModal(id, estado, descripcion) {
        var form = document.getElementById('updateForm');
        form.action = '/admin/incidentes/' + id;

        document.getElementById('update_estado_incidente').value = estado;
        document.getElementById('update_descripcion_falla').value = descripcion;

        var modal = document.getElementById('updateModal');
        var card = document.getElementById('updateModalCard');
        modal.classList.remove('hidden');
        setTimeout(function () {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

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
