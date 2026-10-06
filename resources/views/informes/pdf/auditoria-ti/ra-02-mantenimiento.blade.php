@extends('informes.pdf.layout')

@section('titulo', 'RA-02 Plan y Ejecución de Mantenimiento')
@section('subtitulo', 'Gestión ' . $filtros['gestion'] . ' — Atiende Req. 18 — Criterio: ' . implode(' · ', config('auditoria.criterios')['7.3']))

@section('contenido')

<p style="font-size:8pt;color:#475569;">
    Fecha de corte: <strong>{{ \Carbon\Carbon::parse($filtros['fecha_corte'])->format('d/m/Y') }}</strong>
</p>

<div class="kpi-grid">
    <div class="kpi"><span class="kpi-label">Programados</span><span class="kpi-value">{{ $datos['programados'] }}</span></div>
    <div class="kpi"><span class="kpi-label">% Cumplimiento</span><span class="kpi-value">{{ $datos['cumplimiento_pct'] ?? 'N/A' }}{{ $datos['cumplimiento_pct'] !== null ? '%' : '' }}</span></div>
    <div class="kpi"><span class="kpi-label">% Preventivo</span><span class="kpi-value">{{ $datos['preventivo_pct'] ?? 'N/A' }}{{ $datos['preventivo_pct'] !== null ? '%' : '' }}</span></div>
    <div class="kpi"><span class="kpi-label">Vencidos</span><span class="kpi-value">{{ $datos['vencidos']->count() }}</span></div>
</div>

<div class="seccion-titulo">Cronograma Mensual — Programados vs. Ejecutados</div>
<table>
    <thead><tr><th>Mes</th><th class="center">Programados</th><th class="center">Ejecutados</th></tr></thead>
    <tbody>
        @foreach($datos['cronograma'] as $fila)
            <tr><td>{{ $fila['mes'] }}</td><td class="center">{{ $fila['programados'] }}</td><td class="center">{{ $fila['ejecutados'] }}</td></tr>
        @endforeach
    </tbody>
</table>

@if($datos['vencidos']->isNotEmpty())
<div class="seccion-titulo">Mantenimientos Vencidos</div>
<table>
    <thead><tr><th>Activo</th><th>Tipo</th><th>Fecha Programada</th></tr></thead>
    <tbody>
        @foreach($datos['vencidos'] as $m)
            <tr><td>{{ $m->activo->nombre_equipo ?? '-' }}</td><td>{{ $m->tipo_mantenimiento }}</td><td>{{ \Carbon\Carbon::parse($m->fecha_programada)->format('d/m/Y') }}</td></tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="seccion-titulo">Detalle por Activo</div>
<table>
    <thead><tr><th>Activo</th><th>Tipo</th><th>Programado</th><th>Ejecutado</th><th>Estado</th></tr></thead>
    <tbody>
        @forelse($datos['mantenimientos'] as $m)
            <tr>
                <td>{{ $m->activo->nombre_equipo ?? '-' }}</td>
                <td>{{ $m->tipo_mantenimiento }}</td>
                <td>{{ \Carbon\Carbon::parse($m->fecha_programada)->format('d/m/Y') }}</td>
                <td>{{ $m->fecha_ejecucion ? \Carbon\Carbon::parse($m->fecha_ejecucion)->format('d/m/Y') : 'No ejecutado' }}</td>
                <td>{{ $m->estado_mantenimiento }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="center">Sin mantenimientos en la gestión seleccionada.</td></tr>
        @endforelse
    </tbody>
</table>

@endsection
