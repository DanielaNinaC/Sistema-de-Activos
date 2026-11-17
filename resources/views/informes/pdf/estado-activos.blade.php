@extends('informes.pdf.layout')

@section('titulo', 'Reporte de Activos por Categoría y Estado')
@section('subtitulo', 'Consolidado de distribución de activos tecnológicos según condición operativa')

@section('estilos')
<style>
    .chart-box { border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-top: 6px; background: #ffffff; }
    .chart-table { width: 100%; border-collapse: collapse; }
    .chart-table td { border: none; padding: 1px 4px; }
    .chart-label { width: 100px; font-size: 8px; font-weight: bold; color: #0f172a; vertical-align: middle; }
    .chart-bar-track { background: #f1f5f9; border-radius: 3px; height: 8px; width: 100%; }
    .chart-bar { height: 8px; border-radius: 3px; }
    .chart-bar-optimo { background: #3b82f6; }
    .chart-bar-regular { background: #22c55e; }
    .chart-bar-obsoleto { background: #f97316; }
    .chart-value { width: 26px; text-align: right; font-size: 7px; color: #475569; }
    .chart-row-last td { padding-bottom: 6px; }
    .chart-legend { margin-top: 8px; text-align: center; }
    .legend-item { display: inline-block; margin: 0 8px; font-size: 8px; color: #475569; }
    .legend-dot { display: inline-block; width: 7px; height: 7px; border-radius: 50%; margin-right: 3px; }
    .legend-optimo { background: #3b82f6; }
    .legend-regular { background: #22c55e; }
    .legend-obsoleto { background: #f97316; }
</style>
@endsection

@section('contenido')

{{-- Resumen Superior de Indicadores --}}
<div class="kpi-grid">
    <div class="kpi" style="width: 22%;">
        <span class="kpi-label">Total Activos</span>
        <span class="kpi-value">{{ number_format($datos['totales']['total']) }}</span>
    </div>
    <div class="kpi" style="width: 22%; border-color: #a7f3d0; background: #f0fdf4;">
        <span class="kpi-label" style="color: #166534;">Estado Óptimo</span>
        <span class="kpi-value" style="color: #15803d;">{{ number_format($datos['totales']['optimo']) }}</span>
    </div>
    <div class="kpi" style="width: 22%; border-color: #fde68a; background: #fffbeb;">
        <span class="kpi-label" style="color: #854d0e;">Estado Regular</span>
        <span class="kpi-value" style="color: #b45309;">{{ number_format($datos['totales']['regular']) }}</span>
    </div>
    <div class="kpi" style="width: 22%; border-color: #fecdd3; background: #fff1f2;">
        <span class="kpi-label" style="color: #9f1239;">Estado Obsoleto</span>
        <span class="kpi-value" style="color: #be123c;">{{ number_format($datos['totales']['obsoleto']) }}</span>
    </div>
</div>

<div class="seccion-titulo">Distribución de Estados por Categoría</div>
<div class="chart-box">
    @php
        $valoresChart = array_merge($datos['chart']['optimo'], $datos['chart']['regular'], $datos['chart']['obsoleto']);
        $maxValorChart = max(1, ...$valoresChart);
    @endphp
    <table class="chart-table">
        @forelse($datos['chart']['labels'] as $i => $etiqueta)
            <tr>
                <td class="chart-label" rowspan="3">{{ $etiqueta }}</td>
                <td class="chart-bar-cell" style="width: 65%;">
                    <div class="chart-bar-track">
                        <div class="chart-bar chart-bar-optimo" style="width: {{ round($datos['chart']['optimo'][$i] / $maxValorChart * 100, 1) }}%;"></div>
                    </div>
                </td>
                <td class="chart-value">{{ $datos['chart']['optimo'][$i] }}</td>
            </tr>
            <tr>
                <td class="chart-bar-cell">
                    <div class="chart-bar-track">
                        <div class="chart-bar chart-bar-regular" style="width: {{ round($datos['chart']['regular'][$i] / $maxValorChart * 100, 1) }}%;"></div>
                    </div>
                </td>
                <td class="chart-value">{{ $datos['chart']['regular'][$i] }}</td>
            </tr>
            <tr class="chart-row-last">
                <td class="chart-bar-cell">
                    <div class="chart-bar-track">
                        <div class="chart-bar chart-bar-obsoleto" style="width: {{ round($datos['chart']['obsoleto'][$i] / $maxValorChart * 100, 1) }}%;"></div>
                    </div>
                </td>
                <td class="chart-value">{{ $datos['chart']['obsoleto'][$i] }}</td>
            </tr>
        @empty
            <tr>
                <td class="center" style="color: #64748b; padding: 10px;">Sin datos para graficar.</td>
            </tr>
        @endforelse
    </table>
    <div class="chart-legend">
        <span class="legend-item"><span class="legend-dot legend-optimo"></span>Óptimo</span>
        <span class="legend-item"><span class="legend-dot legend-regular"></span>Regular</span>
        <span class="legend-item"><span class="legend-dot legend-obsoleto"></span>Obsoleto</span>
    </div>
</div>

<div class="seccion-titulo" style="margin-top: 20px;">Distribución por Categoría de Activos</div>
<table>
    <thead>
        <tr>
            <th class="center" style="width: 35px;">#</th>
            <th>Categoría del Activo</th>
            <th class="center" style="width: 75px; color: #15803d;">Óptimo</th>
            <th class="center" style="width: 75px; color: #b45309;">Regular</th>
            <th class="center" style="width: 75px; color: #be123c;">Obsoleto</th>
            <th class="center" style="width: 75px;">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($datos['filas'] as $fila)
            <tr>
                <td class="center" style="color: #64748b;">{{ $fila['numero'] }}</td>
                <td><strong>{{ $fila['categoria'] }}</strong></td>
                <td class="center">
                    @if($fila['optimo'] > 0)
                        <span class="badge badge-bajo">{{ $fila['optimo'] }}</span>
                    @else
                        <span style="color: #94a3b8;">0</span>
                    @endif
                </td>
                <td class="center">
                    @if($fila['regular'] > 0)
                        <span class="badge badge-medio">{{ $fila['regular'] }}</span>
                    @else
                        <span style="color: #94a3b8;">0</span>
                    @endif
                </td>
                <td class="center">
                    @if($fila['obsoleto'] > 0)
                        <span class="badge badge-critico">{{ $fila['obsoleto'] }}</span>
                    @else
                        <span style="color: #94a3b8;">0</span>
                    @endif
                </td>
                <td class="center">
                    <strong>{{ $fila['total'] }}</strong>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="center" style="padding: 20px; color: #64748b;">
                    No se encontraron registros de activos para los filtros seleccionados.
                </td>
            </tr>
        @endforelse
    </tbody>
    @if(count($datos['filas']) > 0)
        <tfoot>
            <tr style="background: #f1f5f9; font-weight: bold; border-top: 2px solid #94a3b8;">
                <td colspan="2" class="right" style="padding: 8px; font-size: 10px; text-transform: uppercase;">
                    TOTAL GENERAL
                </td>
                <td class="center" style="color: #15803d; font-size: 11px;">
                    {{ $datos['totales']['optimo'] }}
                </td>
                <td class="center" style="color: #b45309; font-size: 11px;">
                    {{ $datos['totales']['regular'] }}
                </td>
                <td class="center" style="color: #be123c; font-size: 11px;">
                    {{ $datos['totales']['obsoleto'] }}
                </td>
                <td class="center" style="color: #0f172a; font-size: 12px; font-weight: bold;">
                    {{ $datos['totales']['total'] }}
                </td>
            </tr>
        </tfoot>
    @endif
</table>

@endsection
