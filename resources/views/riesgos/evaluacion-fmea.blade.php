@extends('layouts.principal')

@section('title', 'Evaluación FMEA - Motor de Inferencia ISO 31000')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Encabezado Principal --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Evaluación FMEA </h2>
                <p class="text-xs text-slate-500">Cálculo automatizado de Severidad, Frecuencia dinámicas y MSR según estándares FMEA-MSR y pesos AHP</p>
            </div>
        </div>

        <div class="flex items-center gap-2 self-start sm:self-auto">
            <form method="POST" action="{{ route('admin.riesgos.evaluar.todos') }}">
                @csrf
                <button type="submit" onclick="return confirm('¿Desea ejecutar la inferencia automatizada sobre TODOS los activos del inventario?')" class="btn btn-success">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Evaluar Todos los Activos
                </button>
            </form>

            <!-- <button onclick="toggleManualForm()" id="btnToggleManual" class="btn btn-dark">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Evaluación Manual
            </button> -->
        </div>
    </div>

    {{-- Alerta de Advertencia (ej. activo sin modos de falla) --}}
    @if(session('warning'))
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 flex items-center gap-3 shadow-sm transition text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <span class="font-medium">{{ session('warning') }}</span>
        </div>
    @endif

    {{-- RESUMEN DE EVALUACIÓN MASIVA ("Evaluar Todos los Activos") --}}
    @if(session('resumenLote'))
        @php $lote = session('resumenLote'); @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-5">
            <div class="border-b border-slate-100 pb-4">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-emerald-100 text-emerald-700 border border-emerald-200">Evaluación Masiva Completada</span>
                <h3 class="text-lg font-bold text-slate-900 mt-1">Resumen de "Evaluar Todos los Activos"</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ $lote['evaluados'] }} de {{ $lote['total_activos'] }} activos evaluados bajo ISO 31000 / FMEA-MSR</p>
            </div>

            {{-- KPIs --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="bg-slate-50 rounded-xl p-3.5 text-center border border-slate-200">
                    <span class="text-[10px] font-bold uppercase text-slate-400 block">Evaluados</span>
                    <span class="text-xl font-bold font-mono text-slate-900">{{ $lote['evaluados'] }}</span>
                </div>
                <div class="bg-rose-50 rounded-xl p-3.5 text-center border border-rose-100">
                    <span class="text-[10px] font-bold uppercase text-rose-500 block">Crítico</span>
                    <span class="text-xl font-bold font-mono text-rose-700">{{ $lote['conteo_por_criticidad']['Crítica'] ?? 0 }}</span>
                </div>
                <div class="bg-orange-50 rounded-xl p-3.5 text-center border border-orange-100">
                    <span class="text-[10px] font-bold uppercase text-orange-500 block">Alto</span>
                    <span class="text-xl font-bold font-mono text-orange-700">{{ $lote['conteo_por_criticidad']['Alta'] ?? 0 }}</span>
                </div>
                <div class="bg-amber-50 rounded-xl p-3.5 text-center border border-amber-100">
                    <span class="text-[10px] font-bold uppercase text-amber-500 block">Medio</span>
                    <span class="text-xl font-bold font-mono text-amber-700">{{ $lote['conteo_por_criticidad']['Media'] ?? 0 }}</span>
                </div>
                <div class="bg-emerald-50 rounded-xl p-3.5 text-center border border-emerald-100">
                    <span class="text-[10px] font-bold uppercase text-emerald-500 block">Bajo</span>
                    <span class="text-xl font-bold font-mono text-emerald-700">{{ $lote['conteo_por_criticidad']['Baja'] ?? 0 }}</span>
                </div>
                <div class="bg-blue-50 rounded-xl p-3.5 text-center border border-blue-100">
                    <span class="text-[10px] font-bold uppercase text-blue-500 block">Alertas / Tratamientos</span>
                    <span class="text-sm font-bold font-mono text-blue-700 mt-1 block">{{ $lote['alertas_generadas'] }} / {{ $lote['tratamientos_generados'] }}</span>
                </div>
            </div>

            @if($lote['sin_modos_falla'] > 0)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-amber-800 text-xs">
                    {{ $lote['sin_modos_falla'] }} {{ $lote['sin_modos_falla'] === 1 ? 'activo no se evaluó' : 'activos no se evaluaron' }} por no tener modos de falla definidos en su categoría.
                </div>
            @endif

            {{-- Detalle por activo --}}
            <div class="overflow-x-auto rounded-xl border border-slate-200 max-h-96 overflow-y-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-500 sticky top-0">
                        <tr>
                            <th class="px-4 py-2.5">Activo</th>
                            <th class="px-4 py-2.5 text-center">Score Máx</th>
                            <th class="px-4 py-2.5 text-center">Resultado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($lote['detalle'] as $item)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-2.5 font-semibold text-slate-800">
                                    {{ $item['nombre_equipo'] }}
                                    <span class="block text-[10px] font-mono text-slate-400 font-normal">{{ $item['codigo_patrimonial'] }}</span>
                                </td>
                                @if($item['sin_modos_falla'])
                                    <td class="px-4 py-2.5 text-center text-slate-400 italic" colspan="2">Sin modos de falla definidos</td>
                                @else
                                    <td class="px-4 py-2.5 text-center font-mono font-bold text-slate-800">{{ number_format($item['max_score'], 2) }}</td>
                                    <td class="px-4 py-2.5 text-center">
                                        @if($item['alerta'] === 'Roja')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200">🔴 Crítico</span>
                                        @elseif($item['alerta'] === 'Naranja')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-orange-100 text-orange-800 border border-orange-200">🟠 Alto</span>
                                        @elseif($item['alerta'] === 'Amarilla')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">🟡 Medio</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">🟢 Bajo</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- DESGLOSE DE EJECUCIÓN DEL MOTOR (Fases 1 a 5 + Matriz 10x10 + Detalle) --}}
    @php
        $res = session('resultadoMotor') ?? ($resultadoInferencia ?? null);
    @endphp

    @if($res && !($res['sin_modos_falla'] ?? false))
        @php
            $evaluacionesInferencia = $res['evaluaciones'] ?? [];
            $primerModo = !empty($evaluacionesInferencia) ? $evaluacionesInferencia[0] : null;
            $pesosAhp = $res['pesos_ahp'] ?? [
                'peso_severidad' => 0.648,
                'peso_frecuencia' => 0.230,
                'peso_msr' => 0.122,
                'peso_riesgo_tecnico' => 0.750,
                'peso_dependencia' => 0.250,
            ];
        @endphp

        <div class="bg-white rounded-2xl shadow-sm p-6 text-slate-900 space-y-6 border border-slate-200" id="seccion-resultado-inferencia">

            {{-- 1. Encabezado de Inferencia del Activo --}}
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-100 pb-5">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-blue-100 text-blue-800 border border-blue-200">
                            Modelo de Inferencia FMEA-MSR
                        </span>
                        <span class="text-xs text-slate-400 font-mono">ISO 31000 & AHP Jerárquico</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-900 mt-1">Resultado de Inferencia: {{ $res['activo']->nombre_equipo }}</h3>
                    <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-slate-500 font-mono">
                        <span class="font-semibold text-slate-700">{{ $res['activo']->codigo_patrimonial }}</span>
                        <span>•</span>
                        <span>Categoría: <strong class="text-slate-800 font-sans">{{ $res['activo']->categoria->nombre_categoria ?? 'Sin Categoría' }}</strong></span>
                        <span>•</span>
                        <span>Dependencia Institucional: <strong class="text-slate-800 font-sans">{{ $res['dependencia_nivel'] ?? 'Media' }} (D = {{ $res['dependencia_valor'] ?? 5 }})</strong></span>
                    </div>

                    @if($res['evaluacion_parcial'] ?? false)
                        @php $conservados = $res['modos_conservados']; @endphp
                        <div class="mt-2 text-xs text-blue-800 bg-blue-50 border border-blue-200 rounded-xl px-3 py-2 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>
                                Evaluación parcial: se recalcularon <strong>{{ count(array_filter($evaluacionesInferencia, fn($e) => $e['recalculado'] ?? false)) }}</strong> modos.
                                @if($conservados === 1)
                                    El modo restante <strong>conserva su última evaluación vigente</strong>
                                @else
                                    Los <strong>{{ $conservados }}</strong> modos restantes <strong>conservan su última evaluación vigente</strong>
                                @endif
                                (identificados en la tabla con badge gris y fecha de registro).
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Score Global Máximo del Activo (Agregación) --}}
                <div class="flex items-center gap-4 bg-slate-50 px-5 py-3.5 rounded-2xl border border-slate-200 shrink-0">
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Score Global Máximo</span>
                        <div class="flex items-baseline justify-end gap-1">
                            <span class="text-3xl font-black font-mono text-slate-900">{{ number_format($res['max_score'], 2) }}</span>
                            <span class="text-xs text-slate-400 font-mono">/ 10.00</span>
                        </div>
                        <span class="text-[10px] text-slate-500 block">Nivel: {{ $res['criticidad_global'] ?? 'Moderada' }}</span>
                    </div>
                    <div class="pl-3 border-l border-slate-200">
                        @if($res['alerta_global'] === 'Roja' || ($res['criticidad_global'] ?? '') === 'Crítica' || ($res['max_score'] >= 8.0))
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300 shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-rose-600 animate-ping"></span>
                                🔴 Crítico (Alerta Roja)
                            </span>
                        @elseif($res['alerta_global'] === 'Naranja' || ($res['criticidad_global'] ?? '') === 'Alta' || ($res['max_score'] >= 6.0))
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-orange-100 text-orange-800 border border-orange-300 shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-orange-600"></span>
                                🟠 Alto (Alerta Naranja)
                            </span>
                        @elseif($res['alerta_global'] === 'Amarilla' || ($res['criticidad_global'] ?? '') === 'Media' || ($res['max_score'] >= 3.0))
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-amber-600"></span>
                                🟡 Medio (Alerta Amarilla)
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm">
                                <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                                🟢 Bajo (Alerta Verde)
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 2. SECUENCIA DE LAS 5 FASES DEL MODELO MATEMÁTICO --}}
            <div class="space-y-3 pt-2">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h4 class="text-sm font-bold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-white text-[10px]">1-5</span>
                        Secuencia de Evaluación de Riesgos (Modelo FMEA-MSR y AHP)
                    </h4>
                    <span class="text-xs text-slate-500">Valores del modo: <strong id="indicador-modo-activo" class="text-blue-700">{{ $primerModo['modo_falla'] ?? '-' }}</strong></span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3.5">

                    {{-- FASE 1: Datos del Activo --}}
                    <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-2xs flex flex-col justify-between space-y-2.5">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">FASE 1</span>
                                <span class="text-[10px] text-slate-400 font-mono">Entradas</span>
                            </div>
                            <div>
                                <h5 class="font-bold text-slate-900 text-xs">Datos del Activo</h5>
                                <p class="text-[11px] text-slate-500 leading-tight">Datos base del activo para la evaluación.</p>
                            </div>
                            <div class="space-y-1 text-xs pt-1">
                                <div class="flex justify-between items-center py-0.5 border-b border-slate-100">
                                    <span class="text-slate-500 text-[11px]">Antigüedad:</span>
                                    <strong class="font-mono text-slate-800 text-[11px]">{{ $res['antiguedad_meses'] }} meses</strong>
                                </div>
                                <div class="flex justify-between items-center py-0.5 border-b border-slate-100">
                                    <span class="text-slate-500 text-[11px]">Vida útil est.:</span>
                                    <strong class="font-mono text-slate-800 text-[11px]">{{ $res['vida_util_meses'] }} meses</strong>
                                </div>
                                <div class="flex justify-between items-center py-0.5 border-b border-slate-100">
                                    <span class="text-slate-500 text-[11px]">% Desgaste:</span>
                                    <strong class="font-mono text-amber-600 font-bold text-[11px]">{{ $res['porcentaje_desgaste'] }}%</strong>
                                </div>
                                <div class="flex justify-between items-center py-0.5 border-b border-slate-100">
                                    <span class="text-slate-500 text-[11px]">Incidentes:</span>
                                    <strong class="font-mono text-slate-800 text-[11px]">{{ $res['incidentes_relevantes'] ?? $res['total_incidentes'] }}</strong>
                                </div>
                                <div class="flex justify-between items-center py-0.5">
                                    <span class="text-slate-500 text-[11px]">Dependencia (D):</span>
                                    <strong class="font-mono text-blue-700 text-[11px]">{{ $res['dependencia_nivel'] ?? 'Media' }} (D={{ $res['dependencia_valor'] ?? 5 }})</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- FASE 2: Variables FMEA-MSR --}}
                    <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-2xs flex flex-col justify-between space-y-2.5">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">FASE 2</span>
                                <span class="text-[10px] text-slate-400 font-mono">Inferencia</span>
                            </div>
                            <div>
                                <h5 class="font-bold text-slate-900 text-xs">Variables FMEA-MSR</h5>
                                <p class="text-[11px] text-slate-500 leading-tight">Variables técnicas obtenidas para el modo de falla.</p>
                            </div>
                            <div class="space-y-1.5 pt-1">
                                <div class="flex justify-between items-center p-1.5 rounded-lg bg-slate-50 border border-slate-100 text-xs">
                                    <span class="text-slate-600 font-medium text-[11px]">Severidad (S):</span>
                                    <div class="flex items-center gap-1.5">
                                        <span id="fase2-severidad-val" class="font-mono font-black text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200 text-xs">{{ $primerModo['severidad'] ?? 5 }}</span>
                                        <span id="fase2-severidad-etiqueta" class="text-[10px] font-semibold text-rose-700">{{ $primerModo['etiqueta_severidad'] ?? 'Moderada' }}</span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center p-1.5 rounded-lg bg-slate-50 border border-slate-100 text-xs">
                                    <span class="text-slate-600 font-medium text-[11px]">Frecuencia (F):</span>
                                    <div class="flex items-center gap-1.5">
                                        <span id="fase2-frecuencia-val" class="font-mono font-black text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200 text-xs">{{ $primerModo['frecuencia'] ?? 2 }}</span>
                                        <span id="fase2-frecuencia-etiqueta" class="text-[10px] font-semibold text-amber-700">{{ $primerModo['etiqueta_frecuencia'] ?? 'Baja' }}</span>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center p-1.5 rounded-lg bg-slate-50 border border-slate-100 text-xs">
                                    <span class="text-slate-600 font-medium text-[11px]">MSR / Control:</span>
                                    <div class="flex items-center gap-1.5">
                                        <span id="fase2-msr-val" class="font-mono font-black text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200 text-xs">{{ $primerModo['msr'] ?? 4 }}</span>
                                        <span id="fase2-msr-etiqueta" class="text-[10px] font-semibold text-blue-700">{{ $primerModo['etiqueta_msr'] ?? 'Alta' }}</span>
                                    </div>
                                </div>
                                <span id="fase2-severidad-criterio" class="hidden"></span>
                                <span id="fase2-frecuencia-criterio" class="hidden"></span>
                                <span id="fase2-msr-criterio" class="hidden"></span>
                            </div>
                        </div>
                    </div>

                    {{-- FASE 3: Riesgo Preliminar --}}
                    <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-2xs flex flex-col justify-between space-y-2.5">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">FASE 3</span>
                                <span class="text-[10px] text-slate-400 font-mono">Preliminar</span>
                            </div>
                            <div>
                                <h5 class="font-bold text-slate-900 text-xs">Riesgo Preliminar</h5>
                                <p class="text-[11px] text-slate-500 leading-tight">Resultado preliminar de la matriz F × S.</p>
                            </div>
                            <div class="space-y-1.5 pt-1 text-xs">
                                <div class="flex items-center justify-between bg-slate-50 px-2 py-1 rounded-md border border-slate-100 text-[11px]">
                                    <span class="text-slate-500">Fórmula:</span>
                                    <strong class="font-mono text-slate-800 font-bold">R = F × S</strong>
                                </div>
                                <div class="flex items-center justify-between text-[11px] px-1 text-slate-600 font-mono">
                                    <span>F: <strong id="fase3-frecuencia-val" class="text-amber-700">{{ $primerModo['frecuencia'] ?? 2 }}</strong></span>
                                    <span class="text-slate-300">×</span>
                                    <span>S: <strong id="fase3-severidad-val" class="text-rose-700">{{ $primerModo['severidad'] ?? 5 }}</strong></span>
                                </div>
                                <div class="bg-amber-50/80 rounded-lg p-2 border border-amber-200 text-center">
                                    <span class="text-[9px] text-amber-800 uppercase font-bold block">Índice Preliminar (R)</span>
                                    <div class="flex items-baseline justify-center gap-1 mt-0.5">
                                        <span id="fase3-operacion-texto" class="text-xs font-mono text-slate-500">{{ $primerModo['frecuencia'] ?? 2 }} × {{ $primerModo['severidad'] ?? 5 }} =</span>
                                        <span id="fase3-indice-val" class="text-xl font-black font-mono text-amber-900">{{ ($primerModo['frecuencia'] ?? 2) * ($primerModo['severidad'] ?? 5) }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">/ 100</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="#seccion-matriz-10x10" class="block text-center text-[10px] font-bold text-amber-800 bg-amber-100/70 hover:bg-amber-200 py-1 px-2 rounded-lg transition border border-amber-200">
                            Ver en Matriz 10 × 10 ↓
                        </a>
                    </div>

                    {{-- FASE 4: Scoring Ponderado AHP --}}
                    <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-2xs flex flex-col justify-between space-y-2.5">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">FASE 4</span>
                                <span class="text-[10px] text-slate-400 font-mono">AHP Saaty</span>
                            </div>
                            <div>
                                <h5 class="font-bold text-slate-900 text-xs">Scoring Ponderado AHP</h5>
                                <p class="text-[11px] text-slate-500 leading-tight">Integra variables FMEA-MSR y dependencia institucional.</p>
                            </div>
                            <div class="space-y-2 pt-1 text-xs">
                                <div class="bg-emerald-50/60 rounded-lg p-2 border border-emerald-200 flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] font-bold text-emerald-900 block">Subscore Técnico</span>
                                        <span id="fase4-formula-subscore" class="text-[9px] font-mono text-slate-500 block truncate max-w-[125px]">64.8% S + 23.0% F + 12.2% M</span>
                                    </div>
                                    <span id="fase4-subscore-val" class="font-mono font-black text-emerald-700 text-xs bg-white px-2 py-0.5 rounded border border-emerald-300">
                                        {{ number_format($primerModo['subscore_tecnico'] ?? 4.0, 3) }}
                                    </span>
                                </div>
                                <div class="bg-blue-50/60 rounded-lg p-2 border border-blue-200 flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] font-bold text-blue-900 block">Score Final</span>
                                        <span id="fase4-formula-final" class="text-[9px] font-mono text-slate-500 block truncate max-w-[125px]">75% Técnico + 25% D</span>
                                    </div>
                                    <span id="fase4-final-val" class="font-mono font-black text-blue-700 text-sm bg-white px-2 py-0.5 rounded border border-blue-300">
                                        {{ number_format($primerModo['score'] ?? 4.0, 2) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- FASE 5: Clasificación y Alertas --}}
                    <div class="bg-white rounded-xl p-3.5 border border-slate-200 shadow-2xs flex flex-col justify-between space-y-2.5">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">FASE 5</span>
                                <span class="text-[10px] text-slate-400 font-mono">Decisión</span>
                            </div>
                            <div>
                                <h5 class="font-bold text-slate-900 text-xs">Clasificación & Alertas</h5>
                                <p class="text-[11px] text-slate-500 leading-tight">Clasificación final y acción sugerida.</p>
                            </div>
                            <div class="space-y-1.5 pt-1 text-center">
                                <div id="fase5-nivel-badge" class="inline-flex items-center justify-center w-full">
                                    @if(($primerModo['score'] ?? 0) >= 8.0)
                                        <span class="bg-rose-100 text-rose-800 border border-rose-300 px-2.5 py-0.5 rounded-full text-xs font-black">🔴 Crítico (Roja)</span>
                                    @elseif(($primerModo['score'] ?? 0) >= 6.0)
                                        <span class="bg-orange-100 text-orange-800 border border-orange-300 px-2.5 py-0.5 rounded-full text-xs font-bold">🟠 Alto (Naranja)</span>
                                    @elseif(($primerModo['score'] ?? 0) >= 3.0)
                                        <span class="bg-amber-100 text-amber-800 border border-amber-300 px-2.5 py-0.5 rounded-full text-xs font-bold">🟡 Medio (Amarilla)</span>
                                    @else
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-0.5 rounded-full text-xs font-bold">🟢 Bajo (Verde)</span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-slate-500 font-mono">
                                    Score: <strong id="fase5-score-final" class="text-slate-900 font-bold">{{ number_format($primerModo['score'] ?? 0, 2) }}</strong> / 10.00
                                </div>
                                <div class="bg-slate-50 rounded-lg p-2 border border-slate-100 text-left space-y-0.5">
                                    <div class="text-[9px] font-bold uppercase text-slate-400">Estrategia Sugerida:</div>
                                    <div id="fase5-tratamiento-badge" class="text-[11px] font-bold text-slate-800 truncate">{{ $primerModo['estrategia'] ?? 'Monitoreo Periódico' }}</div>
                                    <p id="fase5-recomendacion-texto" class="text-[10px] text-slate-500 line-clamp-2 leading-tight">{{ $primerModo['accion_preventiva'] ?? 'Riesgo dentro de niveles tolerables. Continuar plan preventivo.' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- 3. TABLA DE EVALUACIONES POR MODO DE FALLA --}}
            <div class="space-y-3 pt-2">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h4 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            Evaluaciones por Modo de Falla
                        </h4>
                        <p class="text-xs text-slate-500">
                            Desglose de variables FMEA-MSR, Índice Preliminar (R), Subscore Técnico AHP y Score Final ponderado institucional.
                        </p>
                    </div>
                    <span class="text-xs text-slate-400 font-mono">Haga clic en una fila para enfocarlo en la matriz</span>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-slate-200 shadow-2xs">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3">Modo de Falla</th>
                                <th class="px-3 py-3 text-center" title="Severidad en escala 1 a 10">Severidad (S)</th>
                                <th class="px-3 py-3 text-center" title="Frecuencia dinámica calculada en escala 1 a 10">Frecuencia (F)</th>
                                <th class="px-3 py-3 text-center" title="Monitoreo y Respuesta en escala 1 a 10">MSR (Control)</th>
                                <th class="px-3 py-3 text-center bg-amber-50/50 text-amber-900" title="Índice Preliminar = F × S (1 a 100)">Preliminar (R)</th>
                                <th class="px-3 py-3 text-center bg-emerald-50/50 text-emerald-900" title="Subscore Técnico AHP = (0.648×S) + (0.230×F) + (0.122×MSR)">Subscore AHP</th>
                                <th class="px-3 py-3 text-center bg-blue-50/50 text-blue-900" title="Score Final = (0.75×Subscore) + (0.25×D)">Score Final</th>
                                <th class="px-3 py-3 text-center">Nivel & Semáforo</th>
                                <th class="px-3 py-3 text-center">Estado / Fecha</th>
                                <th class="px-3 py-3 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 bg-white">
                            @foreach($evaluacionesInferencia as $idx => $ev)
                                @php
                                    $subscore = $ev['subscore_tecnico'] ?? round((0.648 * $ev['severidad']) + (0.230 * $ev['frecuencia']) + (0.122 * $ev['msr']), 3);
                                    $preliminar = $ev['indice_preliminar'] ?? ($ev['frecuencia'] * $ev['severidad']);
                                @endphp
                                <tr id="fila-modo-{{ $ev['id_modo_falla'] }}"
                                    onclick="seleccionarModoFalla({{ $ev['id_modo_falla'] }})"
                                    class="fila-modo-tabla transition cursor-pointer hover:bg-blue-50/40 {{ $idx === 0 ? 'bg-blue-50/60 font-semibold' : '' }}">
                                    <td class="px-4 py-3 font-semibold text-slate-900">
                                         {{ $ev['modo_falla'] }}
                                        @if(!empty($ev['criterio_severidad']))
                                            <span class="block text-[10px] text-slate-400 font-normal truncate max-w-xs">{{ $ev['criterio_severidad'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center font-mono font-bold text-rose-600">
                                        {{ $ev['severidad'] }}
                                        <span class="block text-[9px] text-slate-400 font-sans font-normal">{{ $ev['etiqueta_severidad'] ?? '' }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center font-mono font-bold text-amber-600">
                                        {{ $ev['frecuencia'] }}
                                        <span class="block text-[9px] text-slate-400 font-sans font-normal">{{ $ev['etiqueta_frecuencia'] ?? '' }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center font-mono font-bold text-blue-600">
                                        {{ $ev['msr'] }}
                                        <span class="block text-[9px] text-slate-400 font-sans font-normal">{{ $ev['etiqueta_msr'] ?? '' }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center font-mono font-black text-amber-900 bg-amber-50/40">
                                        {{ $preliminar }}
                                        <span class="block text-[9px] text-slate-400 font-sans font-normal">{{ $ev['frecuencia'] }} × {{ $ev['severidad'] }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center font-mono font-bold text-emerald-800 bg-emerald-50/40">
                                        {{ number_format($subscore, 3) }}
                                    </td>
                                    <td class="px-3 py-3 text-center font-mono font-black text-blue-900 bg-blue-50/40 text-sm">
                                        {{ number_format($ev['score'], 2) }}
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        @if(($ev['score'] ?? 0) >= 8.0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">🔴 Crítico</span>
                                        @elseif(($ev['score'] ?? 0) >= 6.0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-orange-100 text-orange-800 border border-orange-200">🟠 Alto</span>
                                        @elseif(($ev['score'] ?? 0) >= 3.0)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">🟡 Medio</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">🟢 Bajo</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        @if($ev['recalculado'] ?? true)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                Recalculado ahora
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                                Vigente anterior
                                            </span>
                                        @endif
                                        <span class="block text-[9px] font-mono text-slate-400 mt-0.5">{{ $ev['fecha_evaluacion'] ?? '' }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <button type="button"
                                                onclick="seleccionarModoFalla({{ $ev['id_modo_falla'] }}); event.stopPropagation();"
                                                class="btn btn-outline text-[11px] py-1 px-2.5 rounded-lg hover:bg-blue-600 hover:text-white transition">
                                            Enfocar
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 4. MATRIZ DE RIESGOS FRECUENCIA VS. SEVERIDAD (10 × 10) --}}
            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 space-y-3" id="seccion-matriz-10x10">
                {{-- Encabezado compacto + Indicador dinámico en una sola fila --}}
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 border-b border-slate-200/80 pb-3">
                    <div>
                        <h4 class="text-base font-bold text-slate-900">Matriz de Riesgos: Frecuencia vs. Severidad</h4>
                        <p class="text-[11px] text-slate-500 mt-0.5">Producto bidimensional <strong class="text-slate-700">R = F × S</strong> (1 a 100). El riesgo más alto se concentra en la esquina superior derecha.</p>
                    </div>
                    <div class="bg-white px-3 py-2 rounded-xl border border-slate-300 shadow-xs shrink-0 flex items-center gap-3">
                        <div class="space-y-0.5">
                            <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400 block">Modo Seleccionado</span>
                            <strong id="matriz-modo-nombre" class="text-[11px] text-slate-900 block truncate max-w-[180px]">{{ $primerModo['modo_falla'] ?? '-' }}</strong>
                        </div>
                        <div class="flex items-center gap-1.5 font-mono text-xs border-l border-slate-200 pl-3">
                            <div class="text-center">
                                <span class="text-[8px] text-slate-400 uppercase block leading-none">F</span>
                                <strong id="matriz-f-val" class="text-amber-700 text-xs font-bold">{{ $primerModo['frecuencia'] ?? 2 }}</strong>
                            </div>
                            <span class="text-slate-300 text-sm">×</span>
                            <div class="text-center">
                                <span class="text-[8px] text-slate-400 uppercase block leading-none">S</span>
                                <strong id="matriz-s-val" class="text-rose-700 text-xs font-bold">{{ $primerModo['severidad'] ?? 5 }}</strong>
                            </div>
                            <span class="text-slate-300 text-sm">=</span>
                            <div class="text-center bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                <span class="text-[8px] text-amber-800 font-bold uppercase block leading-none">R</span>
                                <strong id="matriz-r-val" class="text-amber-900 text-sm font-black">{{ ($primerModo['frecuencia'] ?? 2) * ($primerModo['severidad'] ?? 5) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Selector de modo de falla (SOLO si hay más de 1 modo) --}}
                @if(count($evaluacionesInferencia) > 1)
                    <div class="flex flex-wrap items-center gap-2" id="contenedor-tabs-modos">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mr-1">Modo:</span>
                        @foreach($evaluacionesInferencia as $idx => $ev)
                            <button type="button"
                                    id="btn-modo-{{ $ev['id_modo_falla'] }}"
                                    onclick="seleccionarModoFalla({{ $ev['id_modo_falla'] }})"
                                    class="btn-modo-tab px-2.5 py-1 rounded-lg text-[11px] font-semibold border transition cursor-pointer select-none {{ $idx === 0 ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                                {{ $ev['modo_falla'] }}
                                <span class="font-mono text-[10px] ml-1 opacity-80">R={{ $ev['indice_preliminar'] ?? ($ev['frecuencia'] * $ev['severidad']) }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Cuadrícula 10 × 10 compacta --}}
                <div class="overflow-x-auto">
                    <div class="min-w-[520px] max-w-2xl mx-auto bg-white p-3 rounded-xl border border-slate-200 shadow-sm">

                        <div class="flex items-stretch gap-1.5">
                            {{-- Eje Vertical: Etiqueta Frecuencia (F) ↑ --}}
                            <div class="flex items-center justify-center select-none text-slate-600 font-bold text-[11px] tracking-wide shrink-0 pb-12" style="writing-mode: vertical-rl; transform: rotate(180deg);">
                                <span>Frecuencia (F) ↑</span>
                            </div>

                            {{-- Matriz y Eje Horizontal --}}
                            <div class="flex-1">
                                <div class="grid grid-cols-11 gap-[3px]" id="grid-matriz-10x10">
                                    {{-- Filas: Frecuencia de 10 hacia 1 (F=10 arriba, F=1 abajo → esquina inf. izq. F=1, sup. der. F=10) --}}
                                    @for($f = 10; $f >= 1; $f--)
                                        {{-- Encabezado de Fila: Frecuencia (F) --}}
                                        <div class="flex items-center justify-center py-1 bg-slate-50 rounded text-slate-600 font-mono font-bold text-[10px] border border-slate-100">
                                            {{ $f }}
                                        </div>

                                        {{-- 10 celdas por fila: Severidad (S) de 1 a 10 --}}
                                        @for($s = 1; $s <= 10; $s++)
                                            @php
                                                $r = $f * $s;
                                                if ($r <= 12) {
                                                    $claseColor = 'bg-emerald-100/80 text-emerald-900 border-emerald-200 hover:bg-emerald-200';
                                                } elseif ($r <= 25) {
                                                    $claseColor = 'bg-lime-100/90 text-lime-900 border-lime-200 hover:bg-lime-200';
                                                } elseif ($r <= 45) {
                                                    $claseColor = 'bg-amber-100 text-amber-950 border-amber-200 hover:bg-amber-200';
                                                } elseif ($r <= 65) {
                                                    $claseColor = 'bg-orange-100 text-orange-950 border-orange-200 hover:bg-orange-200';
                                                } else {
                                                    $claseColor = 'bg-rose-100 text-rose-950 border-rose-200 hover:bg-rose-200';
                                                }
                                                $esActivoInicial = ($primerModo && $primerModo['frecuencia'] == $f && $primerModo['severidad'] == $s);
                                            @endphp

                                            <div id="celda-matriz-{{ $f }}-{{ $s }}"
                                                 data-f="{{ $f }}"
                                                 data-s="{{ $s }}"
                                                 data-r="{{ $r }}"
                                                 onclick="onClicCeldaMatriz({{ $f }}, {{ $s }})"
                                                 class="celda-matriz flex items-center justify-center rounded border text-[10px] font-mono font-bold transition-all duration-150 cursor-pointer select-none relative h-7 {{ $claseColor }} {{ $esActivoInicial ? 'ring-[3px] ring-slate-900 border-white shadow-lg scale-110 z-20 font-black text-xs' : '' }}"
                                                 title="F={{ $f }} | S={{ $s }} | R={{ $r }}">
                                                <span>{{ $r }}</span>
                                                <div id="marker-celda-{{ $f }}-{{ $s }}" class="marker-activo {{ $esActivoInicial ? '' : 'hidden' }} absolute -top-0.5 -right-0.5 flex h-2 w-2">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-slate-900 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-slate-900"></span>
                                                </div>
                                            </div>
                                        @endfor
                                    @endfor

                                    {{-- Esquina inferior izquierda --}}
                                    <div class="flex items-center justify-center font-bold text-[9px] text-slate-400 bg-slate-50 rounded p-0.5 border border-slate-100">
                                        F \ S
                                    </div>

                                    {{-- Encabezados de Columna: Severidad (1 a 10) en la parte inferior --}}
                                    @for($s = 1; $s <= 10; $s++)
                                        <div class="flex items-center justify-center py-1 bg-slate-50 rounded text-slate-600 font-mono font-bold text-[10px] border border-slate-100">
                                            {{ $s }}
                                        </div>
                                    @endfor

                                    {{-- Fila con etiqueta Severidad (S) → debajo de los valores 1..10 --}}
                                    <div></div>
                                    <div class="col-span-10 flex items-center justify-center py-1 text-slate-600 font-bold text-[11px] tracking-wide">
                                        <span>Severidad (S) →</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Leyenda compacta --}}
                        <div class="mt-2 pt-2 border-t border-slate-100 flex flex-wrap items-center gap-1.5 text-[10px] font-mono text-slate-500">
                            <span class="font-bold text-slate-600 text-[10px] mr-1">Leyenda:</span>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 border border-emerald-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>1–12
                            </span>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-lime-100 text-lime-800 border border-lime-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-lime-600"></span>13–25
                            </span>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-900 border border-amber-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-600"></span>26–45
                            </span>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-orange-100 text-orange-900 border border-orange-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-orange-600"></span>46–65
                            </span>
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-rose-100 text-rose-900 border border-rose-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-600"></span>66–100
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- Datos en JSON para la interactividad inmediata en el navegador --}}
        <script>
            window.datosInferenciaFmea = {
                activo: @json($res['activo']),
                dependencia_valor: {{ (int) ($res['dependencia_valor'] ?? 5) }},
                dependencia_nivel: @json($res['dependencia_nivel'] ?? 'Media'),
                pesos_ahp: @json($pesosAhp),
                evaluaciones: @json($evaluacionesInferencia)
            };
        </script>
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

        <form method="POST" action="{{ route('admin.riesgos.evaluar.activo') }}" class="space-y-4" id="formEvaluarActivo">
            @csrf
            <div class="flex flex-col sm:flex-row gap-4 items-end">
                <div class="flex-1 space-y-1">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Seleccionar Activo Tecnológico <span class="text-rose-500">*</span></label>
                    <select name="fk_id_activo" id="selectActivoEvaluar" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" data-searchable data-search-placeholder="Buscar activo del inventario..." required>
                        <option value="">-- Seleccionar Activo del Inventario --</option>
                        @foreach($activos as $activo)
                            <option value="{{ $activo->id_activo }}"
                                    data-categoria="{{ $activo->fk_id_categoria }}"
                                    data-sin-modos="{{ $activo->sin_modos_falla ? '1' : '0' }}"
                                    {{ old('fk_id_activo') == $activo->id_activo ? 'selected' : '' }}>
                                [{{ $activo->codigo_patrimonial }}] {{ $activo->nombre_equipo }} — Categoría: {{ $activo->categoria->nombre_categoria ?? 'Sin Cat.' }} (Antigüedad: {{ $activo->antiguedad_meses }}m, Desgaste: {{ $activo->porcentaje_desgaste }}%){{ $activo->sin_modos_falla ? ' — ⚠ Sin modos de falla definidos' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <button type="submit" class="btn btn-primary" id="btnEjecutarEvaluacion">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Ejecutar Evaluación
                    </button>
                    <a href="#" id="btnConsultarInferencia" class="btn btn-outline" title="Ver inferencia actual sin recalcular">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Ver Inferencia
                    </a>
                </div>
            </div>

            {{-- Selección granular de modos de falla --}}
            <div id="bloqueModosEvaluar" class="border-t border-slate-100 pt-4 space-y-3 hidden">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Modos de falla a evaluar</label>

                {{-- El oculto define el valor cuando el checkbox va desmarcado:
                     sin él, el navegador no enviaría el campo y el request lo
                     interpretaría como "todos". --}}
                <input type="hidden" name="modo_evaluacion" value="especificos">
                <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" id="chkTodosModos" name="modo_evaluacion" value="todos" checked
                           class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm font-semibold text-slate-800">
                        Todos los modos de falla de la categoría
                        <span id="conteoModosCategoria" class="font-mono text-xs text-slate-500"></span>
                    </span>
                </label>

                {{-- Lista filtrada en cliente por la categoría del activo, con el
                     mismo patrón data-categoria/hidden que ya usa esta vista. --}}
                <div id="listaModosEvaluar" class="grid grid-cols-1 sm:grid-cols-2 gap-2 pl-6 opacity-50 pointer-events-none transition">
                    @foreach($modos as $modo)
                        <label class="opcion-modo inline-flex items-start gap-2 cursor-pointer select-none rounded-xl border border-slate-200 bg-slate-50 px-3 py-2"
                               data-categoria="{{ $modo->fk_id_categoria }}">
                            <input type="checkbox" name="modos_falla_ids[]" value="{{ $modo->id_modo_falla }}"
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-2 focus:ring-blue-500">
                            <span class="text-xs text-slate-700 leading-snug">{{ $modo->nombre_modo_falla }}</span>
                        </label>
                    @endforeach
                </div>

                <p id="avisoSinModosEvaluar" class="hidden text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
                    ⚠ La categoría de este activo no tiene modos de falla registrados. No se puede ejecutar la evaluación hasta definir al menos uno.
                </p>
            </div>
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
                    <select name="fk_id_activo" id="selectActivoManual" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" data-searchable data-search-placeholder="Buscar activo..." required>
                        <option value="">Seleccione un activo</option>
                        @foreach($activos as $activo)
                            <option value="{{ $activo->id_activo }}" data-categoria="{{ $activo->fk_id_categoria }}" data-categoria-nombre="{{ $activo->categoria->nombre_categoria ?? '' }}">{{ $activo->codigo_patrimonial }} - {{ $activo->nombre_equipo }} ({{ $activo->categoria->nombre_categoria ?? 'Sin Cat.' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Modo de Falla <span class="text-rose-500">*</span></label>
                    <select name="fk_id_modo_falla" id="selectModoManual" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" data-searchable data-search-placeholder="Buscar modo de falla..." required>
                        <option value="">Seleccione un modo</option>
                        @foreach($modos as $modo)
                            <option value="{{ $modo->id_modo_falla }}" data-categoria="{{ $modo->fk_id_categoria }}">{{ $modo->nombre_modo_falla }} ({{ $modo->categoria->nombre_categoria ?? '-' }})</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1" id="avisoFiltroModo">Seleccione primero un activo para filtrar sus modos de falla.</p>
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
                <button type="button" onclick="toggleManualForm()" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-dark">
                    Guardar Evaluación Manual
                </button>
            </div>
        </form>
    </div>

    {{-- TARJETA 3: Historial de Evaluaciones Vigentes del Sistema --}}
    @if(isset($evaluaciones) && is_object($evaluaciones) && method_exists($evaluaciones, 'count') && $evaluaciones->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-4">
            <div class="border-b border-slate-100 pb-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Historial de Evaluaciones Vigentes en Inventario</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Últimas evaluaciones vigentes calculadas bajo el motor ISO 31000 / FMEA-MSR</p>
                </div>
                <span class="text-xs font-mono text-slate-500">Mostrando {{ $evaluaciones->count() }} de {{ $evaluaciones->total() }} registros</span>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5">Activo</th>
                            <th class="px-4 py-2.5">Modo de Falla</th>
                            <th class="px-3 py-2.5 text-center">S</th>
                            <th class="px-3 py-2.5 text-center">F</th>
                            <th class="px-3 py-2.5 text-center">MSR</th>
                            <th class="px-3 py-2.5 text-center">R (F×S)</th>
                            <th class="px-3 py-2.5 text-center">Score Final</th>
                            <th class="px-4 py-2.5 text-center">Nivel</th>
                            <th class="px-4 py-2.5 text-center">Fecha</th>
                            <th class="px-4 py-2.5 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 bg-white">
                        @foreach($evaluaciones as $evItem)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3 font-semibold text-slate-900">
                                    {{ $evItem->activo->nombre_equipo ?? 'Activo no encontrado' }}
                                    <span class="block text-[10px] font-mono text-slate-400 font-normal">{{ $evItem->activo->codigo_patrimonial ?? '' }}</span>
                                </td>
                                <td class="px-4 py-3 text-slate-800">{{ $evItem->modoFalla->nombre_modo_falla ?? 'Modo eliminado' }}</td>
                                <td class="px-3 py-3 text-center font-mono font-bold text-rose-600">{{ $evItem->valor_severidad }}</td>
                                <td class="px-3 py-3 text-center font-mono font-bold text-amber-600">{{ $evItem->valor_frecuencia }}</td>
                                <td class="px-3 py-3 text-center font-mono font-bold text-blue-600">{{ $evItem->valor_msr }}</td>
                                <td class="px-3 py-3 text-center font-mono font-black text-amber-900 bg-amber-50/50">
                                    {{ $evItem->valor_frecuencia * $evItem->valor_severidad }}
                                </td>
                                <td class="px-3 py-3 text-center font-mono font-bold text-slate-900">{{ number_format($evItem->score_calculado, 2) }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($evItem->nivel_riesgo === 'Crítico')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">🔴 Crítico</span>
                                    @elseif($evItem->nivel_riesgo === 'Alto')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-orange-100 text-orange-800 border border-orange-200">🟠 Alto</span>
                                    @elseif($evItem->nivel_riesgo === 'Medio')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">🟡 Medio</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">🟢 Bajo</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-slate-500 font-mono text-[11px]">
                                    {{ \Illuminate\Support\Carbon::parse($evItem->fecha_evaluacion)->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('admin.riesgos.evaluacion', ['activo_id' => $evItem->fk_id_activo]) }}#seccion-resultado-inferencia"
                                       class="btn btn-outline text-[11px] py-1 px-2.5 rounded-lg hover:bg-blue-600 hover:text-white transition inline-flex items-center gap-1">
                                        <span>Ver Inferencia</span>
                                        <span aria-hidden="true">&rarr;</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="pt-2">
                {{ $evaluaciones->links() }}
            </div>
        </div>
    @endif

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

    // Filtra el <select> de Modo de Falla para mostrar solo los modos de la categoría del activo seleccionado
    function filtrarModosPorActivo() {
        var selectActivo = document.getElementById('selectActivoManual');
        var selectModo = document.getElementById('selectModoManual');
        var aviso = document.getElementById('avisoFiltroModo');
        if (!selectActivo || !selectModo) return;

        var opcionActivo = selectActivo.options[selectActivo.selectedIndex];
        var categoriaId = opcionActivo ? opcionActivo.getAttribute('data-categoria') : '';

        var modoSeleccionadoSigueValido = false;
        Array.prototype.forEach.call(selectModo.options, function (opt) {
            if (opt.value === '') return;
            var coincide = !categoriaId || opt.getAttribute('data-categoria') === categoriaId;
            opt.hidden = !coincide;
            if (coincide && selectModo.value === opt.value) modoSeleccionadoSigueValido = true;
        });

        if (!modoSeleccionadoSigueValido) {
            selectModo.value = '';
        }
        selectModo.dispatchEvent(new Event('change', { bubbles: true }));

        if (aviso) {
            var nombreCategoria = opcionActivo ? opcionActivo.getAttribute('data-categoria-nombre') : '';
            aviso.textContent = categoriaId && nombreCategoria
                ? 'Mostrando solo los modos de falla de la categoría "' + nombreCategoria + '".'
                : 'Seleccione primero un activo para filtrar sus modos de falla.';
        }
    }

    function initFiltroModosManual() {
        var selectActivo = document.getElementById('selectActivoManual');
        if (!selectActivo) return;

        if (selectActivo.dataset.filtroBound !== '1') {
            selectActivo.addEventListener('change', filtrarModosPorActivo);
            selectActivo.dataset.filtroBound = '1';
        }

        filtrarModosPorActivo();
    }

    // ── Selección granular de modos de falla en "Ejecutar Evaluación" ──
    function actualizarModosEvaluar() {
        var selectActivo = document.getElementById('selectActivoEvaluar');
        var bloque = document.getElementById('bloqueModosEvaluar');
        var lista = document.getElementById('listaModosEvaluar');
        var aviso = document.getElementById('avisoSinModosEvaluar');
        var btn = document.getElementById('btnEjecutarEvaluacion');
        if (!selectActivo || !bloque || !lista) return;

        var opcion = selectActivo.options[selectActivo.selectedIndex];
        var hayActivo = !!selectActivo.value;
        var categoriaId = hayActivo && opcion ? opcion.getAttribute('data-categoria') : null;
        var sinModos = hayActivo && opcion ? opcion.getAttribute('data-sin-modos') === '1' : false;

        var visibles = 0;
        Array.prototype.forEach.call(lista.querySelectorAll('.opcion-modo'), function (opt) {
            var coincide = categoriaId !== null && opt.getAttribute('data-categoria') === categoriaId;
            opt.hidden = !coincide;

            var chk = opt.querySelector('input[type="checkbox"]');
            if (chk && !coincide) {
                chk.checked = false;
                chk.disabled = true;
            }
            if (coincide) visibles++;
        });

        if (!hayActivo) {
            bloque.classList.add('hidden');
            if (btn) btn.disabled = false;
            return;
        }

        bloque.classList.remove('hidden');

        var conteo = document.getElementById('conteoModosCategoria');
        if (conteo) conteo.textContent = visibles > 0 ? '(' + visibles + ')' : '';

        var vacio = sinModos || visibles === 0;
        if (aviso) aviso.classList.toggle('hidden', !vacio);
        lista.classList.toggle('hidden', vacio);
        if (btn) btn.disabled = vacio;

        sincronizarModoTodos();
    }

    function sincronizarModoTodos() {
        var chkTodos = document.getElementById('chkTodosModos');
        var lista = document.getElementById('listaModosEvaluar');
        if (!chkTodos || !lista) return;

        var todos = chkTodos.checked;
        lista.classList.toggle('opacity-50', todos);
        lista.classList.toggle('pointer-events-none', todos);

        Array.prototype.forEach.call(lista.querySelectorAll('.opcion-modo'), function (opt) {
            var chk = opt.querySelector('input[type="checkbox"]');
            if (!chk) return;

            var esDeLaCategoria = !opt.hidden;
            var bloqueado = todos || !esDeLaCategoria;

            chk.disabled = bloqueado;
            if (bloqueado) chk.checked = false;
        });
    }

    function sincronizarBotonConsultar() {
        var sel = document.getElementById('selectActivoEvaluar');
        var btn = document.getElementById('btnConsultarInferencia');
        if (!sel || !btn) return;

        function actualizarHref() {
            var val = sel.value;
            if (val) {
                btn.href = "{{ route('admin.riesgos.evaluacion') }}?activo_id=" + encodeURIComponent(val) + "#seccion-resultado-inferencia";
                btn.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                btn.href = "#";
                btn.classList.add('opacity-50', 'pointer-events-none');
            }
        }

        sel.addEventListener('change', actualizarHref);
        actualizarHref();
    }

    function initModosEvaluar() {
        var selectActivo = document.getElementById('selectActivoEvaluar');
        var chkTodos = document.getElementById('chkTodosModos');
        if (!selectActivo) return;

        if (selectActivo.dataset.modosBound !== '1') {
            selectActivo.addEventListener('change', actualizarModosEvaluar);
            selectActivo.dataset.modosBound = '1';
        }

        if (chkTodos && chkTodos.dataset.modosBound !== '1') {
            chkTodos.addEventListener('change', sincronizarModoTodos);
            chkTodos.dataset.modosBound = '1';
        }

        actualizarModosEvaluar();
        sincronizarBotonConsultar();
    }

    // ── INTERACTIVIDAD DINÁMICA: SELECCIÓN DE MODOS DE FALLA Y MATRIZ 10x10 ──
    function seleccionarModoFalla(idModo) {
        if (!window.datosInferenciaFmea || !window.datosInferenciaFmea.evaluaciones) return;
        var evs = window.datosInferenciaFmea.evaluaciones;
        var modo = evs.find(function(e) { return Number(e.id_modo_falla) === Number(idModo); });
        if (!modo) return;

        // 1. Selector de tabs
        document.querySelectorAll('.btn-modo-tab').forEach(function(b) {
            b.classList.remove('bg-blue-600', 'text-white', 'border-blue-600', 'shadow-md', 'shadow-sm', 'ring-2', 'ring-blue-200');
            b.classList.add('bg-white', 'text-slate-700', 'border-slate-200');
        });
        var btnActivo = document.getElementById('btn-modo-' + idModo);
        if (btnActivo) {
            btnActivo.classList.remove('bg-white', 'text-slate-700', 'border-slate-200');
            btnActivo.classList.add('bg-blue-600', 'text-white', 'border-blue-600', 'shadow-sm');
        }

        // Indicador modo activo en cabecera
        var elNombre = document.getElementById('indicador-modo-activo');
        if (elNombre) elNombre.textContent = modo.modo_falla;

        // 2. FASE 2: Inferencia de variables
        var sVal = document.getElementById('fase2-severidad-val');
        var sEtq = document.getElementById('fase2-severidad-etiqueta');
        var sCrit = document.getElementById('fase2-severidad-criterio');
        if (sVal) sVal.textContent = modo.severidad;
        if (sEtq) sEtq.textContent = modo.etiqueta_severidad || '';
        if (sCrit) {
            sCrit.textContent = modo.criterio_severidad || 'Criterio Tabla 20 (ISO 31000).';
            sCrit.title = modo.criterio_severidad || '';
        }

        var fVal = document.getElementById('fase2-frecuencia-val');
        var fEtq = document.getElementById('fase2-frecuencia-etiqueta');
        var fCrit = document.getElementById('fase2-frecuencia-criterio');
        if (fVal) fVal.textContent = modo.frecuencia;
        if (fEtq) fEtq.textContent = modo.etiqueta_frecuencia || '';
        if (fCrit) {
            fCrit.textContent = modo.criterio_frecuencia || 'Calculada por desgaste e incidentes técnicos.';
            fCrit.title = modo.criterio_frecuencia || '';
        }

        var msrVal = document.getElementById('fase2-msr-val');
        var msrEtq = document.getElementById('fase2-msr-etiqueta');
        var msrCrit = document.getElementById('fase2-msr-criterio');
        if (msrVal) msrVal.textContent = modo.msr;
        if (msrEtq) msrEtq.textContent = modo.etiqueta_msr || '';
        if (msrCrit) {
            msrCrit.textContent = modo.criterio_msr || 'Criterio Tabla 22 de monitoreo y mitigación.';
            msrCrit.title = modo.criterio_msr || '';
        }

        // 3. FASE 3: Análisis preliminar R = F x S
        var f3F = document.getElementById('fase3-frecuencia-val');
        var f3S = document.getElementById('fase3-severidad-val');
        var f3Op = document.getElementById('fase3-operacion-texto');
        var f3R = document.getElementById('fase3-indice-val');
        var preliminar = modo.indice_preliminar || (modo.frecuencia * modo.severidad);
        if (f3F) f3F.textContent = modo.frecuencia;
        if (f3S) f3S.textContent = modo.severidad;
        if (f3Op) f3Op.textContent = modo.frecuencia + ' × ' + modo.severidad + ' =';
        if (f3R) f3R.textContent = preliminar;

        // 4. FASE 4: Scoring Ponderado AHP
        var pesos = window.datosInferenciaFmea.pesos_ahp || { peso_severidad: 0.648, peso_frecuencia: 0.230, peso_msr: 0.122, peso_riesgo_tecnico: 0.750, peso_dependencia: 0.250 };
        var subscore = Number(modo.subscore_tecnico || ((pesos.peso_severidad * modo.severidad) + (pesos.peso_frecuencia * modo.frecuencia) + (pesos.peso_msr * modo.msr)));
        var depVal = Number(modo.dependencia || window.datosInferenciaFmea.dependencia_valor || 5);
        var scoreFinal = Number(modo.score);

        var f4SubVal = document.getElementById('fase4-subscore-val');
        var f4SubForm = document.getElementById('fase4-formula-subscore');
        var f4FinVal = document.getElementById('fase4-final-val');
        var f4FinForm = document.getElementById('fase4-formula-final');

        if (f4SubVal) f4SubVal.textContent = subscore.toFixed(3);
        if (f4SubForm) f4SubForm.textContent = '(' + pesos.peso_severidad + '×' + modo.severidad + ') + (' + pesos.peso_frecuencia + '×' + modo.frecuencia + ') + (' + pesos.peso_msr + '×' + modo.msr + ')';
        if (f4FinVal) f4FinVal.textContent = scoreFinal.toFixed(2);
        if (f4FinForm) f4FinForm.textContent = '(' + pesos.peso_riesgo_tecnico + '×' + subscore.toFixed(3) + ') + (' + pesos.peso_dependencia + '×' + depVal + ')';

        // 5. FASE 5: Semáforo y Alertas
        var f5Score = document.getElementById('fase5-score-final');
        var f5Badge = document.getElementById('fase5-nivel-badge');
        var f5Estrat = document.getElementById('fase5-tratamiento-badge');
        var f5Rec = document.getElementById('fase5-recomendacion-texto');

        if (f5Score) f5Score.textContent = scoreFinal.toFixed(2);
        if (f5Badge) {
            if (scoreFinal >= 8.0) {
                f5Badge.innerHTML = '<span class="bg-rose-100 text-rose-800 border border-rose-300 px-3 py-1 rounded-full font-black">🔴 Crítico (Alerta Roja)</span>';
            } else if (scoreFinal >= 6.0) {
                f5Badge.innerHTML = '<span class="bg-orange-100 text-orange-800 border border-orange-300 px-3 py-1 rounded-full font-bold">🟠 Alto (Alerta Naranja)</span>';
            } else if (scoreFinal >= 3.0) {
                f5Badge.innerHTML = '<span class="bg-amber-100 text-amber-800 border border-amber-300 px-3 py-1 rounded-full font-bold">🟡 Medio (Alerta Amarilla)</span>';
            } else {
                f5Badge.innerHTML = '<span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-3 py-1 rounded-full font-bold">🟢 Bajo (Alerta Verde)</span>';
            }
        }
        if (f5Estrat) f5Estrat.textContent = modo.estrategia || 'Monitoreo Periódico';
        if (f5Rec) f5Rec.textContent = modo.accion_preventiva || 'Riesgo dentro de niveles tolerables. Continuar plan de mantenimiento preventivo.';

        // 6. Matriz 10x10
        var matModo = document.getElementById('matriz-modo-nombre');
        var matF = document.getElementById('matriz-f-val');
        var matS = document.getElementById('matriz-s-val');
        var matR = document.getElementById('matriz-r-val');
        if (matModo) matModo.textContent = modo.modo_falla;
        if (matF) matF.textContent = modo.frecuencia;
        if (matS) matS.textContent = modo.severidad;
        if (matR) matR.textContent = preliminar;

        document.querySelectorAll('.celda-matriz').forEach(function(c) {
            c.classList.remove('ring-[3px]', 'ring-4', 'ring-slate-900', 'border-white', 'shadow-lg', 'shadow-xl', 'scale-110', 'z-20', 'font-black', 'text-xs');
        });
        document.querySelectorAll('.marker-activo').forEach(function(m) { m.classList.add('hidden'); });

        var celdaTarget = document.getElementById('celda-matriz-' + modo.frecuencia + '-' + modo.severidad);
        if (celdaTarget) {
            celdaTarget.classList.add('ring-[3px]', 'ring-slate-900', 'border-white', 'shadow-lg', 'scale-110', 'z-20', 'font-black', 'text-xs');
        }
        var markerTarget = document.getElementById('marker-celda-' + modo.frecuencia + '-' + modo.severidad);
        if (markerTarget) {
            markerTarget.classList.remove('hidden');
        }

        // 7. Resaltar fila en la tabla detallada
        document.querySelectorAll('.fila-modo-tabla').forEach(function(tr) {
            tr.classList.remove('bg-blue-50/60', 'font-semibold');
        });
        var filaTarget = document.getElementById('fila-modo-' + idModo);
        if (filaTarget) {
            filaTarget.classList.add('bg-blue-50/60', 'font-semibold');
        }
    }

    function onClicCeldaMatriz(f, s) {
        if (!window.datosInferenciaFmea || !window.datosInferenciaFmea.evaluaciones) return;
        var evs = window.datosInferenciaFmea.evaluaciones;
        var modoEncontrado = evs.find(function(e) { return Number(e.frecuencia) === Number(f) && Number(e.severidad) === Number(s); });
        if (modoEncontrado) {
            seleccionarModoFalla(modoEncontrado.id_modo_falla);
        } else {
            // Mostrar coordenadas de la celda explorada
            var matModo = document.getElementById('matriz-modo-nombre');
            var matF = document.getElementById('matriz-f-val');
            var matS = document.getElementById('matriz-s-val');
            var matR = document.getElementById('matriz-r-val');
            if (matModo) matModo.textContent = 'Coordenada F=' + f + ', S=' + s + ' (Sin modo asignado en este activo)';
            if (matF) matF.textContent = f;
            if (matS) matS.textContent = s;
            if (matR) matR.textContent = f * s;

            document.querySelectorAll('.celda-matriz').forEach(function(c) {
                c.classList.remove('ring-[3px]', 'ring-4', 'ring-slate-900', 'border-white', 'shadow-lg', 'shadow-xl', 'scale-110', 'z-20', 'font-black', 'text-xs');
            });
            document.querySelectorAll('.marker-activo').forEach(function(m) { m.classList.add('hidden'); });

            var celda = document.getElementById('celda-matriz-' + f + '-' + s);
            if (celda) {
                celda.classList.add('ring-[3px]', 'ring-slate-900', 'border-white', 'shadow-lg', 'scale-110', 'z-20', 'font-black', 'text-xs');
            }
            var marker = document.getElementById('marker-celda-' + f + '-' + s);
            if (marker) {
                marker.classList.remove('hidden');
            }
        }
    }

    // Inicialización del DOM
    function initPaginaFmea() {
        initFiltroModosManual();
        initModosEvaluar();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPaginaFmea);
    } else {
        initPaginaFmea();
    }
    document.addEventListener('turbo:load', initPaginaFmea);
</script>
@endsection
