@extends('informes.pdf.layout')

@section('titulo', 'RA-06 Hoja de Verificación de Activos por Ubicación')
@section('subtitulo', $ubicacionSeleccionada->seccion . ($ubicacionSeleccionada->aula ? ' - ' . $ubicacionSeleccionada->aula : '') . ' — Atiende Req. 19 (parcial)')

@section('contenido')

<table>
    <thead>
        <tr>
            <th>Código</th><th>Activo</th><th>Categoría</th><th class="center">Encontrado (Sí/No)</th><th>Estado Observado</th><th>Observación</th>
        </tr>
    </thead>
    <tbody>
        @forelse($activos as $a)
            <tr>
                <td>{{ $a->codigo_patrimonial }}</td>
                <td>{{ $a->nombre_equipo }}</td>
                <td>{{ $a->categoria->nombre_categoria ?? '-' }}</td>
                <td class="center">&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        @empty
            <tr><td colspan="6" class="center">Sin activos registrados en esta ubicación.</td></tr>
        @endforelse
    </tbody>
</table>

<p style="margin-top:24px;font-size:8pt;">Verificado por: ______________________________&nbsp;&nbsp;&nbsp;&nbsp;Fecha: ____________</p>

@endsection
