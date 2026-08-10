@extends('layouts.principal')

@section('title', 'Trazabilidad de Usuarios - Informes y Reportes')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">

    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Trazabilidad de Usuarios</h2>
            <p class="text-xs text-slate-500">Registro de auditoría: quién hizo qué cambio, cuándo y sobre qué tabla</p>
        </div>
        <a href="{{ route('admin.informes.trazabilidad.excel', request()->query()) }}" class="btn btn-success ml-auto">Excel</a>
    </div>

    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-200">
        <form method="GET" action="{{ route('admin.informes.trazabilidad') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end flex-wrap">
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Usuario</label>
                <select name="id_usuario" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition">
                    <option value="">Todos</option>
                    @foreach($usuarios as $usuario)
                        <option value="{{ $usuario->id_usuario }}" {{ request('id_usuario') == $usuario->id_usuario ? 'selected' : '' }}>{{ $usuario->nombre }} {{ $usuario->apellido }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[160px]">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Tabla Afectada</label>
                <select name="tabla_afectada" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition">
                    <option value="">Todas</option>
                    @foreach($tablas as $tabla)
                        <option value="{{ $tabla }}" {{ request('tabla_afectada') == $tabla ? 'selected' : '' }}>{{ $tabla }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Desde</label>
                <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Hasta</label>
                <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
            </div>
            <button type="submit" class="btn btn-dark">Filtrar</button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden space-y-4">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Fecha/Hora</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Usuario</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Acción</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Tabla</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Registro</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Cambios</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-3 font-mono text-xs text-slate-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($log->fecha_hora)->format('d/m/Y H:i:s') }}</td>
                            <td class="px-6 py-3 text-slate-800">{{ $log->usuario ? trim($log->usuario->nombre.' '.$log->usuario->apellido) : 'Usuario eliminado' }}</td>
                            <td class="px-6 py-3 text-slate-700">{{ $log->accion_realizada }}</td>
                            <td class="px-6 py-3 text-slate-700 font-mono text-xs">{{ $log->tabla_afectada }}</td>
                            <td class="px-6 py-3 text-center font-mono text-xs text-slate-500">{{ $log->registro_id ?? '-' }}</td>
                            <td class="px-6 py-3 text-center">
                                @if($log->valor_anterior || $log->valor_nuevo)
                                    <button type="button" onclick="toggleCambios({{ $log->id_log }})" class="text-xs font-semibold text-blue-600 hover:text-blue-800">Ver cambios</button>
                                @else
                                    <span class="text-xs text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                        @if($log->valor_anterior || $log->valor_nuevo)
                            <tr id="cambios-{{ $log->id_log }}" class="hidden bg-slate-50">
                                <td colspan="6" class="px-6 py-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                        <div class="bg-white border border-rose-100 rounded-xl p-3">
                                            <span class="block font-bold text-rose-600 uppercase tracking-wider mb-1">Valor Anterior</span>
                                            <pre class="whitespace-pre-wrap break-words text-slate-700 font-mono">{{ $log->valor_anterior ?: '—' }}</pre>
                                        </div>
                                        <div class="bg-white border border-emerald-100 rounded-xl p-3">
                                            <span class="block font-bold text-emerald-600 uppercase tracking-wider mb-1">Valor Nuevo</span>
                                            <pre class="whitespace-pre-wrap break-words text-slate-700 font-mono">{{ $log->valor_nuevo ?: '—' }}</pre>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">No hay registros de auditoría que coincidan con los filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="px-6 pb-4">{{ $logs->links() }}</div>
        @endif
    </div>

</div>

<script>
    function toggleCambios(id) {
        var fila = document.getElementById('cambios-' + id);
        if (fila) fila.classList.toggle('hidden');
    }
</script>
@endsection
