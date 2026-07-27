@extends('layouts.principal')

@section('title','Inventario - Activos Tecnológicos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition" aria-label="Volver a la pantalla anterior" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold">Inventario de Activos Tecnológicos</h2>
            <p class="text-sm text-gray-500">Registro y consulta de los activos tecnológicos de la institución</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-4 flex-wrap">
        <form method="GET" action="{{ route('admin.activos.inventario') }}" class="flex items-center gap-2 flex-1 max-w-md">
            <div class="relative flex-1">
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por código, equipo, categoría, ubicación..."
                    class="w-full border border-gray-300 bg-white pl-10 pr-4 py-2.5 text-sm rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition" />
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button type="submit" class="px-4 py-2.5 bg-slate-800 text-white text-sm rounded-xl hover:bg-slate-700 transition">Buscar</button>
            @if(request('buscar'))
                <a href="{{ route('admin.activos.inventario') }}" class="px-3 py-2.5 text-sm text-gray-600 hover:text-gray-800 transition">Limpiar</a>
            @endif
        </form>
        <a href="{{ route('admin.activos.registrar') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm bg-blue-600 text-white hover:bg-blue-700 transition">Registrar nuevo activo</a>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold">Listado de activos</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Equipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ubicación</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Etapa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Criticidad</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($activos as $activo)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $activo->id_activo }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $activo->codigo_patrimonial }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $activo->nombre_equipo }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $activo->categoria->nombre_categoria ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $activo->ubicacion->seccion ?? '-' }} {{ $activo->ubicacion->aula ? '- ' . $activo->ubicacion->aula : '' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $activo->estado_operativo }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $activo->etapa_ciclo_vida }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $activo->criticidad_institucional ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">No hay activos registrados todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $activos->links() }}
    </div>
</div>
@endsection
