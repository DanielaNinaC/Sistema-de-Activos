@extends('layouts.principal')

@section('title', 'RA-05 Matriz de Roles y Permisos del Sistema')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <h2 class="text-2xl font-bold text-slate-900">RA-05 &middot; Matriz de Roles y Permisos del Sistema</h2>
    </div>

    @include('informes.auditoria-ti._encabezado', [
        'titulo' => 'Matriz de Roles y Permisos del Sistema',
        'requerimiento' => 'Req. 9 (parcial)',
        'criterio' => config('auditoria.criterios')['7.5'],
        'filtros' => $filtros,
    ])

    <p class="text-[11px] text-amber-700 bg-amber-50 border border-amber-100 rounded-xl px-4 py-2">
        Cubre solo el sistema de activos tecnológicos (SIATEC); no la red institucional ni otros sistemas.
        El "último acceso" se aproxima con la última actividad registrada en la bitácora del sistema, ya que no existe
        un registro dedicado de inicio de sesión.
    </p>

    <div class="flex justify-end gap-2">
        <a href="{{ route('admin.informes.auditoria-ti.ra05.pdf', $filtros) }}" class="btn btn-secondary" target="_blank">Exportar PDF</a>
        <a href="{{ route('admin.informes.auditoria-ti.ra05.excel', $filtros) }}" class="btn btn-secondary">Exportar Excel</a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200">
            <h3 class="text-sm font-bold text-slate-900">Matriz Roles &times; Permisos (usuarios con el permiso activo)</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Rol</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-600 uppercase">Usuarios</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-600 uppercase">Dashboard</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-600 uppercase">Activos</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-600 uppercase">Incidentes</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-600 uppercase">Riesgos</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-600 uppercase">Mantenimiento</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-600 uppercase">Informes</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-slate-600 uppercase">Usuarios</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($datos['matriz'] as $fila)
                        <tr>
                            <td class="px-4 py-2.5 font-semibold">{{ $fila['rol'] }}</td>
                            <td class="px-4 py-2.5 text-center font-mono">{{ $fila['total_usuarios'] }}</td>
                            @foreach($fila['permisos'] as $conteo)
                                <td class="px-4 py-2.5 text-center font-mono">{{ $conteo }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200">
            <h3 class="text-sm font-bold text-slate-900">Usuarios con Rol Administrador</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Nombre</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Correo</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Estado</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-slate-600 uppercase">Última Actividad Registrada</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($datos['admins'] as $u)
                    <tr>
                        <td class="px-4 py-2.5">{{ $u['nombre'] }}</td>
                        <td class="px-4 py-2.5">{{ $u['correo'] }}</td>
                        <td class="px-4 py-2.5">{{ $u['estado'] }}</td>
                        <td class="px-4 py-2.5">{{ $u['ultima_actividad'] ? \Carbon\Carbon::parse($u['ultima_actividad'])->format('d/m/Y H:i') : 'Sin actividad registrada' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">Sin usuarios con rol Administrador.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
