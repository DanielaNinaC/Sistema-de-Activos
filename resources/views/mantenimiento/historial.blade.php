@extends('layouts.principal')

@section('title','Mantenimiento - Historial')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold">Historial de mantenimiento</h2>
            <p class="text-sm text-gray-500">Consulta completa de los mantenimientos realizados y programados</p>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.mantenimiento.historial') }}" class="flex items-center gap-2 max-w-md">
        <div class="relative flex-1">
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por activo, tipo, estado, asignado..."
                class="w-full border border-gray-300 bg-white pl-10 pr-4 py-2.5 text-sm rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition" />
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <button type="submit" class="px-4 py-2.5 bg-slate-800 text-white text-sm rounded-xl hover:bg-slate-700 transition">Buscar</button>
        @if(request('buscar'))
            <a href="{{ route('admin.mantenimiento.historial') }}" class="px-3 py-2.5 text-sm text-gray-600 hover:text-gray-800 transition">Limpiar</a>
        @endif
    </form>

    <div class="bg-white rounded shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Programada</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asignado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ejecución</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Evidencia</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($mantenimientos as $mantenimiento)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">{{ \Carbon\Carbon::parse($mantenimiento->fecha_programada)->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $mantenimiento->activo->nombre_equipo ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $mantenimiento->usuarioAsignado->nombre ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $mantenimiento->tipo_mantenimiento }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $mantenimiento->estado_mantenimiento }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $mantenimiento->fecha_ejecucion ? \Carbon\Carbon::parse($mantenimiento->fecha_ejecucion)->format('d/m/Y') : '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $mantenimiento->evidencia_pdf ? 'Sí' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">No hay mantenimientos para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $mantenimientos->links() }}
    </div>
</div>
@endsection
