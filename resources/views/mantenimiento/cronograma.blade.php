@extends('layouts.principal')

@section('title', 'Cronograma de Mantenimiento')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Encabezado y Acciones --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('admin.mantenimiento.programar') }}" 
               class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition shadow-sm" 
               aria-label="Volver" 
               title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Cronograma de Mantenimiento</h2>
                <p class="text-xs text-slate-500">Calendario interactivo, alertas próximas a 3 días y organización de atenciones</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.mantenimiento.programar') }}" 
               class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition shadow-md shadow-blue-200">
                Registrar Mantenimiento
            </a>
            <a href="{{ route('admin.mantenimiento.historial') }}" 
               class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold px-4 py-2.5 rounded-xl transition">
                Ver Historial
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

    {{-- SECCIÓN UNIFICADA: Calendario Compacto (Izquierda) + Alertas Próximas (Derecha) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- COLUMNA IZQUIERDA: Calendario Mensual Compacto --}}
        @php
            $mesActual = \Carbon\Carbon::now();
            $primerDiaMes = $mesActual->copy()->firstOfMonth();
            $diasEnMes = $mesActual->daysInMonth;
            $startDayOfWeek = $primerDiaMes->dayOfWeekIso; // 1 (Lun) a 7 (Dom)
        @endphp

        <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">
                        Calendario ({{ $mesActual->translatedFormat('F Y') }})
                    </h3>
                    <p class="text-[11px] text-slate-500">Mantenimientos programados por día</p>
                </div>
                <div class="flex items-center gap-2 text-[10px]">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span> Prev</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span> Corr</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span> Prog</span>
                </div>
            </div>

            {{-- Grid Compacto del Calendario --}}
            <div class="grid grid-cols-7 gap-1 text-center text-xs">
                {{-- Encabezados Días de la Semana --}}
                <div class="py-1 font-bold text-slate-500 bg-slate-50 rounded uppercase text-[10px]">Lun</div>
                <div class="py-1 font-bold text-slate-500 bg-slate-50 rounded uppercase text-[10px]">Mar</div>
                <div class="py-1 font-bold text-slate-500 bg-slate-50 rounded uppercase text-[10px]">Mié</div>
                <div class="py-1 font-bold text-slate-500 bg-slate-50 rounded uppercase text-[10px]">Jue</div>
                <div class="py-1 font-bold text-slate-500 bg-slate-50 rounded uppercase text-[10px]">Vie</div>
                <div class="py-1 font-bold text-slate-500 bg-slate-50 rounded uppercase text-[10px]">Sáb</div>
                <div class="py-1 font-bold text-slate-500 bg-slate-50 rounded uppercase text-[10px]">Dom</div>

                {{-- Días Vacíos de Inicio --}}
                @for ($i = 1; $i < $startDayOfWeek; $i++)
                    <div class="min-h-[46px] bg-slate-50/40 rounded border border-slate-100/50"></div>
                @endfor

                {{-- Render de Días del Mes --}}
                @for ($day = 1; $day <= $diasEnMes; $day++)
                    @php
                        $fechaDia = $mesActual->copy()->day($day)->format('Y-m-d');
                        $esHoy = ($fechaDia === date('Y-m-d'));
                        
                        $mantsDia = $mantenimientos->filter(function($m) use ($fechaDia) {
                            return \Carbon\Carbon::parse($m->fecha_programada)->format('Y-m-d') === $fechaDia;
                        });
                    @endphp

                    <div class="min-h-[48px] p-1 rounded border {{ $esHoy ? 'bg-blue-50/60 border-blue-400 ring-1 ring-blue-200' : 'bg-white border-slate-200' }} flex flex-col justify-between text-left transition hover:border-slate-300">
                        <div class="flex items-center justify-between">
                            <span class="font-bold font-mono text-[10px] {{ $esHoy ? 'bg-blue-600 text-white w-4 h-4 rounded-full flex items-center justify-center text-[9px]' : 'text-slate-700' }}">
                                {{ $day }}
                            </span>
                            @if(count($mantsDia) > 0)
                                <span class="text-[9px] font-bold text-blue-700 bg-blue-100 px-1 rounded-full">
                                    {{ count($mantsDia) }}
                                </span>
                            @endif
                        </div>

                        <div class="space-y-0.5 mt-0.5 overflow-y-auto max-h-[30px]">
                            @foreach($mantsDia as $m)
                                @php
                                    $tipoLower = strtolower($m->tipo_mantenimiento);
                                    $colorPill = match(true) {
                                        str_contains($tipoLower, 'correctivo') => 'bg-rose-100 text-rose-800 border-rose-200',
                                        str_contains($tipoLower, 'preventivo') => 'bg-blue-100 text-blue-800 border-blue-200',
                                        default => 'bg-amber-100 text-amber-800 border-amber-200',
                                    };
                                @endphp
                                <button type="button" 
                                        onclick="openUpdateModal({{ $m->id_mantenimiento }}, '{{ addslashes($m->estado_mantenimiento) }}', '{{ addslashes($m->descripcion_trabajo) }}')" 
                                        class="w-full text-left truncate text-[9px] font-medium px-1 py-0.2 rounded border {{ $colorPill }} block hover:brightness-95 transition" 
                                        title="{{ $m->activo->nombre_equipo ?? 'Activo' }} - {{ $m->tipo_mantenimiento }} ({{ $m->estado_mantenimiento }})">
                                    {{ $m->activo->nombre_equipo ?? 'Equipo' }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- COLUMNA DERECHA: Alertas de Mantenimientos Próximos en 3 días --}}
        <div class="lg:col-span-5 bg-white rounded-2xl shadow-sm border border-slate-200 p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="flex h-3 w-3 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
                    </span>
                    <h3 class="text-sm font-bold text-slate-900">
                        Alertas (Próximos 3 días)
                    </h3>
                </div>
                <span class="text-[11px] font-bold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full border border-amber-200">
                    {{ count($alertasProx3Dias) }}
                </span>
            </div>

            @if(count($alertasProx3Dias) > 0)
                <div class="space-y-3 max-h-[440px] overflow-y-auto pr-1">
                    @foreach($alertasProx3Dias as $alerta)
                        @php
                            $fechaProg = \Carbon\Carbon::parse($alerta->fecha_programada)->startOfDay();
                            $hoy = \Carbon\Carbon::now()->startOfDay();
                            $diasRestantes = (int)$hoy->diffInDays($fechaProg, false);

                            if ($diasRestantes < 0) {
                                $badgeRestante = 'Vencido hace ' . abs($diasRestantes) . ' d';
                                $cardColor = 'bg-rose-50 border-rose-200 text-rose-900';
                                $pillColor = 'bg-rose-600 text-white';
                            } elseif ($diasRestantes === 0) {
                                $badgeRestante = 'Vence HOY';
                                $cardColor = 'bg-rose-50 border-rose-200 text-rose-900';
                                $pillColor = 'bg-rose-600 text-white';
                            } elseif ($diasRestantes === 1) {
                                $badgeRestante = 'Vence MAÑANA';
                                $cardColor = 'bg-amber-50 border-amber-200 text-amber-900';
                                $pillColor = 'bg-amber-500 text-white';
                            } else {
                                $badgeRestante = 'Vence en ' . $diasRestantes . ' días';
                                $cardColor = 'bg-amber-50/60 border-amber-200 text-amber-900';
                                $pillColor = 'bg-amber-400 text-slate-900 font-bold';
                            }
                        @endphp

                        <div class="rounded-xl border p-3 shadow-xs flex flex-col justify-between space-y-2 text-xs {{ $cardColor }}">
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $pillColor }}">
                                        {{ $badgeRestante }}
                                    </span>
                                    <span class="text-[11px] font-mono font-semibold">{{ \Carbon\Carbon::parse($alerta->fecha_programada)->format('d/m/Y') }}</span>
                                </div>

                                <h4 class="font-bold text-xs text-slate-900 truncate">
                                    {{ $alerta->activo->nombre_equipo ?? 'Activo' }}
                                </h4>
                                <p class="text-[11px] font-mono text-slate-500 truncate">
                                    {{ $alerta->activo->codigo_patrimonial ?? '-' }}
                                </p>

                                <div class="mt-1.5 text-[11px] space-y-0.5 text-slate-700">
                                    <div><span class="text-slate-400">Origen:</span> <strong>{{ $alerta->origenMantenimiento->nombre ?? 'Preventivo' }}</strong></div>
                                    <div><span class="text-slate-400">Tipo:</span> <strong>{{ $alerta->tipo_mantenimiento }}</strong></div>
                                    <div><span class="text-slate-400">Asignado:</span> {{ $alerta->usuarioAsignado->nombre ?? '-' }}</div>
                                </div>
                            </div>

                            <div class="pt-1.5 border-t border-slate-200/60 flex items-center justify-between">
                                <span class="text-[11px] font-semibold text-slate-700">Estado: {{ $alerta->estado_mantenimiento }}</span>
                                <button type="button" 
                                        onclick="openUpdateModal({{ $alerta->id_mantenimiento }}, '{{ addslashes($alerta->estado_mantenimiento) }}', '{{ addslashes($alerta->descripcion_trabajo) }}')" 
                                        class="text-[11px] bg-slate-900 hover:bg-slate-800 text-white px-2.5 py-0.5 rounded-md transition font-medium">
                                    Editar
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-3.5 rounded-xl text-xs flex items-center gap-2">
                    <span class="text-sm font-bold">✓</span>
                    <span>Sin mantenimientos urgentes en los próximos 3 días.</span>
                </div>
            @endif
        </div>

    </div>

</div>

<!-- Modal para Editar Estado del Mantenimiento -->
<div id="updateModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 border border-slate-100 transform transition-all scale-95 opacity-0" id="updateModalCard">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Editar Mantenimiento</h3>
            <button type="button" onclick="closeUpdateModal()" class="text-slate-400 hover:text-slate-600 transition">✕</button>
        </div>

        <form id="updateForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Estado del Mantenimiento <span class="text-rose-500">*</span></label>
                    <select id="update_estado_mantenimiento" name="estado_mantenimiento" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        <option value="Programado">Programado</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="En ejecución">En ejecución</option>
                        <option value="Finalizado">Finalizado</option>
                        <option value="Cancelado">Cancelado</option>
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
