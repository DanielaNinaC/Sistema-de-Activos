@extends('layouts.principal')

@section('title', 'Historial de Incidentes - Activos Tecnológicos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center w-10 h-10 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition shadow-sm" aria-label="Volver" title="Volver">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">Historial de Incidentes</h2>
                <p class="text-sm text-slate-500">Registro histórico completo de todos los casos reportados</p>
            </div>
        </div>

        <a href="{{ route('admin.incidentes.registrar') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl transition shadow-md shadow-blue-200 self-start sm:self-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Registrar Nuevo Incidente
        </a>
    </div>

    {{-- Alerta Éxito --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800 flex items-center gap-3 shadow-sm transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    {{-- Barra de Búsqueda --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.incidentes.historial') }}" class="flex flex-col sm:flex-row gap-3">
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
                    placeholder="Buscar por activo, código, usuario reportante, estado o descripción..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"
                >
            </div>
            
            <div class="flex items-center gap-2">
                <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-sm transition shadow-sm flex items-center gap-2">
                    Buscar
                </button>

                @if(request('buscar'))
                    <a href="{{ route('admin.incidentes.historial') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold rounded-xl text-sm transition flex items-center gap-1">
                        ✕ Limpiar
                    </a>
                @endif
            </div>
        </form>

        @if(request('buscar'))
            <div class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                <span>Filtro activo para: <strong class="text-slate-800">"{{ request('buscar') }}"</strong></span>
                <span class="text-slate-300">•</span>
                <span>Resultados encontrados: <strong class="text-slate-800">{{ $incidentes->total() }}</strong></span>
            </div>
        @endif
    </div>

    {{-- Tabla Historial de Incidentes --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-12 px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                        <th class="w-28 px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha / Hora</th>
                        <th class="px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Activo Tecnológico</th>
                        <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Reportado Por</th>
                        <th class="px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Estado</th>
                        <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Descripción de Falla</th>
                        <th class="px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($incidentes as $incidente)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="w-12 px-3 py-4 text-center text-sm text-slate-700 font-mono whitespace-nowrap">{{ $incidente->id_incidente }}</td>
                            <td class="w-28 px-3 py-4 text-center text-sm whitespace-nowrap">
                                <div class="font-semibold text-slate-900 font-mono text-sm">{{ \Carbon\Carbon::parse($incidente->fecha_incidente)->format('d/m/Y') }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ \Carbon\Carbon::parse($incidente->fecha_incidente)->format('H:i') }} hs</div>
                            </td>
                            <td class="px-3 py-4 text-center text-sm whitespace-nowrap">
                                <div class="font-semibold text-slate-900">{{ $incidente->activo->nombre_equipo ?? '-' }}</div>
                                @if($incidente->activo && $incidente->activo->codigo_patrimonial)
                                    <div class="text-xs text-slate-400 font-mono">{{ $incidente->activo->codigo_patrimonial }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-4 text-left text-sm text-slate-700 whitespace-nowrap">
                                {{ $incidente->usuarioReporta ? $incidente->usuarioReporta->nombre . ' ' . ($incidente->usuarioReporta->apellido ?? '') : '-' }}
                            </td>
                            <td class="px-3 py-4 text-center text-sm whitespace-nowrap">
                                @php
                                    $st = strtolower(trim($incidente->estado_incidente ?? ''));
                                    $badgeStyle = match(true) {
                                        str_contains($st, 'resuelto') => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        str_contains($st, 'cerrado') => 'bg-slate-100 text-slate-800 border-slate-300',
                                        str_contains($st, 'reportado') => 'bg-rose-100 text-rose-800 border-rose-200',
                                        str_contains($st, 'proceso') => 'bg-blue-100 text-blue-800 border-blue-200',
                                        str_contains($st, 'espera') => 'bg-amber-100 text-amber-800 border-amber-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $badgeStyle }}">
                                    {{ $incidente->estado_incidente }}
                                </span>
                            </td>
                            <td class="px-3 py-4 text-left text-sm text-slate-600 max-w-xs leading-relaxed truncate" title="{{ $incidente->descripcion_falla }}">
                                {{ $incidente->descripcion_falla }}
                            </td>
                            <td class="px-3 py-4 text-center text-sm whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('admin.incidentes.show', $incidente->id_incidente) }}" 
                                       class="inline-flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white px-2.5 py-1 rounded-xl transition text-xs font-semibold shadow-sm"
                                       title="Ver vista completa de detalles del incidente y su seguimiento">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Ver Detalles
                                    </a>

                                    <button type="button" 
                                            onclick="openUpdateModal({{ $incidente->id_incidente }}, '{{ addslashes($incidente->estado_incidente) }}', '{{ addslashes($incidente->descripcion_falla) }}')" 
                                            class="inline-flex items-center gap-1 bg-amber-500 hover:bg-amber-600 text-white px-2.5 py-1 rounded-xl transition text-xs font-semibold shadow-sm"
                                            title="Editar estado del incidente">
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
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                @if(request('buscar'))
                                    No se encontraron incidentes que coincidan con la búsqueda.
                                @else
                                    No hay incidentes registrados en el historial.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $incidentes->links() }}
    </div>
</div>

<!-- Modal para Editar Estado de Incidente en Historial -->
<div id="updateModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 border border-slate-100 transform transition-all scale-95 opacity-0" id="updateModalCard">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-lg font-bold text-slate-900">Editar Incidente</h3>
            <button type="button" onclick="closeUpdateModal()" class="text-slate-400 hover:text-slate-600 transition">✕</button>
        </div>

        <form id="updateForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Estado del Incidente <span class="text-rose-500">*</span></label>
                    <select id="update_estado_incidente" name="estado_incidente" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        <option value="Reportado">Reportado</option>
                        <option value="En proceso">En proceso</option>
                        <option value="En espera">En espera</option>
                        <option value="Resuelto">Resuelto</option>
                        <option value="Cerrado">Cerrado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Descripción de la falla</label>
                    <textarea id="update_descripcion_falla" name="descripcion_falla" rows="4" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-5 mt-4 border-t border-slate-100">
                <button type="button" onclick="closeUpdateModal()" class="px-4 py-2.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openUpdateModal(id, estado, descripcion) {
        var form = document.getElementById('updateForm');
        form.action = '/admin/incidentes/' + id;

        document.getElementById('update_estado_incidente').value = estado;
        document.getElementById('update_descripcion_falla').value = descripcion;

        var modal = document.getElementById('updateModal');
        var card = document.getElementById('updateModalCard');
        modal.classList.remove('hidden');
        setTimeout(function () {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeUpdateModal() {
        var modal = document.getElementById('updateModal');
        var card = document.getElementById('updateModalCard');
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(function () {
            modal.classList.add('hidden');
        }, 150);
    }
</script>
@endsection
