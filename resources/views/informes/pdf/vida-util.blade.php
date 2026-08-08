@extends('informes.pdf.layout')

@section('titulo', 'Vida Útil Remanente del Inventario')
@section('subtitulo', 'Activos ordenados por porcentaje de vida útil consumida')

@section('contenido')
<table>
    <thead>
        <tr>
            <th>Código</th>
            <th>Activo</th>
            <th>Categoría</th>
            <th class="center">Antigüedad</th>
            <th class="center">Vida Útil</th>
            <th class="center">Meses Restantes</th>
            <th class="center">% Consumido</th>
        </tr>
    </thead>
    <tbody>
        @forelse($activos as $activo)
            <tr>
                <td>{{ $activo->codigo_patrimonial }}</td>
                <td>{{ $activo->nombre_equipo }}</td>
                <td>{{ $activo->categoria->nombre_categoria ?? '-' }}</td>
                <td class="center">{{ $activo->vida_util['antiguedad_meses'] }}m</td>
                <td class="center">{{ $activo->vida_util['vida_util_meses'] }}m</td>
                <td class="center">{{ $activo->vida_util['meses_restantes'] }}m</td>
                <td class="center">{{ $activo->vida_util['porcentaje_consumido'] }}%</td>
            </tr>
        @empty
            <tr><td colspan="7" class="center">No hay activos que coincidan con los filtros aplicados.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
