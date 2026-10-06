{{-- Encabezado formal para reportes de Auditoría de TI (RA-01..RA-07). --}}
<div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 space-y-1">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <p class="text-[10px] font-semibold text-indigo-600 uppercase tracking-wider">Lycée Franco-Bolivien &middot; Reporte de Auditoría de TI</p>
            <h2 class="text-xl font-bold text-slate-900">{{ $titulo }}</h2>
        </div>
        <span class="text-xs font-semibold px-3 py-1 bg-indigo-50 text-indigo-700 rounded-full border border-indigo-100">Atiende {{ $requerimiento }}</span>
    </div>
    <p class="text-xs text-slate-500">Criterio de referencia: {{ implode(' · ', (array) $criterio) }}</p>
    <p class="text-xs text-slate-400">
        Fecha de corte: <strong class="text-slate-600">{{ \Carbon\Carbon::parse($filtros['fecha_corte'])->format('d/m/Y') }}</strong>
        &middot; Generado por: <strong class="text-slate-600">{{ trim((auth()->user()->nombre ?? '') . ' ' . (auth()->user()->apellido ?? '')) ?: 'Sistema SIATEC' }}</strong>
        &middot; {{ now()->format('d/m/Y H:i') }}
    </p>
</div>
