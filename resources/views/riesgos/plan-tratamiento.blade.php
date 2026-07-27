@extends('layouts.principal')

@section('title','Riesgos - Plan de Tratamiento')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold">Plan de tratamiento</h2>
            <p class="text-sm text-gray-500">Define estrategias y acciones preventivas para cada evaluación</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg p-5 shadow max-w-6xl mx-auto">
        <h3 class="text-lg font-medium mb-4">Registrar tratamiento</h3>
        <form method="POST" action="{{ route('admin.riesgos.tratamiento.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-sm mb-1">Evaluación</label>
                <select name="fk_id_evaluacion" class="w-full border px-3 py-2.5 text-sm rounded" required>
                    <option value="">Seleccione una evaluación</option>
                    @foreach($evaluaciones as $evaluacion)
                        <option value="{{ $evaluacion->id_evaluacion }}">#{{ $evaluacion->id_evaluacion }} - {{ $evaluacion->activo->nombre_equipo ?? 'Activo' }} - {{ $evaluacion->nivel_riesgo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">Estrategia</label>
                <input name="estrategia" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Estado</label>
                <select name="estado_tratamiento" class="w-full border px-3 py-2.5 text-sm rounded" required>
                    <option value="Pendiente">Pendiente</option>
                    <option value="En proceso">En proceso</option>
                    <option value="Completado">Completado</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm mb-1">Acción preventiva</label>
                <textarea name="accion_preventiva" rows="4" class="w-full border px-3 py-2.5 text-sm rounded" required></textarea>
            </div>
            <div>
                <label class="block text-sm mb-1">Fecha compromiso</label>
                <input name="fecha_compromiso" type="date" class="w-full border px-3 py-2.5 text-sm rounded" />
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button class="bg-blue-600 text-white px-4 py-2.5 rounded text-sm">Guardar tratamiento</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Evaluación</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estrategia</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Compromiso</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tratamientos as $tratamiento)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700">#{{ $tratamiento->evaluacion->id_evaluacion ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $tratamiento->evaluacion->activo->nombre_equipo ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $tratamiento->estrategia }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $tratamiento->estado_tratamiento }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $tratamiento->fecha_compromiso ? \Carbon\Carbon::parse($tratamiento->fecha_compromiso)->format('d/m/Y') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No hay planes de tratamiento registrados todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $tratamientos->links() }}</div>
</div>
@endsection
