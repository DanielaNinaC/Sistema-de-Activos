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

    {{-- Alerta de Éxito --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 flex items-center gap-3 shadow-sm transition text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

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

    {{-- DESGLOSE DE EJECUCIÓN DEL MOTOR (Fases 1 a 5) --}}
    @if(session('resultadoMotor') && !(session('resultadoMotor')['sin_modos_falla'] ?? false))
        @php $res = session('resultadoMotor'); @endphp
        <div class="bg-white rounded-2xl shadow-sm p-6 text-slate-900 space-y-6 border border-slate-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-100 pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-widest bg-blue-50 text-blue-700 border border-blue-100">Motor de Inferencia FMEA-MSR</span>
                        <span class="text-xs text-slate-400 font-mono">ISO 31000</span>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 mt-1">Resultado de Inferencia: {{ $res['activo']->nombre_equipo }}</h3>
                    <p class="text-xs text-slate-500 font-mono">{{ $res['activo']->codigo_patrimonial }} • Categoría: {{ $res['activo']->categoria->nombre_categoria ?? 'Sin Categoría' }}</p>

                    @if($res['evaluacion_parcial'] ?? false)
                        @php $conservados = $res['modos_conservados']; @endphp
                        <p class="mt-2 text-xs text-blue-800 bg-blue-50 border border-blue-200 rounded-xl px-3 py-2 inline-block">
                            Evaluación parcial: se recalcularon <strong>{{ count($res['evaluaciones']) }}</strong> de
                            <strong>{{ $res['modos_categoria'] }}</strong> modos de falla.
                            @if($conservados === 1)
                                El modo restante <strong>conserva su última evaluación vigente</strong>
                            @else
                                Los <strong>{{ $conservados }}</strong> modos restantes <strong>conservan su última evaluación vigente</strong>
                            @endif
                            — no quedaron pendientes ni sin evaluar.
                        </p>
                    @endif
                </div>

                <div class="flex items-center gap-3 bg-slate-50 px-4 py-3 rounded-xl border border-slate-200">
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Score Global Max</span>
                        <span class="text-2xl font-bold font-mono text-slate-900">{{ number_format($res['max_score'], 2) }}</span>
                    </div>
                    <div>
                        @if($res['alerta_global'] === 'Roja')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">🔴 Crítico (Alerta Roja)</span>
                        @elseif($res['alerta_global'] === 'Naranja')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-800 border border-orange-200">🟠 Alto (Alerta Naranja)</span>
                        @elseif($res['alerta_global'] === 'Amarilla')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">🟡 Medio (Alerta Amarilla)</span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">🟢 Bajo (Alerta Verde)</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Grid Resumen Fases --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs">
                {{-- Fase 1 --}}
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-1.5">
                    <span class="font-bold text-blue-600 uppercase tracking-wider text-[10px] block">Fase 1: Lectura de Datos</span>
                    <div class="text-slate-600">Antigüedad: <strong class="text-slate-900 font-mono">{{ $res['antiguedad_meses'] }} meses</strong></div>
                    <div class="text-slate-600">Vida Útil: <strong class="text-slate-900 font-mono">{{ $res['vida_util_meses'] }} meses</strong></div>
                    <div class="text-slate-600">Desgaste: <strong class="text-amber-600 font-mono">{{ $res['porcentaje_desgaste'] }}%</strong></div>
                    <div class="text-slate-600">Incidentes Históricos: <strong class="text-slate-900 font-mono">{{ $res['total_incidentes'] }}</strong></div>
                </div>

                {{-- Fase 2 --}}
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-1.5">
                    <span class="font-bold text-purple-600 uppercase tracking-wider text-[10px] block">Fase 2: Matriz de Inferencia</span>
                    <p class="text-slate-600 text-[11px] leading-relaxed">Severidad (S) deducida de la Tabla 20. Frecuencia (F) calculada dinámicamente por desgaste e incidentes (Tabla 21). MSR (Controles) según Tabla 22.</p>
                </div>

                {{-- Fase 3 --}}
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-1.5">
                    <span class="font-bold text-emerald-600 uppercase tracking-wider text-[10px] block">Fase 3: Ecuación AHP</span>
                    <p class="text-slate-600 text-[11px] font-mono leading-relaxed">Score = (S × {{ $ultimoPeso->peso_severidad ?? 0.648 }}) + (F × {{ $ultimoPeso->peso_frecuencia ?? 0.230 }}) + (MSR × {{ $ultimoPeso->peso_msr ?? 0.122 }})</p>
                </div>

                {{-- Fase 4 & 5 --}}
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-1.5">
                    <span class="font-bold text-rose-600 uppercase tracking-wider text-[10px] block">Fase 4 & 5: Clasificación y Criticidad</span>
                    <div class="text-slate-600">Criticidad Institucional actualizada a: <strong class="text-slate-900 font-semibold">{{ $res['criticidad_global'] }}</strong></div>
                    <div class="text-slate-500 text-[11px] mt-1">Alertas creadas en BD y priorizadas para el equipo de TI.</div>
                </div>
            </div>

            {{-- Detalle de Modos de Falla Evaluados --}}
            <div class="space-y-2">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Evaluaciones por Modo de Falla</h4>
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-4 py-2.5">Modo de Falla</th>
                                <th class="px-4 py-2.5 text-center">Severidad (S)</th>
                                <th class="px-4 py-2.5 text-center">Frecuencia Dinámica (F)</th>
                                <th class="px-4 py-2.5 text-center">MSR (Control)</th>
                                <th class="px-4 py-2.5 text-center">Score AHP</th>
                                <th class="px-4 py-2.5 text-center">Alerta / Semáforo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach($res['evaluaciones'] as $ev)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3 font-semibold text-slate-900">{{ $ev['modo_falla'] }}</td>
                                    <td class="px-4 py-3 text-center font-mono font-bold text-rose-600">{{ $ev['severidad'] }}</td>
                                    <td class="px-4 py-3 text-center font-mono font-bold text-amber-600">{{ $ev['frecuencia'] }}</td>
                                    <td class="px-4 py-3 text-center font-mono font-bold text-blue-600">{{ $ev['msr'] }}</td>
                                    <td class="px-4 py-3 text-center font-mono font-bold text-slate-900">{{ number_format($ev['score'], 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($ev['tipo_alerta'] === 'Roja')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200">🔴 Roja</span>
                                        @elseif($ev['tipo_alerta'] === 'Naranja')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-orange-100 text-orange-800 border border-orange-200">🟠 Naranja</span>
                                        @elseif($ev['tipo_alerta'] === 'Amarilla')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">🟡 Amarilla</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">🟢 Verde</span>
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

                <button type="submit" class="btn btn-primary shrink-0" id="btnEjecutarEvaluacion">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Ejecutar Evaluación
                </button>
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

    // Filtra el <select> de Modo de Falla para mostrar solo los modos de la
    // categoría del activo seleccionado (ej. si el activo es "Servidor", no
    // se pueden elegir modos de falla de "Impresora").
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFiltroModosManual);
    } else {
        initFiltroModosManual();
    }
    document.addEventListener('turbo:load', initFiltroModosManual);

    // ── Selección granular de modos de falla en "Ejecutar Evaluación" ──
    // Mismo enfoque que el filtro manual: las opciones ya vienen renderizadas y
    // se ocultan las que no son de la categoría del activo. La pertenencia se
    // vuelve a validar en el backend (EvaluarActivoRequest).
    function actualizarModosEvaluar() {
        var selectActivo = document.getElementById('selectActivoEvaluar');
        var bloque = document.getElementById('bloqueModosEvaluar');
        var lista = document.getElementById('listaModosEvaluar');
        var aviso = document.getElementById('avisoSinModosEvaluar');
        var btn = document.getElementById('btnEjecutarEvaluacion');
        if (!selectActivo || !bloque || !lista) return;

        var opcion = selectActivo.options[selectActivo.selectedIndex];
        var hayActivo = !!selectActivo.value;
        // Sin activo elegido la categoría es null y no coincide con ninguna,
        // de modo que el estado por defecto es "ningún modo aplicable".
        var categoriaId = hayActivo && opcion ? opcion.getAttribute('data-categoria') : null;
        var sinModos = hayActivo && opcion ? opcion.getAttribute('data-sin-modos') === '1' : false;

        // El filtro se aplica SIEMPRE, incluso sin activo seleccionado. Antes
        // se salía antes de este bucle y las etiquetas conservaban su estado
        // inicial del HTML —todas las categorías visibles—, así que cualquier
        // camino que mostrara el bloque sin una pasada previa del filtro
        // dejaba a la vista el catálogo completo.
        var visibles = 0;
        Array.prototype.forEach.call(lista.querySelectorAll('.opcion-modo'), function (opt) {
            var coincide = categoriaId !== null && opt.getAttribute('data-categoria') === categoriaId;
            // Se oculta con la propiedad DOM, no con la clase `hidden`: la
            // etiqueta lleva `inline-flex`, y Tailwind v4 emite las utilidades
            // de display en orden alfabético dentro de la misma capa, así que
            // `.inline-flex` va DESPUÉS de `.hidden` y le gana por orden a
            // igual especificidad. El atributo, en cambio, lo cubre el
            // preflight con `[hidden]{display:none!important}`. Es el mismo
            // patrón que ya usan el filtro manual e incidentes/registrar.
            opt.hidden = !coincide;

            var chk = opt.querySelector('input[type="checkbox"]');
            if (chk && !coincide) {
                // Desmarcar no basta: deshabilitado garantiza que un modo de
                // otra categoría no viaje en el envío bajo ninguna circunstancia.
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

        // Deja explícito cuántos modos abarca realmente "Todos".
        var conteo = document.getElementById('conteoModosCategoria');
        if (conteo) conteo.textContent = visibles > 0 ? '(' + visibles + ')' : '';

        // Estado explícito cuando la categoría no tiene catálogo definido.
        var vacio = sinModos || visibles === 0;
        if (aviso) aviso.classList.toggle('hidden', !vacio);
        lista.classList.toggle('hidden', vacio);
        if (btn) btn.disabled = vacio;

        sincronizarModoTodos();
    }

    // "Todos" desactiva la selección específica (y limpia lo marcado).
    function sincronizarModoTodos() {
        var chkTodos = document.getElementById('chkTodosModos');
        var lista = document.getElementById('listaModosEvaluar');
        if (!chkTodos || !lista) return;

        var todos = chkTodos.checked;
        lista.classList.toggle('opacity-50', todos);
        lista.classList.toggle('pointer-events-none', todos);

        // Un checkbox sólo se habilita si es de la categoría del activo (es
        // decir, si el filtro lo dejó visible) y "Todos" está desmarcado. Así
        // la re-habilitación nunca revive modos de otra categoría.
        Array.prototype.forEach.call(lista.querySelectorAll('.opcion-modo'), function (opt) {
            var chk = opt.querySelector('input[type="checkbox"]');
            if (!chk) return;

            // Misma fuente de verdad que el filtro: la propiedad `hidden`.
            // Leer la clase aquí dejaría este bucle desincronizado y "Todos"
            // volvería a abarcar modos ocultos de otra categoría.
            var esDeLaCategoria = !opt.hidden;
            var bloqueado = todos || !esDeLaCategoria;

            chk.disabled = bloqueado;
            if (bloqueado) chk.checked = false;
        });
    }

    function initModosEvaluar() {
        var selectActivo = document.getElementById('selectActivoEvaluar');
        var chkTodos = document.getElementById('chkTodosModos');
        if (!selectActivo) return;

        // El guard va en el propio elemento, no en una variable del script:
        // así se re-liga cuando turbo-nav inserta un DOM nuevo, y no se
        // duplica el listener si init vuelve a ejecutarse sobre el mismo.
        if (selectActivo.dataset.modosBound !== '1') {
            selectActivo.addEventListener('change', actualizarModosEvaluar);
            selectActivo.dataset.modosBound = '1';
        }

        if (chkTodos && chkTodos.dataset.modosBound !== '1') {
            chkTodos.addEventListener('change', sincronizarModoTodos);
            chkTodos.dataset.modosBound = '1';
        }

        // Se re-sincroniza siempre, para que un DOM restaurado desde la caché
        // de navegación no quede mostrando los modos del activo anterior.
        actualizarModosEvaluar();
    }

    // Patrón de arranque del sistema: carga normal + cada navegación SPA.
    // Sin el listener de turbo:load, al volver a esta pantalla por el menú el
    // select se quedaba sin listener y cambiar de activo no refrescaba la lista.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModosEvaluar);
    } else {
        initModosEvaluar();
    }
    document.addEventListener('turbo:load', initModosEvaluar);
</script>
@endsection
