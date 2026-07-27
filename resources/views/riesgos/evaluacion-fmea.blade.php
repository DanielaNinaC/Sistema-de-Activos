@extends('layouts.principal')

@section('title','Riesgos - Evaluación FMEA')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold">Evaluación FMEA</h2>
            <p class="text-sm text-gray-500">Calcula el score de riesgo según severidad, frecuencia y MSR</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg p-5 shadow max-w-6xl mx-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">Registrar evaluación</h3>
            <div class="text-sm text-gray-500">
                Peso actual:
                @if($ultimoPeso)
                    S {{ $ultimoPeso->peso_severidad }} | F {{ $ultimoPeso->peso_frecuencia }} | M {{ $ultimoPeso->peso_msr }}
                @else
                    usando valores por defecto
                @endif
            </div>
        </div>
        <form method="POST" action="{{ route('admin.riesgos.evaluacion.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
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
                <label class="block text-sm mb-1">Modo de falla</label>
                <select name="fk_id_modo_falla" class="w-full border px-3 py-2.5 text-sm rounded" required>
                    <option value="">Seleccione un modo</option>
                    @foreach($modos as $modo)
                        <option value="{{ $modo->id_modo_falla }}">{{ $modo->nombre_modo_falla }} ({{ $modo->categoria->nombre_categoria ?? '-' }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">Severidad</label>
                <input name="valor_severidad" type="number" min="1" max="10" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Frecuencia</label>
                <input name="valor_frecuencia" type="number" min="1" max="10" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div>
                <label class="block text-sm mb-1">MSR</label>
                <input name="valor_msr" type="number" min="1" max="10" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Fecha de evaluación</label>
                <input name="fecha_evaluacion" type="datetime-local" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div class="md:col-span-2 flex justify-end gap-3">
                <a href="{{ route('admin.riesgos.score') }}" class="inline-flex items-center px-4 py-2.5 rounded text-sm border border-gray-300 text-gray-700 hover:bg-gray-50">Ver score</a>
                <button class="bg-blue-600 text-white px-4 py-2.5 rounded text-sm">Guardar evaluación</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold">Evaluaciones recientes</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nivel</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($evaluaciones as $evaluacion)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ \Carbon\Carbon::parse($evaluacion->fecha_evaluacion)->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $evaluacion->activo->nombre_equipo ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $evaluacion->modoFalla->nombre_modo_falla ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $evaluacion->score_calculado }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $evaluacion->nivel_riesgo }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No hay evaluaciones registradas todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $evaluaciones->links() }}</div>
</div>
@endsection
