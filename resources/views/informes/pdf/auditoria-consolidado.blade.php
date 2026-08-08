@extends('informes.pdf.layout')

@section('titulo', 'Auditoría Consolidada del Sistema')
@section('subtitulo', 'Período: ' . \Carbon\Carbon::parse($resumen['periodo']['inicio'])->format('d/m/Y') . ' — ' . \Carbon\Carbon::parse($resumen['periodo']['fin'])->format('d/m/Y'))

@section('contenido')

<div class="seccion-titulo">Resumen General de Indicadores y Métricas</div>
<table>
    <thead>
        <tr>
            <th>Indicador / Métrica</th>
            <th class="center">Valor Registrado</th>
            <th>Diagnóstico / Estado</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Total Activos Registrados</td>
            <td class="center"><strong>{{ $resumen['total_activos'] }}</strong></td>
            <td>Inventario General</td>
        </tr>
        <tr>
            <td>Incidentes Reportados en el Período</td>
            <td class="center"><strong>{{ $resumen['total_incidentes'] }}</strong></td>
            <td>Registrados</td>
        </tr>
        <tr>
            <td>Mantenimientos Ejecutados</td>
            <td class="center"><strong>{{ $resumen['total_mantenimientos'] }}</strong></td>
            <td>Ejecutados</td>
        </tr>
        <tr>
            <td>Vida Útil Consumida Promedio</td>
            <td class="center"><strong>{{ $resumen['promedio_vida_util_consumida'] }}%</strong></td>
            <td>Promedio Global</td>
        </tr>
        <tr>
            <td>Tratamientos de Riesgo Pendientes</td>
            <td class="center"><strong>{{ $resumen['tratamientos_pendientes'] }}</strong></td>
            <td><span class="badge badge-medio">Pendientes</span></td>
        </tr>
        <tr>
            <td>Tratamientos de Riesgo Vencidos</td>
            <td class="center"><strong>{{ $resumen['tratamientos_vencidos'] }}</strong></td>
            <td><span class="badge badge-critico">Vencidos</span></td>
        </tr>
        <tr>
            <td>Activos en Nivel Crítico</td>
            <td class="center"><strong>{{ $resumen['distribucion_riesgo']['Crítico'] ?? 0 }}</strong></td>
            <td><span class="badge badge-critico">Crítico</span></td>
        </tr>
        <tr>
            <td>Activos en Nivel Alto</td>
            <td class="center"><strong>{{ $resumen['distribucion_riesgo']['Alto'] ?? 0 }}</strong></td>
            <td><span class="badge badge-alto">Alto</span></td>
        </tr>
    </tbody>
</table>

<div class="seccion-titulo">Distribución de Activos por Nivel de Riesgo (última evaluación vigente)</div>
<table>
    <thead><tr><th>Nivel</th><th class="right">Total Activos</th></tr></thead>
    <tbody>
        @foreach($resumen['distribucion_riesgo'] as $nivel => $total)
            <tr><td>{{ $nivel }}</td><td class="right">{{ $total }}</td></tr>
        @endforeach
    </tbody>
</table>

<div class="seccion-titulo">Mantenimientos Ejecutados por Tipo (período)</div>
<table>
    <thead><tr><th>Tipo</th><th class="right">Total</th></tr></thead>
    <tbody>
        @forelse($resumen['mantenimientos_por_tipo'] as $tipo => $total)
            <tr><td>{{ $tipo }}</td><td class="right">{{ $total }}</td></tr>
        @empty
            <tr><td colspan="2" class="center">Sin mantenimientos ejecutados en el período.</td></tr>
        @endforelse
    </tbody>
</table>

@endsection
