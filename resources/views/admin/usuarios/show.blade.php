@extends('layouts.principal')

@section('title','Ver Usuario')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition" aria-label="Volver a la pantalla anterior" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold">Detalle del usuario</h2>
            <p class="text-sm text-gray-500">Información general y registro de actividad</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 bg-white rounded-lg shadow p-6">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-full bg-slate-900 text-white flex items-center justify-center text-2xl font-bold">
                    {{ strtoupper(substr($usuario->nombre ?? 'U', 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-gray-900">{{ $usuario->nombre }} {{ $usuario->apellido ?? '' }}</h3>
                    <p class="text-sm text-gray-500">{{ $usuario->rol->nombre_rol ?? 'Sin rol' }}</p>
                </div>
            </div>

            <div class="space-y-4 text-sm">
                <div>
                    <p class="text-gray-500">Correo</p>
                    <p class="font-medium text-gray-900 break-words">{{ $usuario->correo }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Estado</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $usuario->estado ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $usuario->estado ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
                <div>
                    <p class="text-gray-500">Rol</p>
                    <p class="font-medium text-gray-900">{{ $usuario->rol->nombre_rol ?? 'Sin rol' }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Total de logs</p>
                    <p class="font-medium text-gray-900">{{ $usuario->logs->count() }}</p>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Logs de actividad</h3>
                    <p class="text-sm text-gray-500">Todos los movimientos registrados por esta persona</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acción</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tabla</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registro</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($usuario->logs as $log)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($log->fecha_hora)->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $log->accion_realizada }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $log->tabla_afectada }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $log->registro_id ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                                    Esta persona todavía no tiene logs registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
