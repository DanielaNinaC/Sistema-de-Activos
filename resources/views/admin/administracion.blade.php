@extends('layouts.principal')

@section('title','Administración')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold">Administración</h2>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 flex items-center gap-3 shadow-sm transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-sm space-y-2 transition">
            <div class="flex items-center gap-2 font-semibold text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Por favor corrige los siguientes errores:</span>
            </div>
            <ul class="list-disc list-inside text-xs space-y-1 text-rose-700 pl-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded shadow">Usuarios: <strong>{{ $usuarios->total() }}</strong></div>
        <div class="bg-white p-4 rounded shadow">Roles: <strong>{{ $roles->count() }}</strong></div>
        <div class="bg-white p-4 rounded shadow">Logs: <strong>{{ $logs_count }}</strong></div>
    </div>

    <!-- Formulario de creación integrado en la subsección Usuarios -->
    <div class="bg-white rounded-lg p-5 shadow mb-6 max-w-5xl mx-auto">
        <h3 class="text-lg font-medium mb-4">Crear usuario</h3>
        <form method="POST" action="{{ route('admin.usuarios.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @csrf
            <div>
                <label class="block text-sm mb-1">Nombre</label>
                <input name="nombre" value="{{ old('nombre') }}" class="w-full max-w-md border @error('nombre') border-rose-500 @else border-gray-200 @enderror px-3 py-2.5 text-sm rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Apellido</label>
                <input name="apellido" value="{{ old('apellido') }}" class="w-full max-w-md border @error('apellido') border-rose-500 @else border-gray-200 @enderror px-3 py-2.5 text-sm rounded focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>
            <div>
                <label class="block text-sm mb-1">Correo</label>
                <input name="correo" type="email" value="{{ old('correo') }}" class="w-full max-w-md border @error('correo') border-rose-500 @else border-gray-200 @enderror px-3 py-2.5 text-sm rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required />
            </div>
            <div>
                <label class="block text-sm mb-1">Rol</label>
                <select name="fk_id_rol" class="w-full max-w-md border @error('fk_id_rol') border-rose-500 @else border-gray-200 @enderror px-3 py-2.5 text-sm rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    @foreach($roles as $r)
                        <option value="{{ $r->id_rol }}" {{ old('fk_id_rol') == $r->id_rol ? 'selected' : '' }}>{{ $r->nombre_rol }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm mb-1">Contraseña</label>
                <input name="contrasena" type="password" class="w-full max-w-md border @error('contrasena') border-rose-500 @else border-gray-200 @enderror px-3 py-2.5 text-sm rounded focus:outline-none focus:ring-2 focus:ring-blue-500" required />
            </div>
            <div class="flex items-end">
                <button class="btn btn-primary">Crear</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Nombre</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Correo</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Rol</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($usuarios as $u)
                <tr>
                    <td class="px-6 py-4 text-center text-sm text-gray-700">{{ $u->id_usuario }}</td>
                    <td class="px-6 py-4 text-center text-sm text-gray-700 font-medium">{{ $u->nombre }} {{ $u->apellido ?? '' }}</td>
                    <td class="px-6 py-4 text-center text-sm text-gray-700">{{ $u->correo }}</td>
                    <td class="px-6 py-4 text-center text-sm text-gray-700">{{ $u->rol->nombre_rol ?? '' }}</td>
                    <td class="px-6 py-4 text-center text-sm text-gray-700">
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-xs font-bold {{ $u->estado ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-rose-100 text-rose-800 border border-rose-200' }}">
                            {{ $u->estado ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-center text-sm text-gray-700 space-x-2">
                        <a href="{{ route('admin.usuarios.show', $u->id_usuario) }}" class="btn btn-xs btn-primary">Ver</a>
                        <a href="{{ route('admin.usuarios.edit', $u->id_usuario) }}" class="btn btn-xs btn-warning">Editar</a>
                        
                        <form id="user-status-form-{{ $u->id_usuario }}" action="{{ route('admin.usuarios.destroy', $u->id_usuario) }}" method="POST" class="inline-block">
                            @csrf
                            @method('DELETE')
                            <button type="button" 
                                    onclick="openConfirmModal('user-status-form-{{ $u->id_usuario }}', {{ $u->estado ? 'true' : 'false' }}, '{{ addslashes($u->nombre . ' ' . ($u->apellido ?? '')) }}')"
                                    class="btn btn-xs btn-dark">
                                {{ $u->estado ? 'Desactivar cuenta' : 'Activar cuenta' }}
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
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
            if (form) form.submit();
        }
    }
</script>
@endsection
