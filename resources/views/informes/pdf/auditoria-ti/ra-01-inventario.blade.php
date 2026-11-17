@extends('informes.pdf.layout')

@section('titulo', 'RA-01 Inventario General de Activos Tecnológicos')
@section('subtitulo', 'Atiende Req. 5 — Criterio: ' . implode(' · ', config('auditoria.criterios')['7.1']))

@section('contenido')

<p style="font-size:8pt;color:#475569;">
    Fecha de corte: <strong>{{ \Carbon\Carbon::parse($filtros['fecha_corte'])->format('d/m/Y') }}</strong>
</p>

<div class="kpi-grid">
    <div class="kpi"><span class="kpi-label">Total Activos</span><span class="kpi-value">{{ $datos['total'] }}</span></div>
    <div class="kpi"><span class="kpi-label">Sin Ubicación</span><span class="kpi-value">{{ $datos['alertas']['sin_ubicacion'] }}</span></div>
    <div class="kpi"><span class="kpi-label">Vida Útil Vencida</span><span class="kpi-value">{{ $datos['alertas']['vida_util_vencida'] }}</span></div>
    <div class="kpi"><span class="kpi-label">Categorías</span><span class="kpi-value">{{ $datos['por_categoria']->count() }}</span></div>
</div>

<p style="font-size:7.5pt;color:#94a3b8;">
    No existe en el sistema un campo de "responsable del activo"; ese indicador se omite de este reporte.
</p>

<div class="seccion-titulo">Totales por Categoría</div>
<table>
    <thead><tr><th>Categoría</th><th class="right">Total</th></tr></thead>
    <tbody>
        @foreach($datos['por_categoria'] as $cat => $total)
            <tr><td>{{ $cat }}</td><td class="right">{{ $total }}</td></tr>
        @endforeach
    </tbody>
</table>

<div class="seccion-titulo">Totales por Estado Operativo</div>
<table>
    <thead><tr><th>Estado</th><th class="right">Total</th></tr></thead>
    <tbody>
        @foreach($datos['por_estado'] as $estado => $total)
            <tr><td>{{ $estado }}</td><td class="right">{{ $total }}</td></tr>
        @endforeach
    </tbody>
</table>

<div class="seccion-titulo">Listado de Activos</div>
<table>
    <thead>
        <tr>
            <th>Código</th><th>Categoría</th><th>Ubicación</th><th>Estado</th><th>Etapa</th><th>Adquisición</th><th class="center">Meses Rest.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($datos['activos'] as $a)
            <tr>
                <td>{{ $a->codigo_patrimonial }}</td>
                <td>{{ $a->categoria->nombre_categoria ?? '-' }}</td>
                <td>{{ $a->ubicacion ? trim($a->ubicacion->seccion . ' ' . ($a->ubicacion->aula ?? '')) : 'Sin ubicación' }}</td>
                <td>{{ $a->estado_operativo }}</td>
                <td>{{ $a->etapa_ciclo_vida }}</td>
                <td>{{ $a->fecha_adquisicion ? \Carbon\Carbon::parse($a->fecha_adquisicion)->format('d/m/Y') : '-' }}</td>
                <td class="center">{{ $a->vida_util['meses_restantes'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@endsection
