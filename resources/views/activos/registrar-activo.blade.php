@extends('layouts.principal')

@section('title','Registrar Activo - Activos Tecnológicos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-700 hover:bg-gray-100 transition" aria-label="Volver a la pantalla anterior" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold">Registrar Activo Tecnológico</h2>
            <p class="text-sm text-gray-500">Captura los datos del nuevo activo para incorporarlo al inventario</p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg p-5 shadow max-w-6xl mx-auto">
        <h3 class="text-lg font-medium mb-4">Formulario de registro</h3>
        <form method="POST" action="{{ route('admin.activos.registrar.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
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
                <label class="block text-sm mb-1">Ubicación</label>
                <select name="fk_id_ubicacion" class="w-full border px-3 py-2.5 text-sm rounded" required>
                    <option value="">Seleccione una ubicación</option>
                    @foreach($ubicaciones as $ubicacion)
                        <option value="{{ $ubicacion->id_ubicacion }}">{{ $ubicacion->seccion }} - {{ $ubicacion->aula }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">Código patrimonial</label>
                <input name="codigo_patrimonial" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Nombre del equipo</label>
                <input name="nombre_equipo" class="w-full border px-3 py-2.5 text-sm rounded" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Marca</label>
                <input name="marca" class="w-full border px-3 py-2.5 text-sm rounded" />
            </div>
            <div>
                <label class="block text-sm mb-1">Modelo</label>
                <input name="modelo" class="w-full border px-3 py-2.5 text-sm rounded" />
            </div>
            <div>
                <label class="block text-sm mb-1">Fecha de adquisición</label>
                <input name="fecha_adquisicion" type="number" min="1900" max="2099" class="w-full border px-3 py-2.5 text-sm rounded" />
            </div>
            <div>
                <label class="block text-sm mb-1">Estado operativo</label>
                <input name="estado_operativo" class="w-full border px-3 py-2.5 text-sm rounded" value="Operativo" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Etapa ciclo de vida</label>
                <input name="etapa_ciclo_vida" class="w-full border px-3 py-2.5 text-sm rounded" value="Operación" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Criticidad institucional</label>
                <input name="criticidad_institucional" class="w-full border px-3 py-2.5 text-sm rounded" />
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm mb-1">Observaciones</label>
                <textarea name="observaciones" rows="3" class="w-full border px-3 py-2.5 text-sm rounded"></textarea>
            </div>
            <div class="md:col-span-2 flex justify-end gap-3">
                <a href="{{ route('admin.activos.inventario') }}" class="inline-flex items-center px-4 py-2.5 rounded text-sm border border-gray-300 text-gray-700 hover:bg-gray-50">Ver inventario</a>
                <button class="bg-blue-600 text-white px-4 py-2.5 rounded text-sm">Guardar activo</button>
            </div>
        </form>
    </div>
</div>
@endsection
