{{-- Modal para Registrar Mantenimiento Realizado con Evidencia Mínima --}}
<div id="realizarMantenimientoModal" 
     class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200">
    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-7 shadow-2xl space-y-5 border border-slate-100 max-h-[92vh] flex flex-col transform transition-transform scale-95 opacity-0 duration-150" 
         id="realizarMantenimientoCard">
        
        <!-- Header del Modal -->
        <div class="flex items-start justify-between border-b border-slate-100 pb-4 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 tracking-tight">Registrar mantenimiento realizado</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Registre evidencia de la intervención ejecutada antes de marcar como realizado.
                    </p>
                </div>
            </div>
            <button type="button" 
                    onclick="closeRealizarModal()" 
                    class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Info rápida del mantenimiento y activo intervenido -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 p-3 rounded-2xl bg-slate-50 border border-slate-200/60 text-xs shrink-0">
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Mantenimiento:</span>
                <span class="font-bold text-slate-800 font-mono" id="realizarModalMantId">—</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Activo:</span>
                <span class="font-bold text-slate-800 truncate block" id="realizarModalActivo">—</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Código:</span>
                <span class="font-mono text-slate-600 block" id="realizarModalCodigo">—</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Tipo:</span>
                <span class="font-bold text-indigo-700 block" id="realizarModalTipo">—</span>
            </div>
        </div>

        <!-- Formulario con scroll interno si la pantalla es pequeña -->
        <form id="realizarMantenimientoForm" method="POST" action="" class="space-y-4 overflow-y-auto pr-1 flex-1">
            @csrf

            <!-- Sección 1: Checklist de mantenimiento -->
            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                        1. Checklist de tareas realizadas
                    </label>
                    <span class="text-[11px] text-slate-400 font-medium">
                        Marque solo las tareas ejecutadas (no obligatorio marcar todas)
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 bg-slate-50/60 p-3 rounded-2xl border border-slate-200/70">
                    @php
                        $tareasChecklist = \App\Models\Mantenimiento::TAREAS_CHECKLIST;
                    @endphp
                    @foreach($tareasChecklist as $num => $tarea)
                        <label class="flex items-start gap-2.5 p-2 rounded-xl bg-white border border-slate-200/80 hover:border-indigo-300 hover:bg-indigo-50/30 transition cursor-pointer select-none group">
                            <input type="checkbox" 
                                   name="tareas[]" 
                                   value="{{ $num }}" 
                                   class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 checklist-tarea-item"
                                   onchange="validarEvidenciaEnVivo()">
                            <span class="text-xs text-slate-700 leading-snug group-hover:text-slate-900">
                                <span class="font-semibold text-slate-400 text-[11px] mr-1">{{ $num }}.</span>
                                {{ $tarea }}
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="text-[11px] text-slate-400 italic">
                    * Las tareas no seleccionadas quedarán registradas como no ejecutadas dentro de esta intervención.
                </p>
            </div>

            <!-- Sección 2: Campo de observaciones o detalle -->
            <div class="space-y-1.5">
                <label for="realizarModalObservaciones" class="block text-xs font-bold text-slate-800 uppercase tracking-wider">
                    2. Observaciones / Detalle del mantenimiento realizado
                </label>
                <textarea id="realizarModalObservaciones" 
                          name="observaciones" 
                          rows="3" 
                          placeholder="Ejemplo: Se realizó limpieza externa del equipo y revisión del cable de alimentación. Se detectó desgaste en el conector, pero el equipo continúa funcionando correctamente." 
                          class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition resize-y"
                          oninput="validarEvidenciaEnVivo()"></textarea>
                <p class="text-[11px] text-slate-400 leading-relaxed">
                    Describa las actividades realizadas, problemas encontrados, componentes intervenidos o resultados obtenidos.
                </p>
            </div>

            <!-- Mensaje de Validación de Evidencia Mínima -->
            <div id="realizarModalErrorEvidencia" class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-start gap-2 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span class="font-medium" id="realizarModalErrorTexto">
                    Debe registrar al menos una tarea realizada o ingresar una observación/detalle del mantenimiento antes de finalizar.
                </span>
            </div>

            <div id="realizarModalExitoEvidencia" class="hidden p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-900 flex items-center gap-2 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="font-semibold text-emerald-800" id="realizarModalResumenEvidencia">
                    Evidencia registrada correctamente. Listo para confirmar.
                </span>
            </div>

            <!-- Footer con Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 shrink-0">
                <button type="button" onclick="closeRealizarModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="button" 
                        id="btnConfirmarRealizacion" 
                        onclick="solicitarConfirmacionRealizacion()"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-xs font-bold text-white shadow-sm hover:shadow transition flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Confirmar mantenimiento realizado
                </button>
            </div>
        </form>

    </div>
</div>

<!-- Diálogo de Confirmación Personalizado (Estilo Tailwind de la Aplicación, sin alert/confirm de navegador) -->
<div id="submodalConfirmarRealizacion" 
     class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 transform transition-all scale-95 opacity-0 duration-150 space-y-4" 
         id="submodalConfirmarCard">
        
        <div class="flex items-start gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h4 class="text-base font-bold text-slate-900">¿Finalizar este mantenimiento?</h4>
                <p class="text-xs text-slate-500 mt-0.5">Confirmación de intervención realizada</p>
            </div>
        </div>

        <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-2xl text-xs text-slate-700 leading-relaxed">
            ¿Está seguro de finalizar este mantenimiento? Las actividades y observaciones registradas serán almacenadas en el historial del activo como evidencia de la intervención realizada.
        </div>

        <!-- Resumen de lo que se registrará -->
        <div id="submodalResumenContenido" class="text-[11px] text-slate-500 space-y-1 px-1">
            <!-- Rellenado dinámicamente -->
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
            <button type="button" 
                    onclick="closeSubmodalConfirmar()" 
                    class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                Volver a revisar
            </button>
            <button type="button" 
                    id="btnEjecutarEnvioRealizacion"
                    onclick="ejecutarEnvioRealizacion()" 
                    class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-xs font-bold text-white shadow-sm hover:shadow transition flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Sí, finalizar mantenimiento
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        // Validación de evidencia mínima en vivo
        window.validarEvidenciaEnVivo = function () {
            var checkboxes = document.querySelectorAll('.checklist-tarea-item:checked');
            var obs = document.getElementById('realizarModalObservaciones');
            var obsTexto = obs ? obs.value.trim() : '';

            var cumpleTareas = checkboxes.length > 0;
            var cumpleObs = obsTexto.length > 0;
            var esValido = cumpleTareas || cumpleObs;

            var errorDiv = document.getElementById('realizarModalErrorEvidencia');
            var exitoDiv = document.getElementById('realizarModalExitoEvidencia');
            var resumenSpan = document.getElementById('realizarModalResumenEvidencia');

            if (esValido) {
                if (errorDiv) errorDiv.classList.add('hidden');
                if (exitoDiv) exitoDiv.classList.remove('hidden');

                if (resumenSpan) {
                    var detalle = [];
                    if (cumpleTareas) detalle.push(checkboxes.length + (checkboxes.length === 1 ? ' tarea seleccionada' : ' tareas seleccionadas'));
                    if (cumpleObs) detalle.push('observaciones registradas');
                    resumenSpan.innerText = 'Evidencia válida: ' + detalle.join(' + ') + '. Listo para confirmar.';
                }
            } else {
                if (errorDiv) errorDiv.classList.remove('hidden');
                if (exitoDiv) exitoDiv.classList.add('hidden');
            }

            return esValido;
        };

        // Apertura del modal principal
        window.openRealizarModal = function (id, activo, codigo, tipo, fechaProg) {
            if (typeof window.closeUpdateModal === 'function') {
                window.closeUpdateModal();
            }

            var form = document.getElementById('realizarMantenimientoForm');
            if (form) {
                form.action = '/admin/mantenimiento/' + id + '/realizar';
                form.reset();
            }

            // Datos informativos
            var idEl = document.getElementById('realizarModalMantId');
            if (idEl) idEl.innerText = '#' + id;

            var actEl = document.getElementById('realizarModalActivo');
            if (actEl) actEl.innerText = activo || 'Activo #' + id;

            var codEl = document.getElementById('realizarModalCodigo');
            if (codEl) codEl.innerText = codigo || '—';

            var tipoEl = document.getElementById('realizarModalTipo');
            if (tipoEl) tipoEl.innerText = tipo || 'Mantenimiento';

            // Desmarcar todos los checkboxes
            document.querySelectorAll('.checklist-tarea-item').forEach(function (cb) {
                cb.checked = false;
            });

            var obsEl = document.getElementById('realizarModalObservaciones');
            if (obsEl) obsEl.value = '';

            // Ejecutar validación inicial
            validarEvidenciaEnVivo();

            var modal = document.getElementById('realizarMantenimientoModal');
            var card = document.getElementById('realizarMantenimientoCard');
            if (modal && card) {
                modal.classList.remove('hidden');
                setTimeout(function () {
                    card.classList.remove('scale-95', 'opacity-0');
                    card.classList.add('scale-100', 'opacity-100');
                }, 10);
            }
        };

        // Cierre del modal principal
        window.closeRealizarModal = function () {
            closeSubmodalConfirmar();
            var modal = document.getElementById('realizarMantenimientoModal');
            var card = document.getElementById('realizarMantenimientoCard');
            if (modal && card) {
                card.classList.remove('scale-100', 'opacity-100');
                card.classList.add('scale-95', 'opacity-0');
                setTimeout(function () {
                    modal.classList.add('hidden');
                }, 150);
            }
        };

        // Apertura del diálogo de confirmación personalizado
        window.solicitarConfirmacionRealizacion = function () {
            var esValido = validarEvidenciaEnVivo();

            if (!esValido) {
                // En lugar de alert() del navegador, destacamos visualmente el aviso en el formulario
                var errorDiv = document.getElementById('realizarModalErrorEvidencia');
                if (errorDiv) {
                    errorDiv.classList.remove('hidden');
                    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    errorDiv.classList.add('ring-2', 'ring-amber-400');
                    setTimeout(function () {
                        errorDiv.classList.remove('ring-2', 'ring-amber-400');
                    }, 1200);
                }
                return;
            }

            // Preparar resumen en el submodal
            var checkboxes = document.querySelectorAll('.checklist-tarea-item:checked');
            var obs = document.getElementById('realizarModalObservaciones');
            var obsTexto = obs ? obs.value.trim() : '';

            var resumenEl = document.getElementById('submodalResumenContenido');
            if (resumenEl) {
                var partes = [];
                partes.push('• <strong>Tareas marcadas:</strong> ' + checkboxes.length + ' de 9.');
                partes.push('• <strong>Observaciones:</strong> ' + (obsTexto.length > 0 ? 'Sí (' + obsTexto.length + ' caracteres)' : 'Sin observaciones adicionales.'));
                resumenEl.innerHTML = partes.join('<br>');
            }

            var submodal = document.getElementById('submodalConfirmarRealizacion');
            var subcard = document.getElementById('submodalConfirmarCard');
            if (submodal && subcard) {
                submodal.classList.remove('hidden');
                setTimeout(function () {
                    subcard.classList.remove('scale-95', 'opacity-0');
                    subcard.classList.add('scale-100', 'opacity-100');
                }, 10);
            }
        };

        // Cierre del diálogo de confirmación
        window.closeSubmodalConfirmar = function () {
            var submodal = document.getElementById('submodalConfirmarRealizacion');
            var subcard = document.getElementById('submodalConfirmarCard');
            if (submodal && subcard) {
                subcard.classList.remove('scale-100', 'opacity-100');
                subcard.classList.add('scale-95', 'opacity-0');
                setTimeout(function () {
                    submodal.classList.add('hidden');
                }, 150);
            }
        };

        // Envío final del formulario tras confirmar en el submodal
        window.ejecutarEnvioRealizacion = function () {
            var btn = document.getElementById('btnEjecutarEnvioRealizacion');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<svg class="animate-spin h-4 w-4 text-white inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Finalizando...';
            }

            var form = document.getElementById('realizarMantenimientoForm');
            if (form) {
                form.submit();
            }
        };
    })();
</script>
