@extends('layouts.principal')

@section('title', 'Modos de Falla - Gestión de Riesgos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Encabezado con Botón de Registro --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Modos de Falla (Reglas FMEA-MSR)</h2>
                <p class="text-sm text-slate-500">Define los modos de falla y asigna sus parámetros base de Severidad (Tabla 20) y Monitoreo MSR (Tabla 22)</p>
            </div>
        </div>

        <button onclick="toggleRegistroForm()" id="btnToggleForm" class="btn btn-primary self-start sm:self-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Registrar Modo de Falla
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
            <h3 class="text-base font-bold text-slate-900">Nuevo Modo de Falla</h3>
            <p class="text-xs text-slate-500 mt-0.5">Asocie el modo de falla y sus valores base de inferencia automatizada</p>
        </div>
        <form method="POST" action="{{ route('admin.riesgos.modos.store') }}" class="p-6">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Categoría de Activo <span class="text-rose-500">*</span></label>
                    <select name="fk_id_categoria" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        <option value="">-- Seleccionar Categoría --</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id_categoria }}" {{ old('fk_id_categoria') == $categoria->id_categoria ? 'selected' : '' }}>
                                {{ $categoria->nombre_categoria }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Nombre del Modo de Falla <span class="text-rose-500">*</span></label>
                    <input name="nombre_modo_falla" value="{{ old('nombre_modo_falla') }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" placeholder="Ej: Caída del sistema operativo, Falla de disco..." required />
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Severidad (Tabla 20) <span class="text-rose-500">*</span></label>
                    <select name="nivel_severidad" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        <option value="">-- Seleccionar nivel de severidad --</option>
                        @foreach($opcionesSeveridad as $valor => $etiqueta)
                            <option value="{{ $valor }}" {{ old('nivel_severidad') == $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Monitoreo MSR (Tabla 22) <span class="text-rose-500">*</span></label>
                    <select name="nivel_msr" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        <option value="">-- Seleccionar nivel de MSR --</option>
                        @foreach($opcionesMsr as $valor => $etiqueta)
                            <option value="{{ $valor }}" {{ old('nivel_msr') == $valor ? 'selected' : '' }}>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Justificación de Severidad <span class="text-slate-400 font-normal">(Opcional)</span></label>
                    <textarea name="justificacion_severidad" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" placeholder="Cite el informe de auditoría o entrevista que respalda esta elección...">{{ old('justificacion_severidad') }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Justificación de MSR <span class="text-slate-400 font-normal">(Opcional)</span></label>
                    <textarea name="justificacion_msr" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" placeholder="Cite el informe de auditoría o entrevista que respalda esta elección...">{{ old('justificacion_msr') }}</textarea>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 mt-5 pt-4 border-t border-slate-100">
                <button type="button" onclick="toggleRegistroForm()" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Guardar Modo de Falla
                </button>
            </div>
        </form>
    </div>

    {{-- Barra de Búsqueda --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.riesgos.modos') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input 
                    type="text" 
                    name="buscar" 
                    value="{{ request('buscar') }}" 
                    placeholder="Buscar por modo de falla o categoría..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                >
            </div>
            
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">
                    Buscar
                </button>

                @if(request('buscar'))
                    <a href="{{ route('admin.riesgos.modos') }}" class="btn btn-secondary">
                        ✕ Limpiar
                    </a>
                @endif
            </div>
        </form>

        @if(request('buscar'))
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                <span>Filtro activo para: <strong class="text-slate-800">"{{ request('buscar') }}"</strong></span>
                <span class="text-slate-300">•</span>
                <span>Resultados encontrados: <strong class="text-slate-800">{{ count($modos) }}</strong></span>
            </div>
        @endif
    </div>

    {{-- Tabla de Modos de Falla --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="w-16 px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Categoría del Activo</th>
                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Modo de Falla</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Severidad</th>
                    <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">MSR</th>
                    <th class="w-36 px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($modos as $modo)
                @php
                    $labelSeveridad = $modo->nivel_severidad ? ucwords(str_replace('_', ' ', $modo->nivel_severidad)) : null;
                    $labelMsr = $modo->nivel_msr ? ucwords(str_replace('_', ' ', $modo->nivel_msr)) : null;
                @endphp
                <tr class="hover:bg-slate-50/50 transition">
                    <td class="w-16 px-6 py-4 text-center text-sm text-slate-700 font-mono">{{ $modo->id_modo_falla }}</td>
                    <td class="px-6 py-4 text-center text-sm text-slate-900 font-semibold">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-100">
                            {{ $modo->categoria->nombre_categoria ?? '-' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-left text-sm text-slate-800 font-medium leading-relaxed">
                        {{ $modo->nombre_modo_falla }}
                    </td>
                    <td class="px-6 py-4 text-center text-sm font-bold text-rose-700">
                        {{ $labelSeveridad ?? '-' }} <span class="font-mono text-slate-400 font-normal">({{ $modo->valor_severidad_base ?? '-' }})</span>
                    </td>
                    <td class="px-6 py-4 text-center text-sm font-bold text-blue-700">
                        {{ $labelMsr ?? '-' }} <span class="font-mono text-slate-400 font-normal">({{ $modo->valor_msr_base ?? '-' }})</span>
                    </td>
                    <td class="w-36 px-6 py-4 text-center text-sm">
                        <button type="button"
                                onclick="openEditModal({{ $modo->id_modo_falla }}, '{{ $modo->fk_id_categoria }}', '{{ addslashes($modo->nombre_modo_falla) }}', '{{ $modo->nivel_severidad }}', '{{ $modo->nivel_msr }}', '{{ addslashes($modo->justificacion_severidad ?? '') }}', '{{ addslashes($modo->justificacion_msr ?? '') }}')"
                                class="btn btn-sm btn-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Editar
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                        @if(request('buscar'))
                            No se encontraron modos de falla que coincidan con la búsqueda.
                        @else
                            No hay modos de falla registrados todavía.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

<!-- Modal para Editar Modo de Falla -->
<div id="editModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 border border-slate-100 transform transition-all scale-95 opacity-0" id="editModalCard">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Editar Modo de Falla</h3>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 transition">✕</button>
        </div>

        <form id="editForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Categoría de Activo <span class="text-rose-500">*</span></label>
                    <select id="edit_fk_id_categoria" name="fk_id_categoria" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        <option value="">-- Seleccionar Categoría --</option>
                        @foreach($categorias as $categoria)
                            <option value="{{ $categoria->id_categoria }}">{{ $categoria->nombre_categoria }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nombre del Modo de Falla <span class="text-rose-500">*</span></label>
                    <input id="edit_nombre_modo_falla" name="nombre_modo_falla" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Severidad (Tabla 20) <span class="text-rose-500">*</span></label>
                    <select id="edit_nivel_severidad" name="nivel_severidad" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        <option value="">-- Seleccionar nivel de severidad --</option>
                        @foreach($opcionesSeveridad as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Monitoreo MSR (Tabla 22) <span class="text-rose-500">*</span></label>
                    <select id="edit_nivel_msr" name="nivel_msr" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        <option value="">-- Seleccionar nivel de MSR --</option>
                        @foreach($opcionesMsr as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Justificación de Severidad <span class="text-slate-400 font-normal">(Opcional)</span></label>
                    <textarea id="edit_justificacion_severidad" name="justificacion_severidad" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Justificación de MSR <span class="text-slate-400 font-normal">(Opcional)</span></label>
                    <textarea id="edit_justificacion_msr" name="justificacion_msr" rows="2" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-5 mt-4 border-t border-slate-100">
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-warning">
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

    function openEditModal(id, idCategoria, nombreModo, nivelSeveridad, nivelMsr, justificacionSeveridad, justificacionMsr) {
        var form = document.getElementById('editForm');
        form.action = '/admin/riesgos/modos-falla/' + id;

        document.getElementById('edit_fk_id_categoria').value = idCategoria;
        document.getElementById('edit_nombre_modo_falla').value = nombreModo;
        document.getElementById('edit_nivel_severidad').value = nivelSeveridad || '';
        document.getElementById('edit_nivel_msr').value = nivelMsr || '';
        document.getElementById('edit_justificacion_severidad').value = justificacionSeveridad || '';
        document.getElementById('edit_justificacion_msr').value = justificacionMsr || '';

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
