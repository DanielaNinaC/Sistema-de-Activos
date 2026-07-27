@extends('layouts.principal')

@section('title','Riesgos - Alertas')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold">Alertas</h2>
            <p class="text-sm text-gray-500">Genera y revisa alertas asociadas a las evaluaciones de riesgo</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg p-5 shadow max-w-4xl mx-auto">
        <h3 class="text-lg font-medium mb-4">Registrar alerta</h3>
        <form method="POST" action="{{ route('admin.riesgos.alertas.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
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
                <label class="block text-sm mb-1">Tipo de alerta</label>
                <input name="tipo_alerta" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Fecha de generación</label>
                <input name="fecha_generacion" type="datetime-local" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div class="md:col-span-2 flex items-center gap-2">
                <input type="checkbox" name="notificacion_enviada" value="1" class="h-4 w-4" />
                <label class="text-sm text-gray-700">Notificación enviada</label>
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button class="bg-blue-600 text-white px-4 py-2.5 rounded text-sm">Guardar alerta</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Enviada</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($alertas as $alerta)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ \Carbon\Carbon::parse($alerta->fecha_generacion)->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $alerta->evaluacion->activo->nombre_equipo ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $alerta->tipo_alerta }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $alerta->notificacion_enviada ? 'Sí' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No hay alertas registradas todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $alertas->links() }}</div>
</div>
@endsection
