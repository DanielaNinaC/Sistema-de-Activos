{{--
    Badge de prioridad de atención de un incidente.
    Uso: @include('incidentes.partials.badge-prioridad', ['prioridad' => $incidente->prioridad])

    Las clases se escriben completas a propósito (nada de "bg-{{ $color }}-100"),
    porque Tailwind no detecta nombres de clase construidos por interpolación.
--}}
@php
    $valor = trim($prioridad ?? '');
    $estiloPrioridad = match ($valor) {
        'Alta' => 'bg-rose-100 text-rose-800 border-rose-200',
        'Media' => 'bg-amber-100 text-amber-800 border-amber-200',
        'Baja' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };
@endphp
<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $estiloPrioridad }}">
    {{ $valor !== '' ? $valor : 'Sin definir' }}
</span>
