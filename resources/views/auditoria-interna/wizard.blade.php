@extends('layouts.principal')

@section('title', 'Auditoría Interna de TI — ' . $auditoria->codigo)

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.auditoria-interna.index') }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900">{{ $auditoria->codigo }}</h2>
                <p class="text-xs text-slate-500">Gestión {{ $auditoria->gestion }} &middot; Fecha de corte {{ $auditoria->fecha_corte->format('d/m/Y') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold px-3 py-1.5 rounded-full
                {{ $auditoria->estado === 'APROBADO' ? 'bg-emerald-100 text-emerald-800' : ($auditoria->estado === 'EN_REVISION' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                {{ $auditoria->estado }}
            </span>
            <a href="{{ route('admin.auditoria-interna.pdf', $auditoria->id) }}" class="btn btn-secondary" target="_blank">Exportar PDF</a>
            <a href="{{ route('admin.auditoria-interna.excel', $auditoria->id) }}" class="btn btn-secondary">Exportar Excel</a>
        </div>
    </div>

    {{-- Pestañas --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="flex flex-wrap border-b border-slate-200" data-audi-tabs>
            @foreach(['datos' => 'Datos Generales', 'muestra' => 'Muestra', 'resultados' => 'Resultados', 'hallazgos' => 'Hallazgos', 'seguimiento' => 'Seguimiento', 'conclusiones' => 'Conclusiones y Aprobación'] as $key => $label)
                <button type="button" class="audi-tab-btn px-4 py-3 text-sm font-medium text-slate-500 hover:text-slate-800 border-b-2 border-transparent" data-audi-tab="{{ $key }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- 1. DATOS GENERALES --}}
        <div class="p-6 space-y-4" data-audi-panel="datos">
            @if($puedeEditar)
                <form method="POST" action="{{ route('admin.auditoria-interna.generar-textos', $auditoria->id) }}" class="flex items-center justify-between gap-3 bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3">
                    @csrf
                    <p class="text-xs text-indigo-800">
                        Objetivo, alcance, antecedentes, metodología, limitaciones, buenas prácticas y conclusiones se
                        generan por PLN a partir de los datos del sistema (muestra, indicadores, semáforo, hallazgos).
                        Regenerarlos sobrescribe el texto actual de estos 7 campos.
                    </p>
                    <button type="submit" class="btn btn-secondary shrink-0">Generar con PLN</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.auditoria-interna.update', $auditoria->id) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div><span class="text-xs text-slate-500 uppercase">Auditor</span><p class="font-semibold">{{ trim($auditoria->auditor->nombre . ' ' . ($auditoria->auditor->apellido ?? '')) }}</p></div>
                    <div><span class="text-xs text-slate-500 uppercase">Período</span><p class="font-semibold">{{ $auditoria->fecha_inicio->format('d/m/Y') }} — {{ $auditoria->fecha_fin->format('d/m/Y') }}</p></div>
                    <div><span class="text-xs text-slate-500 uppercase">Auditoría Anterior</span><p class="font-semibold">{{ $auditoria->auditoriaAnterior->codigo ?? 'No aplica' }}</p></div>
                </div>

                @if(!$auditoria->fk_id_responsable_ti || !$auditoria->fk_id_director)
                    <div class="text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
                        Responsable de TI y Dirección deben quedar asignados antes de exportar el informe o aprobarlo.
                    </div>
                @endif
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Responsable de TI</label>
                        <select name="fk_id_responsable_ti" {{ $puedeEditar ? '' : 'disabled' }} class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm disabled:opacity-60">
                            <option value="">Sin asignar</option>
                            @foreach($usuarios as $u)
                                <option value="{{ $u->id_usuario }}" {{ $auditoria->fk_id_responsable_ti == $u->id_usuario ? 'selected' : '' }}>{{ trim($u->nombre . ' ' . ($u->apellido ?? '')) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Dirección</label>
                        <select name="fk_id_director" {{ $puedeEditar ? '' : 'disabled' }} class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm disabled:opacity-60">
                            <option value="">Sin asignar</option>
                            @foreach($usuarios as $u)
                                <option value="{{ $u->id_usuario }}" {{ $auditoria->fk_id_director == $u->id_usuario ? 'selected' : '' }}>{{ trim($u->nombre . ' ' . ($u->apellido ?? '')) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @foreach(['objetivo' => 'Objetivo', 'alcance' => 'Alcance', 'antecedentes' => 'Antecedentes', 'participantes' => 'Participantes'] as $campo => $label)
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">{{ $label }}</label>
                        <textarea name="{{ $campo }}" rows="2" {{ $puedeEditar ? '' : 'disabled' }} class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm disabled:opacity-60">{{ $auditoria->{$campo} }}</textarea>
                    </div>
                @endforeach
                @if($puedeEditar)
                    <div class="flex justify-end"><button type="submit" class="btn btn-dark">Guardar</button></div>
                @endif
            </form>
        </div>

        {{-- 2. MUESTRA --}}
        <div class="p-6 space-y-4 hidden" data-audi-panel="muestra">
            @php $totalMuestra = $auditoria->muestra->count(); $verificados = $auditoria->muestra->filter->estaVerificada()->count(); @endphp
            <div class="flex items-center justify-between flex-wrap gap-3">
                <p class="text-sm text-slate-600">Avance de verificación: <strong>{{ $verificados }} de {{ $totalMuestra }}</strong> activos verificados.</p>
                @if($puedeEditar)
                    <form method="POST" action="{{ route('admin.auditoria-interna.muestra.generar', $auditoria->id) }}" onsubmit="return confirm('¿Generar/regenerar la muestra? Se conservará lo ya verificado.');">
                        @csrf
                        <button type="submit" class="btn btn-secondary">Generar Muestra</button>
                    </form>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Activo</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-600 uppercase">Nivel al Muestrear</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-600 uppercase">Motivo</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-600 uppercase">Encontrado</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-600 uppercase">Ubicación OK</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-600 uppercase">Estado OK</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Observación</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($auditoria->muestra as $item)
                            <tr>
                                <form method="POST" action="{{ route('admin.auditoria-interna.muestra.verificar', [$auditoria->id, $item->id]) }}">
                                    @csrf
                                    @method('PUT')
                                    <td class="px-3 py-2">{{ $item->activo->nombre_equipo ?? 'Activo eliminado' }}</td>
                                    <td class="px-3 py-2 text-center">{{ $item->nivel_riesgo_al_muestrear }}</td>
                                    <td class="px-3 py-2 text-center text-xs">{{ $item->motivo_inclusion }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <select name="encontrado" class="border border-slate-200 rounded px-1 py-0.5 text-xs" {{ $puedeEditar ? '' : 'disabled' }}>
                                            <option value="1" {{ $item->encontrado ? 'selected' : '' }}>Sí</option>
                                            <option value="0" {{ $item->encontrado === false ? 'selected' : '' }}>No</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <select name="ubicacion_coincide" class="border border-slate-200 rounded px-1 py-0.5 text-xs" {{ $puedeEditar ? '' : 'disabled' }}>
                                            <option value="1" {{ $item->ubicacion_coincide ? 'selected' : '' }}>Sí</option>
                                            <option value="0" {{ $item->ubicacion_coincide === false ? 'selected' : '' }}>No</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <select name="estado_coincide" class="border border-slate-200 rounded px-1 py-0.5 text-xs" {{ $puedeEditar ? '' : 'disabled' }}>
                                            <option value="1" {{ $item->estado_coincide ? 'selected' : '' }}>Sí</option>
                                            <option value="0" {{ $item->estado_coincide === false ? 'selected' : '' }}>No</option>
                                        </select>
                                    </td>
                                    <td class="px-3 py-2"><input type="text" name="observacion" value="{{ $item->observacion }}" class="w-full border border-slate-200 rounded px-1.5 py-0.5 text-xs" {{ $puedeEditar ? '' : 'disabled' }} /></td>
                                    <td class="px-3 py-2">
                                        @if($puedeEditar)
                                            <button type="submit" class="btn btn-secondary btn-sm">Guardar</button>
                                        @endif
                                    </td>
                                </form>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-3 py-8 text-center text-slate-400">Aún no se generó la muestra.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 3. RESULTADOS --}}
        <div class="p-6 space-y-4 hidden" data-audi-panel="resultados">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($semaforo as $apartado => $info)
                    <div class="rounded-2xl border p-4 space-y-2
                        {{ $info['color'] === 'ROJO' ? 'border-rose-200 bg-rose-50' : ($info['color'] === 'AMARILLO' ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50') }}">
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-slate-800">Apartado {{ $apartado }}</h4>
                            <span class="text-xs font-bold uppercase px-2 py-0.5 rounded-full
                                {{ $info['color'] === 'ROJO' ? 'bg-rose-600 text-white' : ($info['color'] === 'AMARILLO' ? 'bg-amber-500 text-white' : 'bg-emerald-600 text-white') }}">
                                {{ $info['color'] }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-600">% cumplimiento: {{ $info['porcentaje_cumplimiento'] ?? 'N/A' }}</p>
                        <ul class="text-xs text-slate-700 space-y-0.5">
                            @foreach($info['indicadores'] as $clave => $ind)
                                <li>
                                    {{ $clave }}:
                                    @if(is_array($ind['valor']))
                                        <span class="text-slate-400">(informativo)</span>
                                    @else
                                        <strong>{{ $ind['valor'] ?? 'N/A' }}</strong>
                                        @if($ind['cumple'] !== null)
                                            <span class="{{ $ind['cumple'] ? 'text-emerald-600' : 'text-rose-600' }}">{{ $ind['cumple'] ? '✓' : '✗' }}</span>
                                        @endif
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
            <p class="text-sm text-slate-600">Opinión general calculada: <strong>{{ $opinionCalculada }}</strong></p>
        </div>

        {{-- 4. HALLAZGOS --}}
        <div class="p-6 space-y-6 hidden" data-audi-panel="hallazgos">
            @if($puedeEditar)
                <form method="POST" action="{{ route('admin.auditoria-interna.hallazgos.store', $auditoria->id) }}" class="bg-slate-50 rounded-2xl p-4 space-y-3 border border-slate-200">
                    @csrf
                    <h4 class="font-bold text-slate-800 text-sm">Nuevo Hallazgo</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Apartado</label>
                            <select name="apartado" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-sm" required>
                                @foreach(\App\Models\HallazgoAuditoria::APARTADOS as $ap)
                                    <option value="{{ $ap }}">{{ $ap }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Requerimiento (N.º)</label>
                            <input type="number" name="requerimiento_ref" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Criterio</label>
                            <input list="lista-criterios" name="criterio" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-sm" required />
                            <datalist id="lista-criterios">
                                @foreach($criterios as $lista)
                                    @foreach($lista as $c)
                                        <option value="{{ $c }}">
                                    @endforeach
                                @endforeach
                            </datalist>
                        </div>
                    </div>
                    @foreach(['condicion' => 'Condición', 'causa' => 'Causa', 'efecto' => 'Efecto', 'evidencia' => 'Evidencia', 'recomendacion' => 'Recomendación'] as $campo => $label)
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">{{ $label }}</label>
                            <textarea name="{{ $campo }}" rows="2" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-sm" required></textarea>
                        </div>
                    @endforeach
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Clasificación</label>
                            <select name="clasificacion" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-sm" required>
                                @foreach(\App\Models\HallazgoAuditoria::CLASIFICACIONES as $c)
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Responsable</label>
                            <input type="text" name="responsable" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-sm" required />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Fecha de Compromiso</label>
                            <input type="date" name="fecha_compromiso" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-sm" />
                        </div>
                    </div>
                    @if($auditoria->muestra->isNotEmpty())
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Activos vinculados (de la muestra, opcional — se usa para sugerir gravedad)</label>
                            <select name="activos[]" multiple class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2 text-sm h-28">
                                @foreach($auditoria->muestra as $item)
                                    <option value="{{ $item->fk_id_activo }}">{{ $item->activo->nombre_equipo ?? '-' }} ({{ $item->nivel_riesgo_al_muestrear }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="flex justify-end"><button type="submit" class="btn btn-dark">Registrar Hallazgo</button></div>
                </form>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Código</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-600 uppercase">Apartado</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Condición</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-600 uppercase">Clasificación</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-600 uppercase">Sugerida</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Responsable</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-600 uppercase">Compromiso</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-600 uppercase">Estado</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($auditoria->hallazgos as $h)
                            <tr>
                                <form method="POST" action="{{ route('admin.auditoria-interna.hallazgos.update', $h->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <td class="px-3 py-2 font-mono text-xs">{{ $h->codigo }}</td>
                                    <td class="px-3 py-2 text-center">{{ $h->apartado }}</td>
                                    <td class="px-3 py-2 max-w-xs truncate" title="{{ $h->condicion }}">{{ $h->condicion }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <select name="clasificacion" class="border border-slate-200 rounded px-1 py-0.5 text-xs" {{ $puedeEditar ? '' : 'disabled' }}>
                                            @foreach(\App\Models\HallazgoAuditoria::CLASIFICACIONES as $c)
                                                <option value="{{ $c }}" {{ $h->clasificacion === $c ? 'selected' : '' }}>{{ $c }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 text-center text-xs text-slate-500">{{ $h->clasificacion_sugerida ?? '—' }}</td>
                                    <td class="px-3 py-2">
                                        <input type="text" name="responsable" value="{{ $h->responsable }}" class="w-full border border-slate-200 rounded px-1.5 py-0.5 text-xs" {{ $puedeEditar ? '' : 'disabled' }} />
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="date" name="fecha_compromiso" value="{{ $h->fecha_compromiso?->format('Y-m-d') }}" class="border border-slate-200 rounded px-1.5 py-0.5 text-xs" {{ $puedeEditar ? '' : 'disabled' }} />
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <select name="estado" class="border border-slate-200 rounded px-1 py-0.5 text-xs" {{ ($puedeEditar || $puedeComentar) ? '' : 'disabled' }}>
                                            @foreach(\App\Models\HallazgoAuditoria::ESTADOS as $e)
                                                <option value="{{ $e }}" {{ $h->estado === $e ? 'selected' : '' }}>{{ $e }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        @if($puedeEditar || $puedeComentar)
                                            <button type="submit" class="btn btn-secondary btn-sm">Guardar</button>
                                        @endif
                                    </td>
                                </form>
                            </tr>
                            @if($puedeComentar)
                                <tr>
                                    <td colspan="9" class="px-3 pb-3 bg-slate-50/60">
                                        <form method="POST" action="{{ route('admin.auditoria-interna.hallazgos.update', $h->id) }}" class="flex items-end gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="estado" value="{{ $h->estado }}" />
                                            <div class="flex-1">
                                                <label class="block text-[10px] font-semibold text-slate-500 uppercase mb-1">Comentario del área (Encargado de TI)</label>
                                                <input type="text" name="comentario_area" value="{{ $h->comentario_area }}" class="w-full border border-slate-200 rounded px-2 py-1 text-xs" />
                                            </div>
                                            <button type="submit" class="btn btn-secondary btn-sm">Comentar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr><td colspan="9" class="px-3 py-8 text-center text-slate-400">Sin hallazgos registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 5. SEGUIMIENTO --}}
        <div class="p-6 space-y-4 hidden" data-audi-panel="seguimiento">
            @if(!$auditoria->auditoriaAnterior)
                <p class="text-sm text-slate-400 text-center py-8">No hay auditoría anterior asociada: no aplica seguimiento.</p>
            @else
                <p class="text-sm text-slate-600">Hallazgos abiertos o en proceso de {{ $auditoria->auditoriaAnterior->codigo }}:</p>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Código</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600 uppercase">Condición</th>
                            <th class="px-3 py-2 text-center text-xs font-semibold text-slate-600 uppercase">Estado</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($seguimientoAnterior as $h)
                            <tr>
                                <form method="POST" action="{{ route('admin.auditoria-interna.seguimiento.update', $h->id) }}" class="contents">
                                    @csrf
                                    @method('PUT')
                                    <td class="px-3 py-2 font-mono text-xs">{{ $h->codigo }}</td>
                                    <td class="px-3 py-2 max-w-md">{{ $h->condicion }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <select name="estado" class="border border-slate-200 rounded px-1 py-0.5 text-xs">
                                            @foreach(\App\Models\HallazgoAuditoria::ESTADOS as $e)
                                                <option value="{{ $e }}" {{ $h->estado === $e ? 'selected' : '' }}>{{ $e }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2"><button type="submit" class="btn btn-secondary btn-sm">Actualizar</button></td>
                                </form>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-8 text-center text-slate-400">Sin hallazgos pendientes de la gestión anterior.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endif
        </div>

        {{-- 6. CONCLUSIONES Y APROBACIÓN --}}
        <div class="p-6 space-y-4 hidden" data-audi-panel="conclusiones">
            @if($puedeEditar)
                <form method="POST" action="{{ route('admin.auditoria-interna.generar-textos', $auditoria->id) }}" class="flex items-center justify-between gap-3 bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3">
                    @csrf
                    <p class="text-xs text-indigo-800">
                        Metodología, limitaciones, buenas prácticas y conclusiones también se generan por PLN a partir
                        de la muestra verificada y los hallazgos registrados hasta ahora.
                    </p>
                    <button type="submit" class="btn btn-secondary shrink-0">Generar con PLN</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.auditoria-interna.update', $auditoria->id) }}" class="space-y-4">
                @csrf
                @method('PUT')
                @foreach(['metodologia' => 'Metodología', 'limitaciones' => 'Limitaciones', 'buenas_practicas' => 'Buenas Prácticas', 'conclusiones' => 'Conclusiones'] as $campo => $label)
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">{{ $label }}</label>
                        <textarea name="{{ $campo }}" rows="2" {{ $puedeEditar ? '' : 'disabled' }} class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm disabled:opacity-60">{{ $auditoria->{$campo} }}</textarea>
                    </div>
                @endforeach
                <p class="text-sm text-slate-700">Opinión calculada (solo lectura): <strong>{{ $auditoria->opinion_calculada ?? $opinionCalculada . ' (provisional)' }}</strong></p>
                @if($puedeEditar)
                    <div class="flex justify-end"><button type="submit" class="btn btn-secondary">Guardar</button></div>
                @endif
            </form>

            @if($puedeEditar)
                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                    @if($auditoria->estado === 'BORRADOR')
                        <form method="POST" action="{{ route('admin.auditoria-interna.enviar-revision', $auditoria->id) }}">
                            @csrf
                            <button type="submit" class="btn btn-secondary">Enviar a Revisión</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.auditoria-interna.aprobar', $auditoria->id) }}" onsubmit="return confirm('Al aprobar, el informe queda inmutable. ¿Continuar?');">
                        @csrf
                        <button type="submit" class="btn btn-dark">Aprobar</button>
                    </form>
                </div>
            @endif
        </div>

    </div>

</div>

<script>
(function () {
    function initTabs() {
        var tabs = document.querySelectorAll('.audi-tab-btn');
        if (!tabs.length || tabs[0].dataset.audiInit) return;

        function activar(clave) {
            document.querySelectorAll('[data-audi-panel]').forEach(function (panel) {
                panel.classList.toggle('hidden', panel.dataset.audiPanel !== clave);
            });
            tabs.forEach(function (btn) {
                var activo = btn.dataset.audiTab === clave;
                btn.classList.toggle('text-slate-800', activo);
                btn.classList.toggle('border-blue-600', activo);
                btn.classList.toggle('text-slate-500', !activo);
                btn.classList.toggle('border-transparent', !activo);
            });
        }

        tabs.forEach(function (btn) {
            btn.dataset.audiInit = '1';
            btn.addEventListener('click', function () { activar(btn.dataset.audiTab); });
        });

        activar('datos');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTabs);
    } else {
        initTabs();
    }
    document.addEventListener('turbo:load', initTabs);
})();
</script>
@endsection
