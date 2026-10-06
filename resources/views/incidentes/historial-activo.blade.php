@extends('layouts.principal')

@section('title', 'Historial de Incidentes: ' . $activo->nombre_equipo)

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.incidentes.historial') }}" class="btn btn-outline btn-icon" aria-label="Volver al historial" title="Volver al historial">
                <span aria-hidden="true">&#8592;</span>
            </a>
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">{{ $activo->nombre_equipo }}</h2>
                <p class="text-sm text-slate-500">Historial completo de incidentes de este activo</p>
            </div>
        </div>

        <a href="{{ route('admin.incidentes.registrar') }}" class="btn btn-primary self-start sm:self-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Registrar Nuevo Incidente
        </a>
    </div>

    {{-- Ficha del activo --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Código Patrimonial</span>
                <span class="block text-sm font-mono font-semibold text-slate-800 mt-0.5">{{ $activo->codigo_patrimonial ?? '-' }}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Categoría</span>
                <span class="block text-sm font-semibold text-slate-800 mt-0.5">{{ $activo->categoria->nombre_categoria ?? '-' }}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Ubicación</span>
                <span class="block text-sm font-semibold text-slate-800 mt-0.5">{{ $activo->ubicacion ? $activo->ubicacion->seccion . ($activo->ubicacion->aula ? ' - ' . $activo->ubicacion->aula : '') : '-' }}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Incidentes</span>
                <span class="block text-sm font-bold text-slate-800 mt-0.5">{{ $incidentes->total() }}</span>
            </div>
            <div>
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Sin Resolver</span>
                <span class="block mt-0.5">
                    @if($totalAbiertos > 0)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">{{ $totalAbiertos }}</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Al día</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- Incidentes del activo --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="w-12 px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">ID</th>
                        <th class="w-28 px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha / Hora</th>
                        <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Descripción de la Falla</th>
                        <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Reportado Por</th>
                        <th class="px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Prioridad</th>
                        <th class="px-3 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Estado</th>
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
                            <td class="px-3 py-4 text-left text-sm text-slate-700">
                                <div class="max-w-md">{{ \Illuminate\Support\Str::limit($incidente->descripcion_falla, 90) }}</div>
                                @if($incidente->modoFalla)
                                    <div class="text-xs text-slate-400 mt-0.5">Modo de falla: {{ $incidente->modoFalla->nombre_modo_falla }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-4 text-left text-sm text-slate-700 whitespace-nowrap">
                                {{ $incidente->usuarioReporta ? $incidente->usuarioReporta->nombre . ' ' . ($incidente->usuarioReporta->apellido ?? '') : '-' }}
                            </td>
                            <td class="px-3 py-4 text-center text-sm whitespace-nowrap">
                                @include('incidentes.partials.badge-prioridad', ['prioridad' => $incidente->prioridad])
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
                            <td class="px-3 py-4 text-center text-sm whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('admin.incidentes.show', $incidente->id_incidente) }}"
                                       class="btn btn-xs btn-primary"
                                       title="Ver vista completa de detalles del incidente y su seguimiento">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Ver Detalles
                                    </a>

                                    <button type="button"
                                            onclick="openUpdateModal({{ $incidente->id_incidente }}, '{{ addslashes($incidente->estado_incidente) }}', '{{ addslashes($incidente->descripcion_falla) }}', '{{ addslashes($incidente->prioridad ?? '') }}')"
                                            class="btn btn-xs btn-warning"
                                            title="Editar estado del incidente">
                                        Editar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                Este activo no tiene incidentes registrados.
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

<!-- Modal para Editar Estado de Incidente -->
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
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nuevo Estado <span class="text-rose-500">*</span></label>
                    <select id="update_estado_incidente" name="estado_incidente" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                        @foreach(\App\Support\Catalogo::filas('estados_incidente') as $estado)
                            <option value="{{ $estado->nombre }}">{{ \App\Support\Catalogo::etiqueta($estado) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Prioridad de Atención</label>
                    <select id="update_prioridad" name="prioridad" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                        @foreach($prioridades as $prioridad)
                            <option value="{{ $prioridad }}">{{ $prioridad }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Descripción de la falla / Notas de avance</label>
                    <textarea id="update_descripcion_falla" name="descripcion_falla" rows="4" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-5 mt-4 border-t border-slate-100">
                <button type="button" onclick="closeUpdateModal()" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openUpdateModal(id, estado, descripcion, prioridad) {
        var form = document.getElementById('updateForm');
        form.action = '/admin/incidentes/' + id;

        document.getElementById('update_estado_incidente').value = estado;
        document.getElementById('update_descripcion_falla').value = descripcion;

        var selPrioridad = document.getElementById('update_prioridad');
        if (selPrioridad && prioridad) selPrioridad.value = prioridad;

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
