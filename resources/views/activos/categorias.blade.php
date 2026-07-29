@extends('layouts.principal')

@section('title','Categorías - Activos Tecnológicos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-2xl font-semibold text-slate-900">Categorías</h2>
        <button onclick="toggleRegistroForm()" id="btnToggleForm" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition shadow-md shadow-blue-200 self-start sm:self-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Registrar Nueva Categoría
        </button>
    </div>

    {{-- Alerta de éxito --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 flex items-center gap-3 shadow-sm transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Errores de validación --}}
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm">
            <ul class="text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li class="flex items-center gap-2">
                        <span class="text-rose-500">•</span>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Formulario de Registro (colapsable) --}}
    <div id="registroForm" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden transition-all duration-300" style="display: none;">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
            <h3 class="text-base font-semibold text-slate-800">Nueva Categoría</h3>
            <p class="text-xs text-slate-500 mt-0.5">Complete los datos para registrar una nueva categoría</p>
        </div>
        <form method="POST" action="{{ route('admin.activos.categorias.store') }}" class="p-6">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-1">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nombre de la categoría <span class="text-rose-500">*</span></label>
                    <input name="nombre_categoria" value="{{ old('nombre_categoria') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" placeholder="Ej: Computadora, Impresora..." required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Vida útil</label>
                    <input name="vida_util_valor" type="number" min="1" value="{{ old('vida_util_valor') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" placeholder="Ej: 5" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Unidad</label>
                    <select name="vida_util_unidad" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                        <option value="meses" {{ old('vida_util_unidad') == 'meses' ? 'selected' : '' }}>Meses</option>
                        <option value="anos" {{ old('vida_util_unidad') == 'anos' ? 'selected' : '' }}>Años</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 mt-5 pt-4 border-t border-slate-100">
                <button type="button" onclick="toggleRegistroForm()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl text-sm transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-sm transition shadow-sm flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Guardar Categoría
                </button>
            </div>
        </form>
    </div>

    {{-- Barra de Búsqueda --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.activos.categorias') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search ?? '' }}" 
                    placeholder="Buscar por nombre de categoría..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                >
            </div>
            
            <div class="flex items-center gap-2">
                <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-sm transition shadow-sm flex items-center gap-2">
                    Buscar
                </button>

                @if(!empty($search))
                    <a href="{{ route('admin.activos.categorias') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl text-sm transition flex items-center gap-1">
                        ✕ Limpiar
                    </a>
                @endif
            </div>
        </form>

        @if(!empty($search))
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                <span>Filtro activo para: <strong class="text-slate-800">"{{ $search }}"</strong></span>
                <span class="text-slate-300">•</span>
                <span>Resultados encontrados: <strong class="text-slate-800">{{ $categorias->count() }}</strong></span>
            </div>
        @endif
    </div>

    {{-- Tabla de Categorías --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Nombre</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Vida Útil</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Dispositivos</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($categorias as $categoria)
                <tr class="hover:bg-slate-50/50 transition">
                    <td class="px-6 py-4 text-center text-sm text-slate-700 font-mono">{{ $categoria->id_categoria }}</td>
                    <td class="px-6 py-4 text-center text-sm text-slate-900 font-semibold">{{ $categoria->nombre_categoria }}</td>
                    <td class="px-6 py-4 text-center text-sm text-slate-700">
                        @if($categoria->vida_util_meses)
                            @php
                                $anios = floor($categoria->vida_util_meses / 12);
                                $mesesRestantes = $categoria->vida_util_meses % 12;
                            @endphp
                            @if($anios > 0 && $mesesRestantes > 0)
                                {{ $anios }} {{ $anios == 1 ? 'año' : 'años' }} y {{ $mesesRestantes }} {{ $mesesRestantes == 1 ? 'mes' : 'meses' }}
                            @elseif($anios > 0)
                                {{ $anios }} {{ $anios == 1 ? 'año' : 'años' }}
                            @else
                                {{ $mesesRestantes }} {{ $mesesRestantes == 1 ? 'mes' : 'meses' }}
                            @endif
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center text-sm text-slate-700 font-medium font-mono">
                        {{ $categoria->activos_count }}
                    </td>
                    <td class="px-6 py-4 text-center text-sm">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.activos.categorias.equipos', $categoria->id_categoria) }}" class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-xl transition text-xs font-semibold shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                                </svg>
                                Ver Equipos
                            </a>
                            <button type="button" onclick="openEditModal({{ $categoria->id_categoria }}, '{{ addslashes($categoria->nombre_categoria) }}', {{ $categoria->vida_util_meses ?? 'null' }})" class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-xl transition text-xs font-semibold shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Editar
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                        No hay categorías registradas todavía.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Editar Categoría -->
<div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 border border-slate-100 transform transition-all scale-95 opacity-0" id="editModalCard">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Editar Categoría</h3>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 transition">✕</button>
        </div>

        <form id="editForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nombre de la categoría <span class="text-rose-500">*</span></label>
                    <input id="edit_nombre_categoria" name="nombre_categoria" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Vida útil</label>
                        <input id="edit_vida_util_valor" name="vida_util_valor" type="number" min="1" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" placeholder="Ej: 5" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Unidad</label>
                        <select id="edit_vida_util_unidad" name="vida_util_unidad" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                            <option value="meses">Meses</option>
                            <option value="anos">Años</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-5 mt-4 border-t border-slate-100">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 text-xs font-semibold text-white bg-amber-500 hover:bg-amber-600 rounded-xl shadow-sm transition">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleRegistroForm() {
        var form = document.getElementById('registroForm');
        var btn = document.getElementById('btnToggleForm');
        if (form.style.display === 'none') {
            form.style.display = 'block';
            btn.style.display = 'none';
        } else {
            form.style.display = 'none';
            btn.style.display = 'inline-flex';
        }
    }

    function openEditModal(id, nombre, vidaUtilMeses) {
        var form = document.getElementById('editForm');
        form.action = '/admin/activos/categorias/' + id;

        document.getElementById('edit_nombre_categoria').value = nombre;

        var valInput = document.getElementById('edit_vida_util_valor');
        var unitSelect = document.getElementById('edit_vida_util_unidad');

        if (vidaUtilMeses && vidaUtilMeses !== 'null') {
            var meses = parseInt(vidaUtilMeses);
            if (meses % 12 === 0 && meses > 0) {
                valInput.value = meses / 12;
                unitSelect.value = 'anos';
            } else {
                valInput.value = meses;
                unitSelect.value = 'meses';
            }
        } else {
            valInput.value = '';
            unitSelect.value = 'anos';
        }

        var modal = document.getElementById('editModal');
        var card = document.getElementById('editModalCard');
        modal.classList.remove('hidden');
        setTimeout(function () {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeEditModal() {
        var modal = document.getElementById('editModal');
        var card = document.getElementById('editModalCard');
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(function () {
            modal.classList.add('hidden');
        }, 150);
    }

    @if($errors->any())
        document.addEventListener('DOMContentLoaded', function() {
            toggleRegistroForm();
        });
    @endif
</script>
@endsection
