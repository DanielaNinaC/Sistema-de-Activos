@extends('layouts.principal')

@section('title', 'Evaluación FMEA - Motor de Inferencia ISO 31000')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Encabezado Principal --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition shadow-sm" aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Evaluación FMEA (Motor de Inferencia ISO 31000)</h2>
                <p class="text-xs text-slate-500">Cálculo automatizado de Severidad, Frecuencia dinámicas y MSR según estándares FMEA-MSR y pesos AHP</p>
            </div>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            <form method="POST" action="{{ route('admin.riesgos.evaluar.todos') }}">
                @csrf
                <button type="submit" onclick="return confirm('¿Desea ejecutar la inferencia automatizada sobre TODOS los activos del inventario?')" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition shadow-md shadow-emerald-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Evaluar Todos los Activos
                </button>
            </form>

            <button onclick="toggleManualForm()" id="btnToggleManual" class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold px-4 py-2.5 rounded-xl transition shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Evaluación Manual
            </button>
        </div>
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

    {{-- DESGLOSE DE EJECUCIÓN DEL MOTOR (Fases 1 a 5) --}}
    @if(session('resultadoMotor'))
        @php $res = session('resultadoMotor'); @endphp
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-2xl shadow-xl p-6 text-white space-y-6 border border-slate-700">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-700/80 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-blue-500/20 text-blue-300 border border-blue-400/30">Motor de Inferencia FMEA-MSR</span>
                        <span class="text-xs text-slate-400 font-mono">ISO 31000</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mt-1">Resultado de Inferencia: {{ $res['activo']->nombre_equipo }}</h3>
                    <p class="text-xs text-slate-400 font-mono">{{ $res['activo']->codigo_patrimonial }} • Categoría: {{ $res['activo']->categoria->nombre_categoria ?? 'Sin Categoría' }}</p>
                </div>

                <div class="flex items-center gap-3 bg-white/5 backdrop-blur-md px-4 py-3 rounded-xl border border-white/10">
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Score Global Max</span>
                        <span class="text-2xl font-bold font-mono text-white">{{ number_format($res['max_score'], 2) }}</span>
                    </div>
                    <div>
                        @if($res['alerta_global'] === 'Roja')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-300 border border-rose-500/40">🔴 Crítico (Alerta Roja)</span>
                        @elseif($res['alerta_global'] === 'Naranja')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-orange-500/20 text-orange-300 border border-orange-500/40">🟠 Alto (Alerta Naranja)</span>
                        @elseif($res['alerta_global'] === 'Amarilla')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40">🟡 Medio (Alerta Amarilla)</span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">🟢 Bajo (Alerta Verde)</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Grid Resumen Fases --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                {{-- Fase 1 --}}
                <div class="bg-white/5 rounded-xl p-4 border border-white/10 space-y-1.5">
                    <span class="font-bold text-blue-400 uppercase tracking-wider text-[10px] block">Fase 1: Lectura de Datos</span>
                    <div class="text-slate-300">Antigüedad: <strong class="text-white font-mono">{{ $res['antiguedad_meses'] }} meses</strong></div>
                    <div class="text-slate-300">Vida Útil: <strong class="text-white font-mono">{{ $res['vida_util_meses'] }} meses</strong></div>
                    <div class="text-slate-300">Desgaste: <strong class="text-amber-400 font-mono">{{ $res['porcentaje_desgaste'] }}%</strong></div>
                    <div class="text-slate-300">Incidentes Históricos: <strong class="text-white font-mono">{{ $res['total_incidentes'] }}</strong></div>
                </div>

                {{-- Fase 2 --}}
                <div class="bg-white/5 rounded-xl p-4 border border-white/10 space-y-1.5">
                    <span class="font-bold text-purple-400 uppercase tracking-wider text-[10px] block">Fase 2: Matriz de Inferencia</span>
                    <p class="text-slate-300 text-[11px] leading-relaxed">Severidad (S) deducida de la Tabla 20. Frecuencia (F) calculada dinámicamente por desgaste e incidentes (Tabla 21). MSR (Controles) según Tabla 22.</p>
                </div>

                {{-- Fase 3 --}}
                <div class="bg-white/5 rounded-xl p-4 border border-white/10 space-y-1.5">
                    <span class="font-bold text-emerald-400 uppercase tracking-wider text-[10px] block">Fase 3: Ecuación AHP</span>
                    <p class="text-slate-300 text-[11px] font-mono leading-relaxed">Score = (S × {{ $ultimoPeso->peso_severidad ?? 0.648 }}) + (F × {{ $ultimoPeso->peso_frecuencia ?? 0.230 }}) + (MSR × {{ $ultimoPeso->peso_msr ?? 0.122 }})</p>
                </div>

                {{-- Fase 4 & 5 --}}
                <div class="bg-white/5 rounded-xl p-4 border border-white/10 space-y-1.5">
                    <span class="font-bold text-rose-400 uppercase tracking-wider text-[10px] block">Fase 4 & 5: Clasificación y Criticidad</span>
                    <div class="text-slate-300">Criticidad Institucional actualizada a: <strong class="text-white font-semibold">{{ $res['criticidad_global'] }}</strong></div>
                    <div class="text-slate-400 text-[11px] mt-1">Alertas creadas en BD y priorizadas para el equipo de TI.</div>
                </div>
            </div>

            {{-- Detalle de Modos de Falla Evaluados --}}
            <div class="space-y-2">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300">Evaluaciones por Modo de Falla</h4>
                <div class="overflow-x-auto rounded-xl border border-white/10">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-white/10 text-slate-200">
                            <tr>
                                <th class="px-4 py-2.5">Modo de Falla</th>
                                <th class="px-4 py-2.5 text-center">Severidad (S)</th>
                                <th class="px-4 py-2.5 text-center">Frecuencia Dinámica (F)</th>
                                <th class="px-4 py-2.5 text-center">MSR (Control)</th>
                                <th class="px-4 py-2.5 text-center">Score AHP</th>
                                <th class="px-4 py-2.5 text-center">Alerta / Semáforo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10 text-slate-300">
                            @foreach($res['evaluaciones'] as $ev)
                                <tr class="hover:bg-white/5 transition">
                                    <td class="px-4 py-3 font-semibold text-white">{{ $ev['modo_falla'] }}</td>
                                    <td class="px-4 py-3 text-center font-mono font-bold text-rose-300">{{ $ev['severidad'] }}</td>
                                    <td class="px-4 py-3 text-center font-mono font-bold text-amber-300">{{ $ev['frecuencia'] }}</td>
                                    <td class="px-4 py-3 text-center font-mono font-bold text-blue-300">{{ $ev['msr'] }}</td>
                                    <td class="px-4 py-3 text-center font-mono font-bold text-white">{{ number_format($ev['score'], 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($ev['tipo_alerta'] === 'Roja')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">🔴 Roja</span>
                                        @elseif($ev['tipo_alerta'] === 'Naranja')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-orange-500/20 text-orange-300 border border-orange-500/30">🟠 Naranja</span>
                                        @elseif($ev['tipo_alerta'] === 'Amarilla')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">🟡 Amarilla</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">🟢 Verde</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- TARJETA 1: Ejecutar Inferencia Automatizada en un Activo Específico --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Ejecución del Motor Automatizado por Activo</h3>
                <p class="text-xs text-slate-500 mt-0.5">Seleccione un activo tecnológico para aplicar las 5 fases de inferencia matemática FMEA-MSR</p>
            </div>
            <span class="text-xs font-mono bg-blue-50 text-blue-700 px-3 py-1 rounded-full border border-blue-100 font-semibold">
                Normativa ISO 31000
            </span>
        </div>

        <form method="POST" action="{{ route('admin.riesgos.evaluar.activo') }}" class="flex flex-col sm:flex-row gap-4 items-end">
            @csrf
            <div class="flex-1 space-y-1">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Seleccionar Activo Tecnológico <span class="text-rose-500">*</span></label>
                <select name="fk_id_activo" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                    <option value="">-- Seleccionar Activo del Inventario --</option>
                    @foreach($activos as $activo)
                        <option value="{{ $activo->id_activo }}">
                            [{{ $activo->codigo_patrimonial }}] {{ $activo->nombre_equipo }} — Categoría: {{ $activo->categoria->nombre_categoria ?? 'Sin Cat.' }} (Antigüedad: {{ $activo->antiguedad_meses }}m, Desgaste: {{ $activo->porcentaje_desgaste }}%)
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-md shadow-blue-200 flex items-center gap-2 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Ejecutar Inferencia FMEA
            </button>
        </form>
    </div>

    {{-- TARJETA 2: Formulario de Evaluación Manual (Colapsable) --}}
    <div id="manualFormCard" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden transition-all duration-300" style="display: none;">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Evaluación Manual Ad-Hoc</h3>
                <p class="text-xs text-slate-500 mt-0.5">Ingreso directo de parámetros personalizados de Severidad, Frecuencia y MSR</p>
            </div>
            <div class="text-xs text-slate-500 font-mono">
                Pesos: S {{ $ultimoPeso->peso_severidad ?? 0.648 }} | F {{ $ultimoPeso->peso_frecuencia ?? 0.230 }} | M {{ $ultimoPeso->peso_msr ?? 0.122 }}
            </div>
        </div>

        <form method="POST" action="{{ route('admin.riesgos.evaluacion.store') }}" class="p-6">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Activo <span class="text-rose-500">*</span></label>
                    <select name="fk_id_activo" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" required>
                        <option value="">Seleccione un activo</option>
                        @foreach($activos as $activo)
                            <option value="{{ $activo->id_activo }}">{{ $activo->codigo_patrimonial }} - {{ $activo->nombre_equipo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Modo de Falla <span class="text-rose-500">*</span></label>
                    <select name="fk_id_modo_falla" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" required>
                        <option value="">Seleccione un modo</option>
                        @foreach($modos as $modo)
                            <option value="{{ $modo->id_modo_falla }}">{{ $modo->nombre_modo_falla }} ({{ $modo->categoria->nombre_categoria ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Severidad (1-10) <span class="text-rose-500">*</span></label>
                    <input name="valor_severidad" type="number" min="1" max="10" value="5" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition font-mono" required />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Frecuencia (1-10) <span class="text-rose-500">*</span></label>
                    <input name="valor_frecuencia" type="number" min="1" max="10" value="4" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition font-mono" required />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">MSR / Control (1-10) <span class="text-rose-500">*</span></label>
                    <input name="valor_msr" type="number" min="1" max="10" value="6" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition font-mono" required />
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Fecha de Evaluación <span class="text-rose-500">*</span></label>
                    <input name="fecha_evaluacion" type="datetime-local" value="{{ date('Y-m-d\TH:i') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition font-mono" required />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-5 pt-4 border-t border-slate-100">
                <button type="button" onclick="toggleManualForm()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl text-xs transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-xs transition shadow-sm">
                    Guardar Evaluación Manual
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    function toggleManualForm() {
        var card = document.getElementById('manualFormCard');
        var btn = document.getElementById('btnToggleManual');
        if (card.style.display === 'none') {
            card.style.display = 'block';
            btn.classList.add('bg-slate-700');
        } else {
            card.style.display = 'none';
            btn.classList.remove('bg-slate-700');
        }
    }
</script>
@endsection
