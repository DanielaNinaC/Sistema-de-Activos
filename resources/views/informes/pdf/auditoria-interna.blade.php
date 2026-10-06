@extends('informes.pdf.layout')

@section('titulo', 'Informe General de Auditoría Interna de TI')
@section('subtitulo', $auditoria->codigo . ' — Gestión ' . $auditoria->gestion . ' — CONFIDENCIAL')

@section('estilos')
<style>
    .firma { margin-top: 40px; border-top: 1pt solid #94a3b8; padding-top: 4px; width: 45%; display: inline-block; font-size: 8pt; }

    /* Este informe usa títulos de sección en negrilla y numerados en vez del
       bloque de color sólido del layout genérico de Informes. */
    .seccion-titulo {
        background: none;
        color: #0f172a;
        font-size: 10.5pt;
        font-weight: bold;
        padding: 0 0 3px 0;
        margin-top: 18px;
        margin-bottom: 5px;
        border-radius: 0;
        border-bottom: 1pt solid #cbd5e1;
    }

    /* Encabezados de tabla en azul suave (no el azul marino del layout genérico). */
    th {
        background-color: #dbeafe;
        color: #1e3a5f;
        border-bottom: 2px solid #93c5fd;
        border-left: 0.5pt solid #bfdbfe;
        border-right: 0.5pt solid #bfdbfe;
    }
</style>
@endsection

@section('contenido')

{{-- 0. Carátula / control del documento --}}
<div class="seccion-titulo">Control del Documento</div>
<table>
    <tbody>
        <tr><td><strong>Código</strong></td><td>{{ $auditoria->codigo }}</td><td><strong>Estado</strong></td><td>{{ $auditoria->estado }}</td></tr>
        <tr><td><strong>Gestión</strong></td><td>{{ $auditoria->gestion }}</td><td><strong>Fecha de Corte</strong></td><td>{{ $auditoria->fecha_corte->format('d/m/Y') }}</td></tr>
        <tr><td><strong>Clasificación</strong></td><td colspan="3">CONFIDENCIAL — Distribución: Auditor, Encargado de TI, Dirección</td></tr>
    </tbody>
</table>

{{-- 1. Resumen ejecutivo --}}
<div class="seccion-titulo">1. Resumen Ejecutivo</div>
<p style="text-align: justify;">{{ $datos['resumen_ejecutivo'] ?? '' }}</p>
<table>
    <thead><tr><th>Indicador</th><th class="center">Valor</th><th class="center">Meta</th><th class="center">Cumple</th></tr></thead>
    <tbody>
        @foreach($datos['indicadores'] as $clave => $ind)
            @continue($ind['informativo'] ?? false)
            <tr>
                <td>{{ $clave }}</td>
                <td class="center">{{ $ind['valor'] ?? 'N/A' }}</td>
                <td class="center">{{ ($ind['meta']['op'] ?? '') . ' ' . ($ind['meta']['valor'] ?? '') }}</td>
                <td class="center">{{ $ind['cumple'] === null ? 'N/A' : ($ind['cumple'] ? 'Sí' : 'No') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- 2. Antecedentes --}}
<div class="seccion-titulo">2. Antecedentes</div>
<p style="text-align: justify;">{{ $auditoria->antecedentes ?: 'No se registraron antecedentes.' }}</p>

{{-- 3. Objetivo, objeto y alcance --}}
<div class="seccion-titulo">3. Objetivo, Objeto y Alcance</div>
<p><strong>Objetivo:</strong> {{ $auditoria->objetivo ?: '—' }}</p>
<p><strong>Alcance:</strong> {{ $auditoria->alcance ?: '—' }}</p>
<p><strong>Gestión:</strong> {{ $auditoria->gestion }} &nbsp; <strong>Fecha de corte:</strong> {{ $auditoria->fecha_corte->format('d/m/Y') }}</p>

{{-- 4. Criterios de auditoría --}}
<div class="seccion-titulo">4. Criterios de Auditoría</div>
<table>
    <thead><tr><th>Apartado</th><th>Criterios de Referencia</th></tr></thead>
    <tbody>
        @foreach(config('auditoria.criterios') as $apartado => $lista)
            <tr><td class="center">{{ $apartado }}</td><td>{{ implode(' · ', $lista) }}</td></tr>
        @endforeach
    </tbody>
</table>

{{-- 5. Metodología, muestra y limitaciones --}}
<div class="seccion-titulo">5. Metodología, Muestra y Limitaciones</div>
<p><strong>Metodología:</strong> {{ $auditoria->metodologia ?: '—' }}</p>
<p><strong>Limitaciones:</strong> {{ $auditoria->limitaciones ?: '—' }}</p>
<p>
    Tamaño de la muestra: <strong>{{ $auditoria->muestra->count() }}</strong> activos —
    por riesgo Alto/Crítico: <strong>{{ $auditoria->muestra->where('motivo_inclusion', 'RIESGO_ALTO_CRITICO')->count() }}</strong>,
    aleatorios: <strong>{{ $auditoria->muestra->where('motivo_inclusion', 'ALEATORIO')->count() }}</strong>.
</p>

{{-- 6. Equipo auditor, participantes, fechas --}}
<div class="seccion-titulo">6. Equipo Auditor, Participantes y Fechas</div>
<p><strong>Auditor:</strong> {{ trim($auditoria->auditor->nombre . ' ' . ($auditoria->auditor->apellido ?? '')) }}</p>
<p><strong>Responsable de TI:</strong> {{ $auditoria->responsableTi ? trim($auditoria->responsableTi->nombre . ' ' . ($auditoria->responsableTi->apellido ?? '')) : 'No asignado' }}</p>
<p><strong>Participantes:</strong> {{ $auditoria->participantes ?: '—' }}</p>
<p><strong>Período de ejecución:</strong> {{ $auditoria->fecha_inicio->format('d/m/Y') }} — {{ $auditoria->fecha_fin->format('d/m/Y') }}</p>

{{-- 7. Resultados por apartado --}}
<div class="seccion-titulo">7. Resultados por Apartado</div>
<table>
    <thead><tr><th>Apartado</th><th class="center">Semáforo</th><th class="center">% Cumplimiento</th><th>Reporte RA de Referencia</th></tr></thead>
    <tbody>
        @php
            $refsRa = ['7.1' => 'RA-01 / RA-04', '7.2' => 'RA-01', '7.3' => 'RA-02', '7.4' => 'RA-03', '7.5' => 'RA-05', '7.6' => '—'];
        @endphp
        @foreach($datos['semaforo'] as $apartado => $info)
            <tr>
                <td>{{ $apartado }}</td>
                <td class="center"><span class="badge badge-{{ $info['color'] === 'ROJO' ? 'critico' : ($info['color'] === 'AMARILLO' ? 'medio' : 'bajo') }}">{{ $info['color'] }}</span></td>
                <td class="center">{{ $info['porcentaje_cumplimiento'] ?? 'N/A' }}</td>
                <td>{{ $refsRa[$apartado] ?? '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- 8. Hallazgos detallados --}}
<div class="seccion-titulo">8. Hallazgos Detallados</div>
@php
    $ordenClasificacion = ['NC_MAYOR' => 1, 'NC_MENOR' => 2, 'OBSERVACION' => 3, 'OPORTUNIDAD_MEJORA' => 4];
    $hallazgosOrdenados = $auditoria->hallazgos->sortBy(fn ($h) => $ordenClasificacion[$h->clasificacion] ?? 9);
@endphp
@forelse($hallazgosOrdenados as $h)
    <table style="margin-top:6px;">
        <tbody>
            <tr><td style="width:22%;"><strong>{{ $h->codigo }}</strong></td><td><span class="badge badge-{{ $h->clasificacion === 'NC_MAYOR' ? 'critico' : ($h->clasificacion === 'NC_MENOR' ? 'alto' : 'neutral') }}">{{ $h->clasificacion }}</span> — Apartado {{ $h->apartado }}</td></tr>
            <tr><td><strong>Condición</strong></td><td>{{ $h->condicion }}</td></tr>
            <tr><td><strong>Criterio</strong></td><td>{{ $h->criterio }}</td></tr>
            <tr><td><strong>Causa</strong></td><td>{{ $h->causa }}</td></tr>
            <tr><td><strong>Efecto</strong></td><td>{{ $h->efecto }}</td></tr>
            <tr><td><strong>Evidencia</strong></td><td>{{ $h->evidencia }}</td></tr>
            <tr><td><strong>Recomendación</strong></td><td>{{ $h->recomendacion }}</td></tr>
            <tr><td><strong>Responsable / Compromiso</strong></td><td>{{ $h->responsable }} — {{ $h->fecha_compromiso?->format('d/m/Y') ?? 'Sin fecha' }} ({{ $h->estado }})</td></tr>
            @if($h->comentario_area)
                <tr><td><strong>Comentario del Área</strong></td><td>{{ $h->comentario_area }}</td></tr>
            @endif
        </tbody>
    </table>
@empty
    <p class="center">No se registraron hallazgos.</p>
@endforelse

{{-- 9. Buenas prácticas --}}
<div class="seccion-titulo">9. Buenas Prácticas Identificadas</div>
<p style="text-align: justify;">{{ $auditoria->buenas_practicas ?: 'No se identificaron buenas prácticas destacables en el período.' }}</p>

{{-- 10. Conclusiones y opinión --}}
<div class="seccion-titulo">10. Conclusiones y Opinión</div>
<p style="text-align: justify;">{{ $auditoria->conclusiones ?: '—' }}</p>
<p style="text-align: justify; font-style: italic; font-size: 7.5pt; color: #64748b;">
    Los resultados se basan en una muestra y en la información registrada en el sistema a la fecha de corte; no
    constituyen una verificación del 100% de los activos.
</p>

{{-- 11. Plan de acción y seguimiento --}}
<div class="seccion-titulo">11. Plan de Acción y Seguimiento</div>
<table>
    <thead><tr><th>Código</th><th>Responsable</th><th class="center">Compromiso</th><th class="center">Estado</th></tr></thead>
    <tbody>
        @forelse($auditoria->hallazgos->where('estado', '!=', 'CERRADO') as $h)
            <tr><td>{{ $h->codigo }}</td><td>{{ $h->responsable }}</td><td class="center">{{ $h->fecha_compromiso?->format('d/m/Y') ?? '-' }}</td><td class="center">{{ $h->estado }}</td></tr>
        @empty
            <tr><td colspan="4" class="center">Sin acciones pendientes de la gestión actual.</td></tr>
        @endforelse
    </tbody>
</table>
@if($auditoria->auditoriaAnterior)
    <p style="margin-top:8px;"><strong>Pendientes de la gestión anterior ({{ $auditoria->auditoriaAnterior->codigo }}):</strong></p>
    <table>
        <thead><tr><th>Código</th><th>Responsable</th><th class="center">Compromiso</th><th class="center">Estado</th></tr></thead>
        <tbody>
            @forelse($seguimientoAnterior as $h)
                <tr><td>{{ $h->codigo }}</td><td>{{ $h->responsable }}</td><td class="center">{{ $h->fecha_compromiso?->format('d/m/Y') ?? '-' }}</td><td class="center">{{ $h->estado }}</td></tr>
            @empty
                <tr><td colspan="4" class="center">Sin hallazgos pendientes de la gestión anterior.</td></tr>
            @endforelse
        </tbody>
    </table>
@endif

{{-- 12. Aprobación y distribución --}}
<div class="seccion-titulo">12. Aprobación y Distribución</div>
<table style="border: none;">
    <tr style="border: none;">
        <td style="border: none; width: 33%; text-align: center;">
            <div class="firma">
                <strong>{{ trim($auditoria->auditor->nombre . ' ' . ($auditoria->auditor->apellido ?? '')) }}</strong><br>
                <span style="color: #64748b;">Auditor</span>
            </div>
        </td>
        <td style="border: none; width: 33%; text-align: center;">
            <div class="firma">
                <strong>{{ $auditoria->responsableTi ? trim($auditoria->responsableTi->nombre . ' ' . ($auditoria->responsableTi->apellido ?? '')) : 'No asignado' }}</strong><br>
                <span style="color: #64748b;">Encargado de TI</span>
            </div>
        </td>
        <td style="border: none; width: 33%; text-align: center;">
            <div class="firma">
                <strong>{{ $auditoria->director ? trim($auditoria->director->nombre . ' ' . ($auditoria->director->apellido ?? '')) : 'No asignado' }}</strong><br>
                <span style="color: #64748b;">Dirección</span>
            </div>
        </td>
    </tr>
</table>

{{-- Anexo A --}}
<div class="seccion-titulo" style="page-break-before: always; text-align: center;">Anexo A — Inventario General de Activos Tecnológicos (RA-01)</div>
<table>
    <thead><tr><th>Código</th><th>Categoría</th><th>Ubicación</th><th>Estado Operativo</th></tr></thead>
    <tbody>
        @forelse($anexos['activos'] as $a)
            <tr>
                <td>{{ $a->codigo_patrimonial }}</td>
                <td>{{ $a->categoria->nombre_categoria ?? '-' }}</td>
                <td>{{ $a->ubicacion ? trim($a->ubicacion->seccion . ' ' . ($a->ubicacion->aula ?? '')) : 'Sin ubicación' }}</td>
                <td>{{ $a->estado_operativo }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="center">No hay activos registrados.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- Anexo B --}}
<div class="seccion-titulo" style="page-break-before: always; text-align: center;">Anexo B — Mantenimientos del Período (RA-02)</div>
<table>
    <thead><tr><th>Activo</th><th>Tipo</th><th>Programado</th><th>Ejecutado</th><th>Estado</th></tr></thead>
    <tbody>
        @forelse($anexos['mantenimientos_periodo'] as $m)
            <tr>
                <td>{{ $m->activo->nombre_equipo ?? 'Activo eliminado' }}</td>
                <td>{{ $m->tipo_mantenimiento }}</td>
                <td>{{ \Carbon\Carbon::parse($m->fecha_programada)->format('d/m/Y') }}</td>
                <td>{{ $m->fecha_ejecucion ? \Carbon\Carbon::parse($m->fecha_ejecucion)->format('d/m/Y') : 'No ejecutado' }}</td>
                <td>{{ $m->estado_mantenimiento }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="center">No hay mantenimientos programados en el período.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- Anexo C --}}
<div class="seccion-titulo" style="page-break-before: always; text-align: center;">Anexo C — Incidentes del Período (RA-03)</div>
<table>
    <thead><tr><th>Activo</th><th>Apertura</th><th>Cierre</th><th>Estado</th></tr></thead>
    <tbody>
        @forelse($anexos['incidentes_periodo'] as $i)
            <tr>
                <td>{{ $i->activo->nombre_equipo ?? 'Activo eliminado' }}</td>
                <td>{{ \Carbon\Carbon::parse($i->fecha_incidente)->format('d/m/Y H:i') }}</td>
                <td>{{ $i->fecha_cierre ? \Carbon\Carbon::parse($i->fecha_cierre)->format('d/m/Y H:i') : 'Sin cerrar' }}</td>
                <td>{{ $i->estado_incidente }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="center">No hay incidentes en el período.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- Anexo D --}}
<div class="seccion-titulo" style="page-break-before: always; text-align: center;">Anexo D — Detalle de la Muestra Verificada</div>
<table>
    <thead><tr><th>Activo</th><th>Nivel</th><th>Motivo</th><th class="center">Encontrado</th><th class="center">Ubicación OK</th><th class="center">Estado OK</th></tr></thead>
    <tbody>
        @forelse($auditoria->muestra as $m)
            <tr>
                <td>{{ $m->activo->nombre_equipo ?? 'Activo eliminado' }}</td>
                <td>{{ $m->nivel_riesgo_al_muestrear }}</td>
                <td>{{ $m->motivo_inclusion }}</td>
                <td class="center">{{ $m->encontrado === null ? '—' : ($m->encontrado ? 'Sí' : 'No') }}</td>
                <td class="center">{{ $m->ubicacion_coincide === null ? '—' : ($m->ubicacion_coincide ? 'Sí' : 'No') }}</td>
                <td class="center">{{ $m->estado_coincide === null ? '—' : ($m->estado_coincide ? 'Sí' : 'No') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="center">No se generó muestra.</td></tr>
        @endforelse
    </tbody>
</table>

{{-- Anexo C --}}
<div class="seccion-titulo" style="page-break-before: always; text-align: center;">Anexo E — Extracto de Bitácora del Período</div>
<table>
    <thead><tr><th>Fecha/Hora</th><th>Acción</th><th>Tabla Afectada</th></tr></thead>
    <tbody>
        @forelse($anexos['logs_periodo'] as $log)
            <tr><td>{{ \Carbon\Carbon::parse($log->fecha_hora)->format('d/m/Y H:i') }}</td><td>{{ $log->accion_realizada }}</td><td>{{ $log->tabla_afectada }}</td></tr>
        @empty
            <tr><td colspan="3" class="center">Sin registros de bitácora en el período.</td></tr>
        @endforelse
    </tbody>
</table>

@endsection
