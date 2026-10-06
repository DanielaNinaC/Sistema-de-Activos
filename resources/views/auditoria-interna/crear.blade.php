@extends('layouts.principal')

@section('title', 'Nueva Auditoría Interna de TI')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ route('admin.auditoria-interna.index') }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <h2 class="text-2xl font-bold text-slate-900">Nueva Auditoría Interna de TI</h2>
    </div>

    @if($anteriorAprobada)
        <div class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 text-xs text-indigo-800">
            Se preseleccionará como auditoría anterior: <strong>{{ $anteriorAprobada->codigo }}</strong> (gestión {{ $anteriorAprobada->gestion }}),
            para la pestaña de Seguimiento.
        </div>
    @endif

    <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3 text-xs text-emerald-800">
        Objetivo, alcance y antecedentes se generan automáticamente por PLN a partir de estos datos (gestión, fechas,
        auditoría anterior) apenas creas el borrador — no hace falta redactarlos aquí. Podrás revisarlos, editarlos o
        regenerarlos en cualquier momento desde el asistente.
    </div>

    <form method="POST" action="{{ route('admin.auditoria-interna.store') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Gestión (Año)</label>
                <input type="number" name="gestion" value="{{ old('gestion', now()->year) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" required />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', now()->startOfYear()->toDateString()) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" required />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Fecha Fin</label>
                <input type="date" name="fecha_fin" value="{{ old('fecha_fin', now()->endOfYear()->toDateString()) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" required />
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Fecha de Corte</label>
            <input type="date" name="fecha_corte" value="{{ old('fecha_corte', now()->toDateString()) }}" class="w-full sm:w-1/3 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm" required />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Responsable de TI</label>
                <select name="fk_id_responsable_ti" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm">
                    <option value="">Sin asignar</option>
                    @foreach($usuarios as $u)
                        <option value="{{ $u->id_usuario }}">{{ trim($u->nombre . ' ' . ($u->apellido ?? '')) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Dirección</label>
                <select name="fk_id_director" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm">
                    <option value="">Sin asignar</option>
                    @foreach($usuarios as $u)
                        <option value="{{ $u->id_usuario }}">{{ trim($u->nombre . ' ' . ($u->apellido ?? '')) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2">
            Responsable de TI y Dirección deben quedar asignados (aquí o luego en el asistente) antes de poder exportar el informe o aprobarlo.
        </p>

        <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Participantes</label>
            <textarea name="participantes" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm">{{ old('participantes') }}</textarea>
        </div>

        <div class="pt-2 border-t border-slate-100 flex justify-end">
            <button type="submit" class="btn btn-dark">Crear Borrador</button>
        </div>
    </form>

</div>
@endsection
