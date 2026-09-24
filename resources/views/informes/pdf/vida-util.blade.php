@extends('informes.pdf.layout')

@section('titulo', 'Vida Útil')
@section('subtitulo', 'Monitoreo de desgaste, tiempo de vida restante y sugerencias de reemplazo tecnológico')

@section('estilos')
<style>
    .kpi-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 5px 0;
        margin-top: 4px;
        margin-bottom: 12px;
    }
    .kpi-table td {
        padding: 7px 5px;
        border-radius: 4px;
        text-align: center;
        vertical-align: middle;
    }
    .kpi-card-total {
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
    }
    .kpi-card-reemplazo {
        background-color: #fff1f2;
        border: 1px solid #fecdd3;
    }
    .kpi-card-superada {
        background-color: #fef2f2;
        border: 1px solid #fca5a5;
    }
    .kpi-card-cumple {
        background-color: #fffbeb;
        border: 1px solid #fde68a;
    }
    .kpi-lbl {
        font-size: 6.8pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: block;
        margin-bottom: 2px;
    }
    .kpi-num {
        font-size: 14pt;
        font-weight: bold;
        font-family: monospace;
        display: block;
        line-height: 1;
    }
</style>
@endsection

@section('contenido')

{{-- 1. Resumen General de Activos (Tarjetas KPI) --}}
<table class="kpi-table">
    <tr>
        <td class="kpi-card-total" style="width: 25%;">
            <span class="kpi-lbl" style="color: #475569;">Total Activos</span>
            <span class="kpi-num" style="color: #0f172a;">{{ $resumenGeneral['total_activos'] }}</span>
        </td>
        <td class="kpi-card-reemplazo" style="width: 25%;">
            <span class="kpi-lbl" style="color: #9f1239;">Sugerencia Reemplazo</span>
            <span class="kpi-num" style="color: #be123c;">{{ $resumenGeneral['sugerencia_reemplazo'] }}</span>
        </td>
        <td class="kpi-card-superada" style="width: 25%;">
            <span class="kpi-lbl" style="color: #991b1b;">Vida Útil Superada</span>
            <span class="kpi-num" style="color: #dc2626;">{{ $resumenGeneral['superaron_vida_util'] }}</span>
        </td>
        <td class="kpi-card-cumple" style="width: 25%;">
            <span class="kpi-lbl" style="color: #92400e;">Cumple en ≤ 1 Año</span>
            <span class="kpi-num" style="color: #d97706;">{{ $resumenGeneral['cumplen_un_anio'] }}</span>
        </td>
    </tr>
</table>

{{-- 2. Resumen por Categoría de Activo --}}
<div class="seccion-titulo">Resumen por Categoría de Activo</div>
<table style="margin-top: 5px; margin-bottom: 14px;">
    <thead>
        <tr>
            <th>Categoría</th>
            <th class="center" style="width: 14%;">Total Activos</th>
            <th class="center" style="width: 18%;">Vida Útil Superada</th>
            <th class="center" style="width: 18%;">Cumple en ≤ 1 Año</th>
            <th class="center" style="width: 20%;">Sugerencia Reemplazo</th>
        </tr>
    </thead>
    <tbody>
        @forelse($resumenCategorias as $cat)
            <tr>
                <td style="font-weight: bold;">
                    {{ $cat['nombre'] }}
                    <span style="font-size: 7pt; color: #64748b; font-weight: normal;">({{ $cat['vida_util_meses'] }}m)</span>
                </td>
                <td class="center font-mono" style="font-weight: bold;">{{ $cat['total'] }}</td>
                <td class="center font-mono">
                    @if($cat['superada'] > 0)
                        <span class="badge badge-critico">{{ $cat['superada'] }}</span>
                    @else
                        <span style="color: #94a3b8;">0</span>
                    @endif
                </td>
                <td class="center font-mono">
                    @if($cat['cumple_1_anio'] > 0)
                        <span class="badge badge-medio">{{ $cat['cumple_1_anio'] }}</span>
                    @else
                        <span style="color: #94a3b8;">0</span>
                    @endif
                </td>
                <td class="center font-mono">
                    @if($cat['sugerencia_reemplazo'] > 0)
                        <span class="badge badge-alto">{{ $cat['sugerencia_reemplazo'] }}</span>
                    @else
                        <span class="badge badge-bajo">0 (Al día)</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="center" style="padding: 10px; color: #64748b; font-style: italic;">
                    No hay categorías registradas.
                </td>
            </tr>
        @endforelse
    </tbody>
    @if(count($resumenCategorias) > 0)
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td style="text-transform: uppercase; font-size: 7.5pt;">Total General</td>
                <td class="center font-mono">{{ collect($resumenCategorias)->sum('total') }}</td>
                <td class="center font-mono" style="color: #b91c1c;">{{ collect($resumenCategorias)->sum('superada') }}</td>
                <td class="center font-mono" style="color: #b45309;">{{ collect($resumenCategorias)->sum('cumple_1_anio') }}</td>
                <td class="center font-mono" style="color: #be123c;">{{ collect($resumenCategorias)->sum('sugerencia_reemplazo') }}</td>
            </tr>
        </tfoot>
    @endif
</table>

{{-- 3. Listado Detallado de Activos --}}
<div class="seccion-titulo">Listado Detallado de Activos</div>
<table style="margin-top: 5px;">
    <thead>
        <tr>
            <th class="center" style="width: 4%;">N.º</th>
            <th style="width: 11%;">Código</th>
            <th style="width: 20%;">Activo</th>
            <th style="width: 14%;">Categoría</th>
            <th style="width: 14%;">Ubicación</th>
            <th class="center" style="width: 8%;">Antigüedad</th>
            <th class="center" style="width: 8%;">Vida Útil</th>
            <th class="center" style="width: 8%;">Meses Rest.</th>
            <th class="center" style="width: 8%;">% Consumido</th>
            <th class="center" style="width: 12%;">Sugerencia</th>
        </tr>
    </thead>
    <tbody>
        @forelse($activos as $i => $activo)
            @php
                $pct = $activo->vida_util['porcentaje_consumido'];
                $restantes = $activo->vida_util['meses_restantes'];
                $superada = $restantes <= 0;
                $cumple1Anio = !$superada && $restantes <= 12;
                $badgeClass = match(true) {
                    $pct >= 90 => 'badge-critico',
                    $pct >= 75 => 'badge-alto',
                    $pct >= 50 => 'badge-medio',
                    default    => 'badge-bajo',
                };
                $ubicacion = $activo->ubicacion
                    ? ($activo->ubicacion->seccion . (!empty($activo->ubicacion->aula) ? ' — ' . $activo->ubicacion->aula : ''))
                    : '—';
            @endphp
            <tr>
                <td class="center" style="color:#64748b; font-weight:bold;">{{ $i + 1 }}</td>
                <td style="font-family:monospace; font-weight:bold; font-size:7.5pt;">{{ $activo->codigo_patrimonial }}</td>
                <td>{{ $activo->nombre_equipo }}</td>
                <td>{{ $activo->categoria->nombre_categoria ?? '—' }}</td>
                <td>{{ $ubicacion }}</td>
                <td class="center font-mono">{{ $activo->vida_util['antiguedad_meses'] }} m</td>
                <td class="center font-mono">{{ $activo->vida_util['vida_util_meses'] }} m</td>
                <td class="center font-mono" style="font-weight:bold; color: {{ $superada ? '#b91c1c' : ($cumple1Anio ? '#b45309' : '#0f172a') }};">
                    {{ $restantes }} m
                </td>
                <td class="center font-mono">
                    <span class="badge {{ $badgeClass }}">{{ $pct }}%</span>
                </td>
                <td class="center">
                    @if($superada)
                        <span class="badge badge-critico">Reemplazo</span>
                    @elseif($cumple1Anio)
                        <span class="badge badge-alto">≤ 1 año</span>
                    @else
                        <span class="badge badge-bajo">En tiempo</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="center" style="padding:20px; color:#64748b; font-style:italic;">
                    No hay activos que coincidan con los filtros aplicados.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
@endsection
