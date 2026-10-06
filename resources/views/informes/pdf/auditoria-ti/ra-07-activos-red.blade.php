@extends('informes.pdf.layout')

@section('titulo', 'RA-07 Activos de Red por Ubicación')
@section('subtitulo', 'Atiende Req. 20 (parcial) — Criterio: ' . implode(' · ', config('auditoria.criterios')['7.1']))

@section('contenido')

@if(!$datos['configurado'])
    <p style="font-size:8.5pt;color:#9a3412;">
        No se ha configurado la lista de categorías de red (config('auditoria.categorias_red')). Este reporte
        queda vacío hasta que se indiquen los IDs de categoría correspondientes a equipos de red.
    </p>
@else
    @forelse($datos['por_ubicacion'] as $ubicacion => $activos)
        <div class="seccion-titulo">{{ $ubicacion }}</div>
        <table>
            <thead><tr><th>Código</th><th>Categoría</th><th>Estado Operativo</th></tr></thead>
            <tbody>
                @foreach($activos as $a)
                    <tr><td>{{ $a->codigo_patrimonial }}</td><td>{{ $a->categoria->nombre_categoria ?? '-' }}</td><td>{{ $a->estado_operativo }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p class="center">No hay activos registrados en las categorías de red configuradas.</p>
    @endforelse
@endif

@endsection
