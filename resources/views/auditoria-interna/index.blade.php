@extends('layouts.principal')

@section('title', 'Auditoría Interna de TI')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Auditoría Interna de TI</h2>
                <p class="text-xs text-slate-500">Informe General de Auditoría Interna — ISO 19011, ISO 55001, NAG 275</p>
            </div>
        </div>
        @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.auditoria-interna.create') }}" class="btn btn-dark">Nueva Auditoría</a>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Código</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Gestión</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase">Auditor</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Estado</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase">Opinión</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($auditorias as $a)
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $a->codigo }}</td>
                        <td class="px-4 py-3 text-center">{{ $a->gestion }}</td>
                        <td class="px-4 py-3">{{ trim($a->auditor->nombre . ' ' . ($a->auditor->apellido ?? '')) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                {{ $a->estado === 'APROBADO' ? 'bg-emerald-100 text-emerald-800' : ($a->estado === 'EN_REVISION' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                {{ $a->estado }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">{{ $a->opinion_calculada ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.auditoria-interna.show', $a->id) }}" class="btn btn-secondary btn-sm">Abrir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">No hay auditorías registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
