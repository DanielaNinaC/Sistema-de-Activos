@extends('informes.pdf.layout')

@section('titulo', 'Evaluaciones Críticas Sin Tratamiento')
@section('subtitulo', 'Evaluaciones vigentes en nivel Alto o Crítico sin plan de tratamiento asociado')

@section('contenido')
<table>
    <thead>
        <tr>
            <th>Activo</th>
            <th>Modo de Falla</th>
            <th class="center">Nivel</th>
            <th class="center">Fecha Evaluación</th>
            <th class="center">Días sin Tratamiento</th>
        </tr>
    </thead>
    <tbody>
        @forelse($evaluaciones as $ev)
            <tr>
                <td>{{ $ev->activo->nombre_equipo ?? '-' }} ({{ $ev->activo->codigo_patrimonial ?? '-' }})</td>
                <td>{{ $ev->modoFalla->nombre_modo_falla ?? '-' }}</td>
                <td class="center">
                    <span class="badge {{ $ev->nivel_riesgo === 'Crítico' ? 'badge-critico' : 'badge-alto' }}">{{ $ev->nivel_riesgo }}</span>
                </td>
                <td class="center">{{ \Carbon\Carbon::parse($ev->fecha_evaluacion)->format('d/m/Y') }}</td>
                <td class="center">{{ $ev->dias_sin_tratamiento }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="center">No hay evaluaciones críticas pendientes de tratamiento.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
