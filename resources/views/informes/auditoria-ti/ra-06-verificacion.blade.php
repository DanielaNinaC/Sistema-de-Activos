@extends('layouts.principal')

@section('title', 'RA-06 Hoja de Verificación de Activos por Ubicación')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <h2 class="text-2xl font-bold text-slate-900">RA-06 &middot; Hoja de Verificación de Activos por Ubicación (CPD)</h2>
    </div>

    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.informes.auditoria-ti.ra06') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Ubicación</label>
                <select name="fk_id_ubicacion" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm min-w-[220px]">
                    <option value="">Seleccione una ubicación</option>
                    @foreach($ubicaciones as $ub)
                        <option value="{{ $ub->id_ubicacion }}" {{ $ubicacionSeleccionada && $ubicacionSeleccionada->id_ubicacion == $ub->id_ubicacion ? 'selected' : '' }}>
                            {{ $ub->seccion }}{{ $ub->aula ? ' - ' . $ub->aula : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">Generar</button>
                @if($ubicacionSeleccionada)
                    <a href="{{ route('admin.informes.auditoria-ti.ra06.pdf', ['fk_id_ubicacion' => $ubicacionSeleccionada->id_ubicacion]) }}" class="btn btn-secondary" target="_blank">Exportar PDF (imprimible)</a>
                @endif
            </div>
        </form>
    </div>

    @if($ubicacionSeleccionada)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200">
                <h3 class="text-sm font-bold text-slate-900">
                    Activos en {{ $ubicacionSeleccionada->seccion }}{{ $ubicacionSeleccionada->aula ? ' - ' . $ubicacionSeleccionada->aula : '' }}
                </h3>
                <p class="text-xs text-slate-500">Formato imprimible para marcar durante la visita de verificación física.</p>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Código</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Activo</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Categoría</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-600 uppercase">Encontrado (Sí/No)</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Estado Observado</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Observación</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($activos as $a)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs">{{ $a->codigo_patrimonial }}</td>
                            <td class="px-4 py-3">{{ $a->nombre_equipo }}</td>
                            <td class="px-4 py-3">{{ $a->categoria->nombre_categoria ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">&#9633;</td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3"></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Sin activos registrados en esta ubicación.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

</div>
@endsection
