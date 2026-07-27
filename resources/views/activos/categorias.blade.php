@extends('layouts.principal')

@section('title','Categorías - Activos Tecnológicos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition" aria-label="Volver a la pantalla anterior" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold">Categorías</h2>
            <p class="text-sm text-gray-500">Administración de categorías de activos tecnológicos</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg p-5 shadow max-w-4xl mx-auto">
        <h3 class="text-lg font-medium mb-4">Registrar categoría</h3>
        <form method="POST" action="{{ route('admin.activos.categorias.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @csrf
            <div class="md:col-span-2">
                <label class="block text-sm mb-1">Nombre de la categoría</label>
                <input name="nombre_categoria" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Vida útil en meses</label>
                <input name="vida_util_meses" type="number" min="1" class="w-full border px-3 py-2.5 text-sm rounded" />
            </div>
            <div class="flex items-end justify-end">
                <button class="bg-blue-600 text-white px-4 py-2.5 rounded text-sm">Guardar categoría</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold">Listado de categorías</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vida útil (meses)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($categorias as $categoria)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $categoria->id_categoria }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $categoria->nombre_categoria }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $categoria->vida_util_meses ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500">No hay categorías registradas todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
