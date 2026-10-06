@extends('informes.pdf.layout')

@section('titulo', 'RA-05 Matriz de Roles y Permisos del Sistema')
@section('subtitulo', 'Atiende Req. 9 (parcial) — Criterio: ' . implode(' · ', config('auditoria.criterios')['7.5']))

@section('contenido')

<p style="font-size:8pt;color:#475569;">
    Cubre solo el sistema de activos tecnológicos (SIATEC); no la red institucional ni otros sistemas. El "último
    acceso" se aproxima con la última actividad registrada en la bitácora del sistema, ya que no existe un registro
    dedicado de inicio de sesión.
</p>

<div class="seccion-titulo">Matriz Roles x Permisos (usuarios con el permiso activo)</div>
<table>
    <thead>
        <tr>
            <th>Rol</th><th class="center">Usuarios</th><th class="center">Dashboard</th><th class="center">Activos</th>
            <th class="center">Incidentes</th><th class="center">Riesgos</th><th class="center">Mant.</th>
            <th class="center">Informes</th><th class="center">Usuarios</th>
        </tr>
    </thead>
    <tbody>
        @foreach($datos['matriz'] as $fila)
            <tr>
                <td>{{ $fila['rol'] }}</td>
                <td class="center">{{ $fila['total_usuarios'] }}</td>
                @foreach($fila['permisos'] as $conteo)
                    <td class="center">{{ $conteo }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

<div class="seccion-titulo">Usuarios con Rol Administrador</div>
<table>
    <thead><tr><th>Nombre</th><th>Correo</th><th>Estado</th><th>Última Actividad Registrada</th></tr></thead>
    <tbody>
        @forelse($datos['admins'] as $u)
            <tr>
                <td>{{ $u['nombre'] }}</td>
                <td>{{ $u['correo'] }}</td>
                <td>{{ $u['estado'] }}</td>
                <td>{{ $u['ultima_actividad'] ? \Carbon\Carbon::parse($u['ultima_actividad'])->format('d/m/Y H:i') : 'Sin actividad registrada' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="center">Sin usuarios con rol Administrador.</td></tr>
        @endforelse
    </tbody>
</table>

@endsection
