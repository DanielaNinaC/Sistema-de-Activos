@extends('layouts.principal')

@section('title','Mantenimiento - Programar')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold">Mantenimiento</h2>
            <p class="text-sm text-gray-500">Programa y registra el mantenimiento de los activos</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg p-5 shadow">
            <h3 class="text-lg font-medium mb-4">Programar mantenimiento</h3>
            <form method="POST" action="{{ route('admin.mantenimiento.programar.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @csrf
                <div>
                    <label class="block text-sm mb-1">Activo</label>
                    <select name="fk_id_activo" class="w-full border px-3 py-2.5 text-sm rounded" required>
                        <option value="">Seleccione un activo</option>
                        @foreach($activos as $activo)
                            <option value="{{ $activo->id_activo }}">{{ $activo->id_activo }} - {{ $activo->nombre_equipo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Usuario asignado</label>
                    <select name="fk_id_usuario_asignado" class="w-full border px-3 py-2.5 text-sm rounded" required>
                        <option value="">Seleccione un usuario</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id_usuario }}">{{ $usuario->nombre }} {{ $usuario->apellido ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Evaluación origen</label>
                    <select name="id_evaluacion_origen" class="w-full border px-3 py-2.5 text-sm rounded">
                        <option value="">Sin evaluación de origen</option>
                        @foreach($evaluaciones as $evaluacion)
                            <option value="{{ $evaluacion->id_evaluacion }}">Evaluación #{{ $evaluacion->id_evaluacion }} - {{ $evaluacion->activo->nombre_equipo ?? 'Activo' }} - {{ $evaluacion->nivel_riesgo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Tipo</label>
                    <input name="tipo_mantenimiento" class="w-full border px-3 py-2.5 text-sm rounded" placeholder="Preventivo / Correctivo / Programado" required />
                </div>
                <div>
                    <label class="block text-sm mb-1">Estado</label>
                    <select name="estado_mantenimiento" class="w-full border px-3 py-2.5 text-sm rounded" required>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Programado">Programado</option>
                        <option value="En ejecución">En ejecución</option>
                        <option value="Finalizado">Finalizado</option>
                        <option value="Cancelado">Cancelado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Fecha programada</label>
                    <input name="fecha_programada" type="date" class="w-full border px-3 py-2.5 text-sm rounded" required />
                </div>
                <div>
                    <label class="block text-sm mb-1">Fecha de ejecución</label>
                    <input name="fecha_ejecucion" type="date" class="w-full border px-3 py-2.5 text-sm rounded" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Evidencia PDF</label>
                    <input type="file" name="evidencia_pdf" accept="application/pdf" class="w-full border px-3 py-2.5 text-sm rounded bg-white" />
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Descripción del trabajo</label>
                    <textarea name="descripcion_trabajo" rows="4" class="w-full border px-3 py-2.5 text-sm rounded"></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3">
                    <a href="{{ route('admin.mantenimiento.historial') }}" class="inline-flex items-center px-4 py-2.5 rounded text-sm border border-gray-300 text-gray-700 hover:bg-gray-50">Ver historial</a>
                    <button class="bg-blue-600 text-white px-4 py-2.5 rounded text-sm">Guardar mantenimiento</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold">Próximos mantenimientos</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($mantenimientos as $mantenimiento)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ \Carbon\Carbon::parse($mantenimiento->fecha_programada)->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $mantenimiento->activo->nombre_equipo ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $mantenimiento->tipo_mantenimiento }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $mantenimiento->estado_mantenimiento }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No hay mantenimientos registrados todavía.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
