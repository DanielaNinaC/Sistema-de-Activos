@extends('layouts.principal')

@section('title','Riesgos - Score')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold">Score de Riesgo</h2>
            <p class="text-sm text-gray-500">Configura los pesos AHP y revisa el resultado de las evaluaciones</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg p-5 shadow max-w-4xl mx-auto">
        <h3 class="text-lg font-medium mb-4">Actualizar pesos AHP</h3>
        <form method="POST" action="{{ route('admin.riesgos.score.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @csrf
            <div>
                <label class="block text-sm mb-1">Peso severidad</label>
                <input name="peso_severidad" type="number" step="0.001" min="0" max="1" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Peso frecuencia</label>
                <input name="peso_frecuencia" type="number" step="0.001" min="0" max="1" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Peso MSR</label>
                <input name="peso_msr" type="number" step="0.001" min="0" max="1" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Fecha de actualización</label>
                <input name="fecha_actualizacion" type="datetime-local" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button class="bg-blue-600 text-white px-4 py-2.5 rounded text-sm">Guardar pesos</button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @forelse($pesos->take(3) as $peso)
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm text-gray-500">Actualizado</div>
                <div class="text-lg font-semibold text-gray-900">{{ \Carbon\Carbon::parse($peso->fecha_actualizacion)->format('d/m/Y H:i') }}</div>
                <div class="mt-2 text-sm text-gray-700">S: {{ $peso->peso_severidad }} | F: {{ $peso->peso_frecuencia }} | M: {{ $peso->peso_msr }}</div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow p-4 md:col-span-3 text-sm text-gray-500">No hay pesos registrados todavía.</div>
        @endforelse
    </div>
</div>
@endsection
