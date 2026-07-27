@extends('layouts.principal')

@section('title','Riesgos - Modos de Falla')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold">Modos de Falla</h2>
            <p class="text-sm text-gray-500">Define los modos de falla por categoría de activo</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-lg p-5 shadow max-w-4xl mx-auto">
        <h3 class="text-lg font-medium mb-4">Registrar modo de falla</h3>
        <form method="POST" action="{{ route('admin.riesgos.modos.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @csrf
            <div>
                <label class="block text-sm mb-1">Categoría</label>
                <select name="fk_id_categoria" class="w-full border px-3 py-2.5 text-sm rounded" required>
                    <option value="">Seleccione una categoría</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id_categoria }}">{{ $categoria->nombre_categoria }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">Nombre del modo de falla</label>
                <input name="nombre_modo_falla" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button class="bg-blue-600 text-white px-4 py-2.5 rounded text-sm">Guardar modo</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold">Listado de modos de falla</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoría</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modo de falla</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($modos as $modo)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $modo->id_modo_falla }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $modo->categoria->nombre_categoria ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $modo->nombre_modo_falla }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500">No hay modos de falla registrados todavía.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
