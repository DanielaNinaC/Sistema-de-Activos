@extends('informes.pdf.layout')

@section('titulo', 'RA-04 Clasificación de Activos por Criticidad')
@section('subtitulo', 'Atiende Req. 7 (parcial) — Criterio: ' . implode(' · ', config('auditoria.criterios')['7.1']))

@section('contenido')

<p style="font-size:8pt;color:#475569;">
    Clasificación de criticidad de activos tecnológicos; no incluye la clasificación de la información por confidencialidad.
</p>

<div class="seccion-titulo">Por Nivel de Dependencia (categoría)</div>
<table>
    <thead><tr><th>Nivel de Dependencia</th><th class="right">Total</th><th class="right">%</th></tr></thead>
    <tbody>
        @foreach($datos['porDependencia'] as $nivel => $total)
            <tr><td>{{ $nivel }}</td><td class="right">{{ $total }}</td><td class="right">{{ $datos['total'] > 0 ? round(($total / $datos['total']) * 100, 1) : 0 }}%</td></tr>
        @endforeach
    </tbody>
</table>

<div class="seccion-titulo">Por Nivel de Riesgo (evaluación vigente)</div>
<table>
    <thead><tr><th>Nivel de Riesgo</th><th class="right">Total</th><th class="right">%</th></tr></thead>
    <tbody>
        @foreach($datos['porNivelRiesgo'] as $nivel => $total)
            <tr><td>{{ $nivel }}</td><td class="right">{{ $total }}</td><td class="right">{{ $datos['total'] > 0 ? round(($total / $datos['total']) * 100, 1) : 0 }}%</td></tr>
        @endforeach
    </tbody>
</table>

<div class="seccion-titulo">Cruce Dependencia x Nivel de Riesgo</div>
<table>
    <thead><tr><th>Nivel de Dependencia</th><th>Nivel de Riesgo</th><th class="right">Total</th></tr></thead>
    <tbody>
        @forelse($datos['cruce'] as $dependencia => $porNivel)
            @foreach($porNivel as $nivel => $total)
                <tr><td>{{ $dependencia }}</td><td>{{ $nivel }}</td><td class="right">{{ $total }}</td></tr>
            @endforeach
        @empty
            <tr><td colspan="3" class="center">Sin activos registrados.</td></tr>
        @endforelse
    </tbody>
</table>

@endsection
