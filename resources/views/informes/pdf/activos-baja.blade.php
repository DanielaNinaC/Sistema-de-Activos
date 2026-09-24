@extends('informes.pdf.layout')

@section('titulo', 'Reporte de Activos Dados de Baja')
@section('subtitulo', 'Trazabilidad de activos tecnológicos retirados formalmente del servicio')

@section('contenido')

@php
    $etiquetasFiltro = [
        'buscar' => 'Código',
        'fk_id_categoria' => 'Categoría',
        'motivo_baja' => 'Motivo de Baja',
        'fecha_inicio' => 'Baja Desde',
        'fecha_fin' => 'Baja Hasta',
    ];
    $filtrosAplicados = collect($filtros ?? [])
        ->only(array_keys($etiquetasFiltro))
        ->filter(fn ($v) => filled($v));

    if ($filtrosAplicados->has('fk_id_categoria')) {
        $catFiltro = \App\Models\Categoria::find($filtrosAplicados['fk_id_categoria']);
        $filtrosAplicados['fk_id_categoria'] = $catFiltro->nombre_categoria ?? $filtrosAplicados['fk_id_categoria'];
    }
@endphp

@if($filtrosAplicados->isNotEmpty())
    <div class="seccion-titulo" style="margin-top: 0;">Filtros Aplicados</div>
    <p style="font-size: 9px; color: #475569;">
        @foreach($filtrosAplicados as $clave => $valor)
            <strong>{{ $etiquetasFiltro[$clave] }}:</strong> {{ $valor }}@if(!$loop->last) &nbsp;&middot;&nbsp; @endif
        @endforeach
    </p>
@endif

{{-- Resumen --}}
<div class="kpi-grid">
    <div class="kpi" style="width: 22%;">
        <span class="kpi-label">Total de Bajas</span>
        <span class="kpi-value">{{ $resumen['total'] }}</span>
    </div>
    <div class="kpi" style="width: 22%;">
        <span class="kpi-label">Con Motivo Registrado</span>
        <span class="kpi-value">{{ $resumen['con_motivo_registrado'] }}</span>
    </div>
</div>

<table style="margin-top: 8px;">
    <tr>
        <td style="width: 50%; vertical-align: top; border: none; padding: 0 6px 0 0;">
            <div class="seccion-titulo" style="margin-top: 4px;">Bajas por Categoría</div>
            <table>
                <thead><tr><th>Categoría</th><th class="center" style="width: 50px;">Cant.</th></tr></thead>
                <tbody>
                    @forelse($resumen['por_categoria'] as $categoria => $cantidad)
                        <tr><td>{{ $categoria }}</td><td class="center">{{ $cantidad }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="center" style="color: #94a3b8;">Sin datos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </td>
        <td style="width: 50%; vertical-align: top; border: none; padding: 0 0 0 6px;">
            <div class="seccion-titulo" style="margin-top: 4px;">Bajas por Motivo</div>
            <table>
                <thead><tr><th>Motivo</th><th class="center" style="width: 50px;">Cant.</th></tr></thead>
                <tbody>
                    @forelse($resumen['por_motivo'] as $motivo => $cantidad)
                        <tr><td>{{ $motivo }}</td><td class="center">{{ $cantidad }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="center" style="color: #94a3b8;">Sin datos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </td>
    </tr>
</table>

<div class="seccion-titulo">Detalle de Activos Dados de Baja</div>
<table>
    <thead>
        <tr>
            <th>Código</th>
            <th>Categoría</th>
            <th>Marca / Modelo</th>
            <th>Últ. Ubicación</th>
            <th>Estado Previo</th>
            <th>Fecha Baja</th>
            <th>Motivo</th>
            <th>Responsable</th>
            <th>Observaciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($activos as $activo)
            <tr>
                <td><strong>{{ $activo->codigo_patrimonial }}</strong></td>
                <td>{{ $activo->categoria->nombre_categoria ?? '-' }}</td>
                <td>{{ trim(($activo->marca ?? '') . ' ' . ($activo->modelo ?? '')) ?: '-' }}</td>
                <td>
                    @if($activo->ubicacion)
                        {{ $activo->ubicacion->seccion }}{{ $activo->ubicacion->aula ? ' - ' . $activo->ubicacion->aula : '' }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $activo->estado_previo_baja ?? 'No especificado' }}</td>
                <td>{{ $activo->fecha_baja ? $activo->fecha_baja->format('d/m/Y') : 'No especificado' }}</td>
                <td>{{ $activo->motivo_baja ?? 'No especificado' }}</td>
                <td>{{ $activo->usuarioBaja ? trim($activo->usuarioBaja->nombre . ' ' . ($activo->usuarioBaja->apellido ?? '')) : 'No especificado' }}</td>
                <td style="font-size: 8px;">{{ $activo->observaciones ?: '-' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="center" style="padding: 20px; color: #64748b;">
                    No se encontraron activos dados de baja para los filtros seleccionados.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@endsection
