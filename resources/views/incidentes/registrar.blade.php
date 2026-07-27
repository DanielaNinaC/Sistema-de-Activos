@extends('layouts.principal')

@section('title','Incidentes - Registrar')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold">Incidentes</h2>
            <p class="text-sm text-gray-500">Registra incidentes y revisa los casos recientes</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg p-5 shadow">
            <h3 class="text-lg font-medium mb-4">Registrar incidente</h3>
            <form method="POST" action="{{ route('admin.incidentes.registrar.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
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
                    <label class="block text-sm mb-1">Usuario que reporta</label>
                    <select name="fk_id_usuario_reporta" class="w-full border px-3 py-2.5 text-sm rounded" required>
                        <option value="">Seleccione un usuario</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id_usuario }}">{{ $usuario->nombre }} {{ $usuario->apellido ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm mb-1">Fecha del incidente</label>
                    <input type="datetime-local" name="fecha_incidente" class="w-full border px-3 py-2.5 text-sm rounded" required />
                </div>
                <div>
                    <label class="block text-sm mb-1">Estado</label>
                    <select name="estado_incidente" class="w-full border px-3 py-2.5 text-sm rounded" required>
                        <option value="Reportado">Reportado</option>
                        <option value="En proceso">En proceso</option>
                        <option value="Resuelto">Resuelto</option>
                        <option value="Cerrado">Cerrado</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm mb-1">Descripción de la falla</label>
                    <textarea name="descripcion_falla" rows="4" class="w-full border px-3 py-2.5 text-sm rounded" required></textarea>
                </div>
                <div class="md:col-span-2 flex justify-end gap-3">
                    <a href="{{ route('admin.incidentes.historial') }}" class="inline-flex items-center px-4 py-2.5 rounded text-sm border border-gray-300 text-gray-700 hover:bg-gray-50">Ver historial</a>
                    <button class="bg-blue-600 text-white px-4 py-2.5 rounded text-sm">Guardar incidente</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold">Últimos incidentes</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($incidentes as $incidente)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ \Carbon\Carbon::parse($incidente->fecha_incidente)->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $incidente->activo->nombre_equipo ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $incidente->estado_incidente }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500">Aún no hay incidentes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
