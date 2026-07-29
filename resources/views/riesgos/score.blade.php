@extends('layouts.principal')

@section('title', 'Configuración de Score AHP - Gestión de Riesgos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition shadow-sm" aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Score de Riesgo AHP</h2>
                <p class="text-xs text-slate-500">Configura los pesos del Proceso de Jerarquía Analítica (AHP) para la evaluación FMEA</p>
            </div>
        </div>

        <button onclick="toggleRegistroForm()" id="btnToggleForm" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition shadow-md shadow-blue-200 self-start sm:self-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Actualizar Pesos AHP
        </button>
    </div>

    {{-- Alerta de éxito --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 flex items-center gap-3 shadow-sm transition text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Errores de validación --}}
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm text-xs space-y-1">
            <div class="font-bold">Error al guardar los pesos:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- TARJETA PRINCIPAL: Visualización de Pesos Actuales (Último Cambio) --}}
    @php
        $actual = $pesos->first();
        $pSev = (float)($actual->peso_severidad ?? 0.648);
        $pFrec = (float)($actual->peso_frecuencia ?? 0.230);
        $pMsr = (float)($actual->peso_msr ?? 0.122);
        $sumaTotal = round($pSev + $pFrec + $pMsr, 3);
    @endphp

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h3 class="text-base font-bold text-slate-900">Configuración de Pesos AHP Vigente</h3>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Ponderaciones activas aplicadas en el cálculo automático del Score FMEA</p>
            </div>
            
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-slate-600">Fecha del Cambio:</span>
                <span class="font-mono text-xs font-bold bg-slate-100 text-slate-800 px-3 py-1 rounded-full border border-slate-200">
                    {{ $actual ? \Carbon\Carbon::parse($actual->fecha_actualizacion)->format('d/m/Y H:i') . ' hs' : 'Valores por Defecto' }}
                </span>
            </div>
        </div>

        {{-- Grid 3 Tarjetas de Indicadores (KPIs) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

            {{-- 1. Peso Severidad --}}
            <div class="bg-gradient-to-br from-rose-50/70 to-white border border-rose-200/80 rounded-2xl p-5 space-y-3 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-rose-700">1. Peso Severidad</span>
                    <span class="text-xs font-mono font-bold bg-rose-100 text-rose-800 px-2 py-0.5 rounded-full">
                        {{ number_format($pSev * 100, 1) }}%
                    </span>
                </div>
                <div>
                    <div class="text-2xl font-bold font-mono text-slate-900">{{ number_format($pSev, 3) }}</div>
                    <p class="text-[11px] text-slate-500 mt-1 leading-tight">Ponderación asignada al impacto directo de la falla en la operación o seguridad del activo.</p>
                </div>
                <div class="w-full bg-rose-100 h-2 rounded-full overflow-hidden">
                    <div class="bg-rose-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, $pSev * 100) }}%"></div>
                </div>
            </div>

            {{-- 2. Peso Frecuencia --}}
            <div class="bg-gradient-to-br from-amber-50/70 to-white border border-amber-200/80 rounded-2xl p-5 space-y-3 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-amber-700">2. Peso Frecuencia</span>
                    <span class="text-xs font-mono font-bold bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">
                        {{ number_format($pFrec * 100, 1) }}%
                    </span>
                </div>
                <div>
                    <div class="text-2xl font-bold font-mono text-slate-900">{{ number_format($pFrec, 3) }}</div>
                    <p class="text-[11px] text-slate-500 mt-1 leading-tight">Ponderación asignada a la probabilidad estimada de recurrencia del modo de falla.</p>
                </div>
                <div class="w-full bg-amber-100 h-2 rounded-full overflow-hidden">
                    <div class="bg-amber-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, $pFrec * 100) }}%"></div>
                </div>
            </div>

            {{-- 3. Peso MSR --}}
            <div class="bg-gradient-to-br from-blue-50/70 to-white border border-blue-200/80 rounded-2xl p-5 space-y-3 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-700">3. Peso MSR (Controles)</span>
                    <span class="text-xs font-mono font-bold bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">
                        {{ number_format($pMsr * 100, 1) }}%
                    </span>
                </div>
                <div>
                    <div class="text-2xl font-bold font-mono text-slate-900">{{ number_format($pMsr, 3) }}</div>
                    <p class="text-[11px] text-slate-500 mt-1 leading-tight">Ponderación para Monitoreo y Sistemas de Respuesta (capacidad de detección/mitigación).</p>
                </div>
                <div class="w-full bg-blue-100 h-2 rounded-full overflow-hidden">
                    <div class="bg-blue-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, $pMsr * 100) }}%"></div>
                </div>
            </div>

        </div>

        {{-- Banner de Ecuación Matemático --}}
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
            <div class="flex items-center gap-2">
                <span class="font-bold text-slate-700">Fórmula de Cálculo:</span>
                <code class="bg-white border border-slate-200 px-2.5 py-1 rounded-md text-slate-800 font-mono font-semibold">
                    Score = (Severidad × {{ $pSev }}) + (Frecuencia × {{ $pFrec }}) + (MSR × {{ $pMsr }})
                </code>
            </div>
            <div class="flex items-center gap-2 self-end sm:self-auto">
                <span class="text-slate-500">Suma Total de Pesos:</span>
                <span class="font-mono font-bold px-2.5 py-0.5 rounded-full border {{ abs($sumaTotal - 1.0) < 0.01 ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : 'bg-rose-100 text-rose-800 border-rose-200' }}">
                    {{ number_format($sumaTotal, 3) }} ({{ number_format($sumaTotal * 100, 1) }}%)
                </span>
            </div>
        </div>
    </div>

    {{-- Formulario de Registro de Nuevo Cambio (colapsable) --}}
    <div id="registroForm" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden transition-all duration-300" style="display: none;">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
            <h3 class="text-base font-bold text-slate-900">Actualizar Configuración de Pesos AHP</h3>
            <p class="text-xs text-slate-500 mt-0.5">La suma de los tres pesos debe ser igual a 1.000 (100%)</p>
        </div>

        <form method="POST" action="{{ route('admin.riesgos.score.store') }}" class="p-6 space-y-5" id="formPesos">
            @csrf
            
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Peso Severidad <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           step="0.001" 
                           min="0" 
                           max="1" 
                           id="input_peso_severidad"
                           name="peso_severidad" 
                           value="{{ old('peso_severidad', $pSev) }}" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition font-mono font-bold" 
                           required />
                    <span class="text-[11px] text-slate-400 mt-1 block">Ej: 0.648</span>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Peso Frecuencia <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           step="0.001" 
                           min="0" 
                           max="1" 
                           id="input_peso_frecuencia"
                           name="peso_frecuencia" 
                           value="{{ old('peso_frecuencia', $pFrec) }}" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition font-mono font-bold" 
                           required />
                    <span class="text-[11px] text-slate-400 mt-1 block">Ej: 0.230</span>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Peso MSR <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" 
                           step="0.001" 
                           min="0" 
                           max="1" 
                           id="input_peso_msr"
                           name="peso_msr" 
                           value="{{ old('peso_msr', $pMsr) }}" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition font-mono font-bold" 
                           required />
                    <span class="text-[11px] text-slate-400 mt-1 block">Ej: 0.122</span>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                        Fecha y Hora del Cambio <span class="text-rose-500">*</span>
                    </label>
                    <input type="datetime-local" 
                           name="fecha_actualizacion" 
                           value="{{ old('fecha_actualizacion', date('Y-m-d\TH:i')) }}" 
                           class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition font-mono" 
                           required />
                </div>

                <div class="flex items-center justify-end">
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 w-full text-right">
                        <span class="text-xs text-slate-500 block">Suma Calculada:</span>
                        <span id="sumaPreview" class="text-sm font-mono font-bold text-emerald-700">1.000 (100%)</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="toggleRegistroForm()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl text-xs transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition shadow-sm flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Guardar Nueva Configuración
                </button>
            </div>
        </form>
    </div>

    {{-- Tabla de Historial de Configuración de Pesos --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden space-y-4">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Historial de Cambios de Pesos AHP</h3>
            <span class="text-xs text-slate-500">Total de registros: {{ count($pesos) }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-16 px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha de Cambio</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Severidad (P_S)</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Frecuencia (P_F)</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">MSR (P_MSR)</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Suma Total</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($pesos as $index => $peso)
                        @php
                            $sum = (float)$peso->peso_severidad + (float)$peso->peso_frecuencia + (float)$peso->peso_msr;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="w-16 px-6 py-4 text-center text-sm text-slate-700 font-mono">{{ $peso->id_config }}</td>
                            <td class="px-6 py-4 text-center text-sm font-mono font-semibold text-slate-800 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($peso->fecha_actualizacion)->format('d/m/Y H:i') }} hs
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-mono font-bold text-rose-700">
                                {{ number_format($peso->peso_severidad, 3) }}
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-mono font-bold text-amber-700">
                                {{ number_format($peso->peso_frecuencia, 3) }}
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-mono font-bold text-blue-700">
                                {{ number_format($peso->peso_msr, 3) }}
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-mono text-slate-800">
                                {{ number_format($sum, 3) }}
                            </td>
                            <td class="px-6 py-4 text-center text-sm whitespace-nowrap">
                                @if($index === 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Vigente / Actual
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                        Histórico
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500 text-sm">
                                No hay registros de configuración de pesos todavía. Se están utilizando los valores por defecto (0.648 / 0.230 / 0.122).
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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

    function calcSum() {
        var s = parseFloat(document.getElementById('input_peso_severidad').value) || 0;
        var f = parseFloat(document.getElementById('input_peso_frecuencia').value) || 0;
        var m = parseFloat(document.getElementById('input_peso_msr').value) || 0;
        var total = (s + f + m).toFixed(3);
        var preview = document.getElementById('sumaPreview');

        var pct = (total * 100).toFixed(1);
        preview.textContent = total + ' (' + pct + '%)';

        if (Math.abs(total - 1.0) < 0.005) {
            preview.className = 'text-sm font-mono font-bold text-emerald-700';
        } else {
            preview.className = 'text-sm font-mono font-bold text-rose-600 animate-pulse';
        }
    }

    document.getElementById('input_peso_severidad').addEventListener('input', calcSum);
    document.getElementById('input_peso_frecuencia').addEventListener('input', calcSum);
    document.getElementById('input_peso_msr').addEventListener('input', calcSum);

    document.addEventListener('DOMContentLoaded', function() {
        calcSum();
        @if($errors->any())
            toggleRegistroForm();
        @endif
    });
</script>
@endsection
