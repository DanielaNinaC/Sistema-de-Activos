@extends('layouts.principal')

@section('title', 'Lista de Usuarios')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-2xl font-semibold text-slate-900">Lista de Usuarios</h2>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.usuarios.create') }}" class="btn btn-primary self-start sm:self-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Registrar Nuevo Usuario
            </a>
        </div>
    </div>

    {{-- Barra de Búsqueda --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.usuarios.index') }}" class="flex flex-col sm:flex-row gap-3">
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
                    placeholder="Buscar por nombre, correo o rol..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                >
            </div>
            
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-dark">
                    Buscar
                </button>

                @if(!empty($search))
                    <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">
                        ✕ Limpiar
                    </a>
                @endif
            </div>
        </form>

        @if(!empty($search))
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                <span>Filtro activo para: <strong class="text-slate-800">"{{ $search }}"</strong></span>
                <span class="text-slate-300">•</span>
                <span>Resultados encontrados: <strong class="text-slate-800">{{ $usuarios->total() }}</strong></span>
            </div>
        @endif
    </div>

    {{-- Tabla de Usuarios --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-16 px-4 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Nombre</th>
                        <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Correo</th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Rol</th>
                        <th class="px-4 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider whitespace-nowrap">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($usuarios as $u)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="w-16 px-4 py-3.5 text-center text-sm text-slate-700 font-mono">{{ $u->id_usuario }}</td>
                        <td class="px-5 py-3.5 text-left text-sm text-slate-900 font-semibold whitespace-nowrap">{{ $u->nombre }} {{ $u->apellido ?? '' }}</td>
                        <td class="px-5 py-3.5 text-left text-sm text-slate-600 font-mono">{{ $u->correo }}</td>
                        <td class="px-4 py-3.5 text-center text-sm whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-slate-100 text-slate-700">
                                {{ $u->rol->nombre_rol ?? 'Sin rol' }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-center text-sm whitespace-nowrap">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $u->estado ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-200' }}">
                                {{ $u->estado ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-center text-sm whitespace-nowrap">
                            <div class="inline-flex items-center justify-center gap-2">
                                <a href="{{ route('admin.usuarios.show', $u->id_usuario) }}" class="btn btn-sm btn-primary">
                                    Ver
                                </a>
                                <a href="{{ route('admin.usuarios.edit', $u->id_usuario) }}" class="btn btn-sm btn-warning">
                                    Editar
                                </a>
                                <form id="user-status-form-{{ $u->id_usuario }}" action="{{ route('admin.usuarios.destroy', $u->id_usuario) }}" method="POST" class="inline-flex m-0 p-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" 
                                            onclick="openConfirmModal('user-status-form-{{ $u->id_usuario }}', {{ $u->estado ? 'true' : 'false' }}, '{{ addslashes($u->nombre . ' ' . ($u->apellido ?? '')) }}')"
                                            class="btn btn-sm btn-dark whitespace-nowrap">
                                        {{ $u->estado ? 'Desactivar cuenta' : 'Activar cuenta' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            No se encontraron usuarios que coincidan con la búsqueda.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $usuarios->links() }}
    </div>
</div>

<!-- Modal Confirmación Personalizado -->
<div id="confirmModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5 border border-slate-100 transform transition-all scale-95 opacity-0" id="confirmModalCard">
        <div class="flex items-center gap-4">
            <div id="modalIconContainer" class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h3 id="modalTitle" class="text-lg font-bold text-slate-900">Confirmación</h3>
                <p id="modalDescription" class="text-xs text-slate-500 mt-0.5">Gestión de acceso de usuario</p>
            </div>
        </div>

        <p class="text-sm text-slate-600 leading-relaxed" id="modalText">
            ¿Está seguro de realizar esta acción en la cuenta de usuario?
        </p>

        <div class="flex items-center justify-end gap-3 pt-2">
            <button type="button" onclick="closeConfirmModal()" class="btn btn-secondary">
                Cancelar
            </button>
            <button type="button" id="confirmSubmitBtn" onclick="submitConfirmedForm()" class="btn btn-danger">
                Confirmar
            </button>
        </div>
    </div>
</div>

<script>
    let pendingFormId = null;

    function openConfirmModal(formId, isDeactivating, userName) {
        pendingFormId = formId;
        var modal = document.getElementById('confirmModal');
        var card = document.getElementById('confirmModalCard');
        var title = document.getElementById('modalTitle');
        var desc = document.getElementById('modalDescription');
        var text = document.getElementById('modalText');
        var iconContainer = document.getElementById('modalIconContainer');
        var confirmBtn = document.getElementById('confirmSubmitBtn');

        if (isDeactivating) {
            title.innerText = 'Desactivar Cuenta';
            desc.innerText = 'Restricción de acceso';
            text.innerText = '¿Está seguro de que desea desactivar la cuenta de ' + userName + '? El usuario no podrá acceder al sistema.';
            iconContainer.className = 'w-12 h-12 rounded-xl flex items-center justify-center shrink-0 bg-rose-100 text-rose-600';
            confirmBtn.className = 'btn btn-danger';
            confirmBtn.innerText = 'Desactivar';
        } else {
            title.innerText = 'Activar Cuenta';
            desc.innerText = 'Habilitar acceso';
            text.innerText = '¿Está seguro de que desea activar la cuenta de ' + userName + '? El usuario podrá acceder normalmente al sistema.';
            iconContainer.className = 'w-12 h-12 rounded-xl flex items-center justify-center shrink-0 bg-emerald-100 text-emerald-600';
            confirmBtn.className = 'btn btn-success';
            confirmBtn.innerText = 'Activar';
        }

        modal.classList.remove('hidden');
        setTimeout(function () {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeConfirmModal() {
        var modal = document.getElementById('confirmModal');
        var card = document.getElementById('confirmModalCard');
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(function () {
            modal.classList.add('hidden');
            pendingFormId = null;
        }, 150);
    }

    function submitConfirmedForm() {
        if (pendingFormId) {
            var form = document.getElementById(pendingFormId);
            if (form) {
                var btn = document.getElementById('confirmSubmitBtn');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="inline-block animate-spin mr-1.5">&#9696;</span> Procesando...';
                }
                form.submit();
            }
        }
    }
</script>
@endsection
