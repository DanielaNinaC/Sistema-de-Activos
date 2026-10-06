@extends('layouts.principal')

@section('title', 'Registrar Incidente - Activos Tecnológicos')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    {{-- Header idéntico al registro de activo --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.incidentes.seguimiento') }}" class="btn btn-outline btn-icon" aria-label="Volver a la pantalla anterior" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Registrar Incidente</h2>
            <p class="text-sm text-slate-500">Reporte una falla o problema técnico detectado en un equipo</p>
        </div>
    </div>

    {{-- Formulario Principal estilo Card igual a registrar-activo --}}
    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200">
        <h3 class="text-lg font-bold text-slate-900 mb-6 border-b border-slate-100 pb-3">Formulario de Registro</h3>
        
        <form method="POST" action="{{ route('admin.incidentes.registrar.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-5" id="formRegistrarIncidente">
            @csrf

            <!-- Selección de Activo Tecnológico (Columna Izquierda 1) -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Activo Tecnológico Afectado *</label>
                <select name="fk_id_activo" id="selectActivo" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" data-searchable data-search-placeholder="Buscar por código, nombre o categoría..." required>
                    <option value="">Seleccione un activo tecnológico</option>
                    @foreach($activos as $activo)
                        @php
                            $ubicacionTxt = $activo->ubicacion ? ($activo->ubicacion->seccion . ($activo->ubicacion->aula ? ' - ' . $activo->ubicacion->aula : '')) : 'Sin ubicación';
                            $categoriaTxt = $activo->categoria ? $activo->categoria->nombre_categoria : 'Sin categoría';
                            $marcaModeloTxt = trim(($activo->marca ?? '') . ' ' . ($activo->modelo ?? ''));
                        @endphp
                        <option
                            value="{{ $activo->id_activo }}"
                            data-codigo="{{ $activo->codigo_patrimonial ?? 'ID: '.$activo->id_activo }}"
                            data-nombre="{{ $activo->nombre_equipo }}"
                            data-marcamodelo="{{ $marcaModeloTxt != '' ? $marcaModeloTxt : 'N/A' }}"
                            data-categoria="{{ $categoriaTxt }}"
                            data-categoria-id="{{ $activo->fk_id_categoria }}"
                            data-ubicacion="{{ $ubicacionTxt }}"
                            data-estado="{{ $activo->estado_operativo ?? 'No especificado' }}"
                            data-criticidad="{{ $activo->criticidad_institucional ?? '' }}"
                            data-prioridad-sugerida="{{ $prioridadPorCriticidad[$activo->criticidad_institucional] ?? '' }}"
                            {{ old('fk_id_activo') == $activo->id_activo ? 'selected' : '' }}
                        >
                            [{{ $activo->codigo_patrimonial ?? 'ID: '.$activo->id_activo }}] {{ $activo->nombre_equipo }} - {{ $categoriaTxt }} ({{ $ubicacionTxt }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Usuario que reporta (Columna Derecha 1 - Input Editable) -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Usuario que Reporta *</label>
                <input type="hidden" name="fk_id_usuario_reporta" id="hiddenUsuarioId" value="{{ old('fk_id_usuario_reporta') }}">
                <input 
                    type="text" 
                    name="usuario_reporta_nombre"
                    data-error-de="fk_id_usuario_reporta"
                    id="inputUsuarioReporta"
                    list="listUsuarios" 
                    value="{{ old('usuario_reporta_nombre') }}"
                    placeholder="Ingrese o busque el nombre del usuario..." 
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" 
                    required 
                    autocomplete="off"
                />
                <datalist id="listUsuarios">
                    @foreach($usuarios as $usuario)
                        <option value="{{ $usuario->nombre }} {{ $usuario->apellido ?? '' }}" data-id="{{ $usuario->id_usuario }}">
                    @endforeach
                </datalist>
            </div>

            <!-- Fecha y Hora del Incidente (Columna Izquierda 2) -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Fecha y Hora del Incidente *</label>
                <input 
                    type="datetime-local" 
                    name="fecha_incidente" 
                    value="{{ old('fecha_incidente', \Carbon\Carbon::now('America/La_Paz')->format('Y-m-d\TH:i')) }}" 
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition cursor-pointer" 
                    required 
                />
            </div>

            <!-- Estado Inicial (Columna Derecha 2) -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Estado Inicial *</label>
                <select name="estado_incidente" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                    @php $estadosIniciales = \App\Support\Catalogo::estadosIncidenteAbiertos(); @endphp
                    @foreach($estadosIniciales as $estado)
                        <option value="{{ $estado->nombre }}" {{ old('estado_incidente', $estadosIniciales[0]->nombre) == $estado->nombre ? 'selected' : '' }}>{{ \App\Support\Catalogo::etiqueta($estado) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Prioridad de Atención -->
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Prioridad de Atención *</label>
                <select name="prioridad" id="selectPrioridad" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                    @foreach($prioridades as $prioridad)
                        <option value="{{ $prioridad }}" {{ old('prioridad', 'Media') == $prioridad ? 'selected' : '' }}>{{ $prioridad }}</option>
                    @endforeach
                </select>
                <p id="avisoPrioridadSugerida" class="mt-1.5 text-xs text-blue-700 hidden">
                    Sugerida según la criticidad del activo (<span id="prevCriticidad" class="font-semibold"></span>). Puedes cambiarla.
                </p>
            </div>

            <!-- Sección Dinámica Informativa del Activo Seleccionado (Ancho completo) -->
            <div id="secDetalleActivo" class="md:col-span-2 hidden bg-slate-50 border border-blue-100 rounded-2xl p-5 space-y-3 transition">
                <div class="flex items-center gap-2 text-blue-700 font-semibold text-sm border-b border-blue-100 pb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Especificaciones del Activo Seleccionado</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                    <div class="bg-white p-3 rounded-xl border border-slate-200">
                        <span class="text-slate-500 font-medium uppercase tracking-wider block text-[10px] mb-0.5">Código Patrimonial</span>
                        <span id="prevCodigo" class="font-bold text-slate-800 text-sm">--</span>
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-slate-200">
                        <span class="text-slate-500 font-medium uppercase tracking-wider block text-[10px] mb-0.5">Categoría / Equipo</span>
                        <span id="prevCategoria" class="font-bold text-slate-800 text-sm">--</span>
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-slate-200">
                        <span class="text-slate-500 font-medium uppercase tracking-wider block text-[10px] mb-0.5">Ubicación</span>
                        <span id="prevUbicacion" class="font-bold text-slate-800 text-sm">--</span>
                    </div>
                    <div class="bg-white p-3 rounded-xl border border-slate-200">
                        <span class="text-slate-500 font-medium uppercase tracking-wider block text-[10px] mb-0.5">Estado Operativo</span>
                        <span id="prevEstado" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 mt-0.5">--</span>
                    </div>
                </div>
            </div>

            <!-- Modo de Falla asociado (Opcional, filtrado por categoría del activo) -->
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Modo de Falla Asociado <span class="text-slate-400 font-normal normal-case">(Opcional)</span></label>
                <select name="fk_id_modo_falla" id="selectModoIncidente" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" data-searchable data-search-placeholder="Buscar modo de falla...">
                    <option value="">-- Sin clasificar --</option>
                    @foreach($modos as $modo)
                        <option value="{{ $modo->id_modo_falla }}" data-categoria="{{ $modo->fk_id_categoria }}" {{ old('fk_id_modo_falla') == $modo->id_modo_falla ? 'selected' : '' }}>{{ $modo->nombre_modo_falla }} ({{ $modo->categoria->nombre_categoria ?? '-' }})</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 mt-1" id="avisoFiltroModoIncidente">Seleccione primero el activo para filtrar por su categoría. Ayuda a que el reporte de "modos de falla más frecuentes" use datos reales en vez de solo texto libre.</p>
            </div>

            <!-- Plantillas de Diagnóstico Rápido (Ancho completo) -->
            <!-- <div class="md:col-span-2 space-y-1.5">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Plantillas Rápida de Descripción</label>
                <p class="text-xs text-slate-400">Haga clic en una plantilla para añadirla automáticamente al texto de la falla:</p>
                <div class="flex flex-wrap gap-2 pt-1">
                    <button type="button" class="btn-preset inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-medium text-slate-700 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 transition" data-text="El equipo no enciende y no da señales de alimentación eléctrica.">
                        ⚡ No enciende / Sin energía
                    </button>
                    <button type="button" class="btn-preset inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-medium text-slate-700 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 transition" data-text="Falla de conectividad a la red local e internet. El puerto de red o Wi-Fi no responde.">
                        🌐 Falla de conectividad en red
                    </button>
                    <button type="button" class="btn-preset inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-medium text-slate-700 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 transition" data-text="El proyector no emite señal de video o muestra colores distorsionados/pantalla azul.">
                        📽️ Proyector sin señal / Video
                    </button>
                    <button type="button" class="btn-preset inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-medium text-slate-700 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 transition" data-text="El equipo emite ruidos anómalos en los ventiladores y presenta sobrecalentamiento excesivo.">
                        🔥 Sobrecalentamiento / Ruidos
                    </button>
                    <button type="button" class="btn-preset inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-medium text-slate-700 hover:bg-blue-50 hover:border-blue-200 hover:text-blue-700 transition" data-text="Los periféricos (teclado, mouse o monitor) presentan fallas de detección o daño físico.">
                        ⌨️ Falla en periféricos
                    </button>
                </div>
            </div> -->

            <!-- Descripción detallada de la falla (Ancho completo) -->
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Descripción Detallada de la Falla *</label>
                <textarea 
                    name="descripcion_falla" 
                    id="descripcionFalla"
                    rows="4" 
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" 
                    placeholder="Describa los síntomas del equipo, mensajes de error o comportamiento anómalo observados..." 
                    required
                >{{ old('descripcion_falla') }}</textarea>
            </div>

            

            <!-- Botones de Acción idénticos a registrar-activo -->
            <div class="md:col-span-2 flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.incidentes.seguimiento') }}" class="btn btn-outline">
                    Ver seguimiento
                </a>
                <button type="submit" class="btn btn-primary flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Registrar Incidente
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        function initRegistrarIncidente() {
            var selectActivo = document.getElementById('selectActivo');
            var secDetalle = document.getElementById('secDetalleActivo');
            var prevCodigo = document.getElementById('prevCodigo');
            var prevCategoria = document.getElementById('prevCategoria');
            var prevUbicacion = document.getElementById('prevUbicacion');
            var prevEstado = document.getElementById('prevEstado');
            var descripcionFalla = document.getElementById('descripcionFalla');
            var selectPrioridad = document.getElementById('selectPrioridad');
            var avisoPrioridad = document.getElementById('avisoPrioridadSugerida');
            var prevCriticidad = document.getElementById('prevCriticidad');

            if (!selectActivo) return;

            // La sugerencia solo se aplica mientras el usuario no haya elegido
            // una prioridad a mano; si ya decidió, no se le sobrescribe.
            var prioridadElegidaPorUsuario = @json(old('prioridad') !== null);
            if (selectPrioridad) {
                selectPrioridad.addEventListener('change', function () {
                    prioridadElegidaPorUsuario = true;
                    if (avisoPrioridad) avisoPrioridad.classList.add('hidden');
                });
            }

            function sugerirPrioridad(opcionActivo) {
                if (!selectPrioridad || !opcionActivo) return;

                var sugerida = opcionActivo.getAttribute('data-prioridad-sugerida') || '';
                var criticidad = opcionActivo.getAttribute('data-criticidad') || '';

                if (!sugerida || prioridadElegidaPorUsuario) return;

                selectPrioridad.value = sugerida;
                if (prevCriticidad) prevCriticidad.textContent = criticidad;
                if (avisoPrioridad) avisoPrioridad.classList.remove('hidden');
            }

            function updateActivoPreview() {
                var selectedOpt = selectActivo.options[selectActivo.selectedIndex];
                if (!selectedOpt || !selectedOpt.value) {
                    if (secDetalle) secDetalle.classList.add('hidden');
                    return;
                }

                var codigo = selectedOpt.getAttribute('data-codigo') || '--';
                var nombre = selectedOpt.getAttribute('data-nombre') || '--';
                var categoria = selectedOpt.getAttribute('data-categoria') || '--';
                var ubicacion = selectedOpt.getAttribute('data-ubicacion') || '--';
                var estado = selectedOpt.getAttribute('data-estado') || 'No especificado';

                if (prevCodigo) prevCodigo.textContent = codigo;
                if (prevCategoria) prevCategoria.textContent = nombre + ' (' + categoria + ')';
                if (prevUbicacion) prevUbicacion.textContent = ubicacion;
                if (prevEstado) {
                    prevEstado.textContent = estado;
                    if (estado.toLowerCase() === 'operativo') {
                        prevEstado.className = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 mt-0.5';
                    } else if (estado.toLowerCase().includes('mantenimiento')) {
                        prevEstado.className = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800 mt-0.5';
                    } else {
                        prevEstado.className = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800 mt-0.5';
                    }
                }

                sugerirPrioridad(selectedOpt);

                if (secDetalle) secDetalle.classList.remove('hidden');
            }

            selectActivo.removeEventListener('change', updateActivoPreview);
            selectActivo.addEventListener('change', updateActivoPreview);
            updateActivoPreview();

            // Filtrar Modo de Falla según la categoría del activo seleccionado
            function filtrarModosPorActivo() {
                var selectModo = document.getElementById('selectModoIncidente');
                var aviso = document.getElementById('avisoFiltroModoIncidente');
                if (!selectModo) return;

                var opcionActivo = selectActivo.options[selectActivo.selectedIndex];
                var categoriaId = opcionActivo ? opcionActivo.getAttribute('data-categoria-id') : '';

                var modoSigueValido = false;
                Array.prototype.forEach.call(selectModo.options, function (opt) {
                    if (opt.value === '') return;
                    var coincide = !categoriaId || opt.getAttribute('data-categoria') === categoriaId;
                    opt.hidden = !coincide;
                    if (coincide && selectModo.value === opt.value) modoSigueValido = true;
                });

                if (!modoSigueValido) {
                    selectModo.value = '';
                }
                selectModo.dispatchEvent(new Event('change', { bubbles: true }));

                if (aviso) {
                    var nombreCategoria = opcionActivo ? opcionActivo.getAttribute('data-categoria') : '';
                    aviso.textContent = categoriaId && nombreCategoria
                        ? 'Mostrando solo los modos de falla de la categoría "' + nombreCategoria + '".'
                        : 'Seleccione primero el activo para filtrar por su categoría.';
                }
            }

            selectActivo.removeEventListener('change', filtrarModosPorActivo);
            selectActivo.addEventListener('change', filtrarModosPorActivo);
            filtrarModosPorActivo();

            // Botones de plantilla rápida
            var presetBtns = document.querySelectorAll('.btn-preset');
            presetBtns.forEach(function (btn) {
                btn.onclick = function () {
                    var presetText = btn.getAttribute('data-text');
                    if (descripcionFalla && presetText) {
                        if (descripcionFalla.value.trim().length > 0) {
                            descripcionFalla.value += '\n' + presetText;
                        } else {
                            descripcionFalla.value = presetText;
                        }
                        descripcionFalla.focus();
                    }
                };
            });

            // Sincronizar datalist de usuario
            var inputUsuario = document.getElementById('inputUsuarioReporta');
            var hiddenUsuarioId = document.getElementById('hiddenUsuarioId');
            var listUsuarios = document.getElementById('listUsuarios');

            if (inputUsuario && hiddenUsuarioId && listUsuarios) {
                inputUsuario.addEventListener('input', function () {
                    var val = inputUsuario.value.trim();
                    var matchedId = '';
                    for (var i = 0; i < listUsuarios.options.length; i++) {
                        if (listUsuarios.options[i].value.trim() === val) {
                            matchedId = listUsuarios.options[i].getAttribute('data-id') || '';
                            break;
                        }
                    }
                    hiddenUsuarioId.value = matchedId;
                });
            }

            // Auto-establecer fecha y hora exacta del cliente local si no hay selección previa
            var inputFecha = document.querySelector('input[name="fecha_incidente"]');
            if (inputFecha && !'{{ old('fecha_incidente') }}') {
                var now = new Date();
                var offsetMs = now.getTimezoneOffset() * 60000;
                var localISOTime = (new Date(now.getTime() - offsetMs)).toISOString().slice(0, 16);
                inputFecha.value = localISOTime;
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initRegistrarIncidente);
        } else {
            initRegistrarIncidente();
        }

        document.addEventListener('turbo:load', initRegistrarIncidente);
    })();
</script>
@endsection
