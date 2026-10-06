@extends('informes.pdf.layout')

@section('titulo', 'RA-03 Registro de Incidentes de la Gestión')
@section('subtitulo', 'Gestión ' . $filtros['gestion'] . ' — Atiende Req. 21 — Criterio: ' . implode(' · ', config('auditoria.criterios')['7.4']))

@section('contenido')

<p style="font-size:8pt;color:#475569;">
    Fecha de corte: <strong>{{ \Carbon\Carbon::parse($filtros['fecha_corte'])->format('d/m/Y') }}</strong>.
    No existe en el sistema una columna de "tipo de incidente"; ese filtro se omite de este reporte.
</p>

<div class="kpi-grid">
    <div class="kpi"><span class="kpi-label">Total Incidentes</span><span class="kpi-value">{{ $datos['total'] }}</span></div>
    <div class="kpi"><span class="kpi-label">TMR (días)</span><span class="kpi-value">{{ $datos['tmr_dias'] ?? 'N/A' }}</span></div>
    <div class="kpi"><span class="kpi-label">Abiertos a Corte</span><span class="kpi-value">{{ $datos['abiertos_a_corte'] }}</span></div>
    <div class="kpi"><span class="kpi-label">Categorías Afectadas</span><span class="kpi-value">{{ $datos['por_categoria']->count() }}</span></div>
</div>

<div class="seccion-titulo">Detalle de Incidentes</div>
<table>
    <thead><tr><th>Activo</th><th>Categoría</th><th>Apertura</th><th>Cierre</th><th>Estado</th></tr></thead>
    <tbody>
        @forelse($datos['incidentes'] as $i)
            <tr>
                <td>{{ $i->activo->nombre_equipo ?? '-' }}</td>
                <td>{{ $i->activo->categoria->nombre_categoria ?? '-' }}</td>
                <td>{{ \Carbon\Carbon::parse($i->fecha_incidente)->format('d/m/Y H:i') }}</td>
                <td>{{ $i->fecha_cierre ? \Carbon\Carbon::parse($i->fecha_cierre)->format('d/m/Y H:i') : 'Sin cerrar' }}</td>
                <td>{{ $i->estado_incidente }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="center">Sin incidentes en la gestión seleccionada.</td></tr>
        @endforelse
    </tbody>
</table>

@endsection
