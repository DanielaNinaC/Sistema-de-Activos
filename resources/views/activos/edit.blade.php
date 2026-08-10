@extends('layouts.principal')

@section('title', 'Editar Activo - ' . $activo->nombre_equipo)

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.activos.inventario') }}" class="btn btn-outline btn-icon" aria-label="Volver al inventario" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Editar Activo Tecnológico</h2>
            <p class="text-sm text-slate-500">Modifica la información del activo <span class="font-mono font-semibold text-slate-800">{{ $activo->codigo_patrimonial }}</span></p>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 space-y-1 shadow-sm">
            <div class="font-semibold text-sm">Por favor corrige los siguientes errores:</div>
            <ul class="list-disc list-inside text-xs space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200">
        <h3 class="text-lg font-bold text-slate-900 mb-6 border-b border-slate-100 pb-3">Formulario de Edición</h3>

        <form method="POST" action="{{ route('admin.activos.update', $activo->id_activo) }}" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Categoría *</label>
                <select name="fk_id_categoria" id="selectCategoria" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                    <option value="">Seleccione una categoría</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id_categoria }}" data-nombre="{{ strtolower($cat->nombre_categoria) }}" {{ old('fk_id_categoria', $activo->fk_id_categoria) == $cat->id_categoria ? 'selected' : '' }}>
                            {{ $cat->nombre_categoria }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Ubicación *</label>
                <select name="fk_id_ubicacion" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                    <option value="">Seleccione una ubicación</option>
                    @foreach($ubicaciones as $ub)
                        <option value="{{ $ub->id_ubicacion }}" {{ old('fk_id_ubicacion', $activo->fk_id_ubicacion) == $ub->id_ubicacion ? 'selected' : '' }}>
                            {{ $ub->seccion }} - {{ $ub->aula }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Código Patrimonial *</label>
                <input type="text" name="codigo_patrimonial" value="{{ old('codigo_patrimonial', $activo->codigo_patrimonial) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Nombre del Equipo *</label>
                <input type="text" name="nombre_equipo" value="{{ old('nombre_equipo', $activo->nombre_equipo) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Marca</label>
                <input type="text" name="marca" value="{{ old('marca', $activo->marca) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Modelo</label>
                <input type="text" name="modelo" value="{{ old('modelo', $activo->modelo) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Fecha de Adquisición</label>
                <input type="date" name="fecha_adquisicion" value="{{ old('fecha_adquisicion', $activo->fecha_adquisicion) }}" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition cursor-pointer" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Estado Operativo *</label>
                <select name="estado_operativo" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                    <option value="Operativo" {{ old('estado_operativo', $activo->estado_operativo) == 'Operativo' ? 'selected' : '' }}>Operativo</option>
                    <option value="En Mantenimiento" {{ old('estado_operativo', $activo->estado_operativo) == 'En Mantenimiento' ? 'selected' : '' }}>En Mantenimiento</option>
                    <option value="Inoperativo" {{ old('estado_operativo', $activo->estado_operativo) == 'Inoperativo' ? 'selected' : '' }}>Inoperativo</option>
                    <option value="De Baja" {{ old('estado_operativo', $activo->estado_operativo) == 'De Baja' ? 'selected' : '' }}>De Baja</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Etapa Ciclo de Vida *</label>
                <select name="etapa_ciclo_vida" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition" required>
                    <option value="Operación" {{ old('etapa_ciclo_vida', $activo->etapa_ciclo_vida) == 'Operación' ? 'selected' : '' }}>Operación</option>
                    <option value="Mantenimiento" {{ old('etapa_ciclo_vida', $activo->etapa_ciclo_vida) == 'Mantenimiento' ? 'selected' : '' }}>Mantenimiento</option>
                    <option value="Renovación / Retiro" {{ old('etapa_ciclo_vida', $activo->etapa_ciclo_vida) == 'Renovación / Retiro' ? 'selected' : '' }}>Renovación / Retiro</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Criticidad Institucional</label>
                <select name="criticidad_institucional" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">
                    <option value="">Seleccione criticidad</option>
                    <option value="Alta" {{ old('criticidad_institucional', $activo->criticidad_institucional) == 'Alta' ? 'selected' : '' }}>Alta</option>
                    <option value="Media" {{ old('criticidad_institucional', $activo->criticidad_institucional) == 'Media' ? 'selected' : '' }}>Media</option>
                    <option value="Baja" {{ old('criticidad_institucional', $activo->criticidad_institucional) == 'Baja' ? 'selected' : '' }}>Baja</option>
                </select>
            </div>

            <!-- ===== SECCIÓN DINÁMICA: COMPUTADORA ===== -->
            <div id="secComputadora" class="md:col-span-2 hidden bg-slate-50 border border-blue-100 rounded-2xl p-5 space-y-4">
                <div class="flex items-center gap-2 text-blue-700 font-semibold text-sm border-b border-blue-100 pb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <span>Especificaciones de Computadora / PC</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Procesador</label>
                        <input type="text" name="procesador" value="{{ old('procesador', $activo->detalleComputadora->procesador ?? '') }}" placeholder="Ej: Intel Core i7 12700K" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Memoria RAM (GB)</label>
                        <input type="number" name="ram_gb" value="{{ old('ram_gb', $activo->detalleComputadora->ram_gb ?? '') }}" placeholder="Ej: 16" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Disco Sólido SSD (GB)</label>
                        <input type="number" name="disco_solido_gb" value="{{ old('disco_solido_gb', $activo->detalleComputadora->disco_solido_gb ?? '') }}" placeholder="Ej: 512" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Disco Rígido HDD (GB)</label>
                        <input type="number" name="disco_rigido_gb" value="{{ old('disco_rigido_gb', $activo->detalleComputadora->disco_rigido_gb ?? '') }}" placeholder="Ej: 1000" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Sistema Operativo</label>
                        <input type="text" name="sistema_operativo" value="{{ old('sistema_operativo', $activo->detalleComputadora->sistema_operativo ?? '') }}" placeholder="Ej: Windows 11 Pro" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                    </div>
                </div>

                <div class="pt-3 border-t border-blue-100">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Componentes y Periféricos Incluidos (Sí / No)</label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                        <label class="flex items-center gap-2 p-2.5 bg-white rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition">
                            <input type="checkbox" name="monitor" value="1" {{ old('monitor', $activo->detalleComputadora->monitor ?? false) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500" />
                            <span class="text-xs font-semibold text-slate-700">Monitor</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 bg-white rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition">
                            <input type="checkbox" name="teclado_mouse" value="1" {{ old('teclado_mouse', $activo->detalleComputadora->teclado_mouse ?? false) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500" />
                            <span class="text-xs font-semibold text-slate-700">Teclado / Mouse</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 bg-white rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition">
                            <input type="checkbox" name="camara_web" value="1" {{ old('camara_web', $activo->detalleComputadora->camara_web ?? false) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500" />
                            <span class="text-xs font-semibold text-slate-700">Cámara Web</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 bg-white rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition">
                            <input type="checkbox" name="parlantes" value="1" {{ old('parlantes', $activo->detalleComputadora->parlantes ?? false) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500" />
                            <span class="text-xs font-semibold text-slate-700">Parlantes</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 bg-white rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition">
                            <input type="checkbox" name="lector" value="1" {{ old('lector', $activo->detalleComputadora->lector ?? false) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500" />
                            <span class="text-xs font-semibold text-slate-700">Lector</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- ===== SECCIÓN DINÁMICA: EQUIPO DE RED ===== -->
            <div id="secEquipoRed" class="md:col-span-2 hidden bg-slate-50 border border-emerald-100 rounded-2xl p-5 space-y-4">
                <div class="flex items-center gap-2 text-emerald-700 font-semibold text-sm border-b border-emerald-100 pb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span>Especificaciones de Equipo de Red / Servidor / Impresora</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Dirección IP</label>
                        <input type="text" name="direccion_ip" value="{{ old('direccion_ip', $activo->detalleEquipoRed->direccion_ip ?? '') }}" placeholder="Ej: 192.168.1.50" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Versión de Firmware</label>
                        <input type="text" name="version_firmware" value="{{ old('version_firmware', $activo->detalleEquipoRed->version_firmware ?? '') }}" placeholder="Ej: v2.4.1" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                    </div>
                </div>
            </div>

            <!-- ===== SECCIÓN DINÁMICA: PROYECTOR ===== -->
            <div id="secProyector" class="md:col-span-2 hidden bg-slate-50 border border-amber-100 rounded-2xl p-5 space-y-4">
                <div class="flex items-center gap-2 text-amber-700 font-semibold text-sm border-b border-amber-100 pb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span>Especificaciones de Proyector Multimedia</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Tipo de Cable / Conectividad</label>
                        <input type="text" name="tipo_cable" value="{{ old('tipo_cable', $activo->detalleProyector->tipo_cable ?? '') }}" placeholder="Ej: HDMI, VGA, USB-C, Inalámbrico" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Calidad de Imagen / Lúmenes</label>
                        <input type="number" name="calidad_imagen" value="{{ old('calidad_imagen', $activo->detalleProyector->calidad_imagen ?? '') }}" placeholder="Ej: 3600" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 transition" />
                    </div>
                </div>
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Observaciones</label>
                <textarea name="observaciones" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition">{{ old('observaciones', $activo->observaciones) }}</textarea>
            </div>

            <div class="md:col-span-2 flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.activos.inventario') }}" class="btn btn-outline">
                    Cancelar
                </a>
                <button type="submit" class="btn btn-primary">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        function initDynamicSections() {
            var selectCategoria = document.getElementById('selectCategoria');
            var secComp = document.getElementById('secComputadora');
            var secRed = document.getElementById('secEquipoRed');
            var secProy = document.getElementById('secProyector');

            if (!selectCategoria) return;

            function toggleDynamicSections() {
                var selectedOpt = selectCategoria.options[selectCategoria.selectedIndex];
                var nombreCat = selectedOpt ? (selectedOpt.getAttribute('data-nombre') || selectedOpt.text.toLowerCase()) : '';

                // Ocultar todas las secciones dinámicas
                if (secComp) secComp.classList.add('hidden');
                if (secRed) secRed.classList.add('hidden');
                if (secProy) secProy.classList.add('hidden');

                // Evaluar según el nombre de la categoría elegida
                if (
                    nombreCat.includes('computadora') ||
                    nombreCat.includes('computador') ||
                    nombreCat.includes('ordenador') ||
                    nombreCat.includes('pc') ||
                    nombreCat.includes('laptop') ||
                    nombreCat.includes('desktop') ||
                    nombreCat.includes('escritorio')
                ) {
                    if (secComp) secComp.classList.remove('hidden');
                } else if (
                    nombreCat.includes('red') ||
                    nombreCat.includes('servidor') ||
                    nombreCat.includes('switch') ||
                    nombreCat.includes('router') ||
                    nombreCat.includes('impresora') ||
                    nombreCat.includes('printer')
                ) {
                    if (secRed) secRed.classList.remove('hidden');
                } else if (nombreCat.includes('proyector')) {
                    if (secProy) secProy.classList.remove('hidden');
                }
            }

            selectCategoria.removeEventListener('change', toggleDynamicSections);
            selectCategoria.addEventListener('change', toggleDynamicSections);
            toggleDynamicSections();
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDynamicSections);
        } else {
            initDynamicSections();
        }

        document.addEventListener('change', function (e) {
            if (e.target && e.target.id === 'selectCategoria') {
                initDynamicSections();
            }
        });
    })();
</script>
@endsection
