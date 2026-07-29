@extends('layouts.principal')

@section('title', 'Dashboard')

@section('content')
@php
    $categoriaMax = max((int) ($activosPorCategoria->max('total') ?? 1), 1);
    $estadoMax = max((int) ($activosPorEstado->max('total') ?? 1), 1);
    $alertaBadge = fn ($tipo) => match (strtolower($tipo)) {
        'roja' => 'bg-red-100 text-red-800',
        'naranja' => 'bg-orange-100 text-orange-800',
        'amarilla' => 'bg-yellow-100 text-yellow-800',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<div class="space-y-6">
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 text-white shadow-lg">
        {{-- Glow accents matching login --}}
        <div class="absolute -top-20 -left-20 w-72 h-72 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-20 -right-20 w-72 h-72 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative p-8 lg:p-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl">
                <p class="text-sm uppercase tracking-[0.3em] text-white/60 mb-3">Dashboard</p>
                <h2 class="text-3xl lg:text-4xl font-semibold leading-tight">
                    Bienvenido, {{ Auth::user()->nombre ?? Auth::user()->name ?? Auth::user()->correo ?? 'Usuario' }}
                </h2>
                <p class="mt-3 text-white/75 max-w-xl">
                    Vista general de activos, riesgo, incidentes, mantenimientos y alertas para tomar decisiones rápidas.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <a href="{{ route('admin.activos.inventario') }}" class="rounded-xl border border-indigo-400/20 bg-white/10 px-4 py-3 hover:bg-indigo-500/20 transition">Inventario</a>
                <a href="{{ route('admin.riesgos.evaluacion') }}" class="rounded-xl border border-indigo-400/20 bg-white/10 px-4 py-3 hover:bg-indigo-500/20 transition">Evaluación FMEA</a>
                <a href="{{ route('admin.incidentes.registrar') }}" class="rounded-xl border border-indigo-400/20 bg-white/10 px-4 py-3 hover:bg-indigo-500/20 transition">Incidentes</a>
                <a href="{{ route('admin.mantenimiento.programar') }}" class="rounded-xl border border-indigo-400/20 bg-white/10 px-4 py-3 hover:bg-indigo-500/20 transition">Mantenimiento</a>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <article class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-500">Activos registrados</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalActivos }}</p>
                </div>
                <div class="rounded-xl bg-blue-50 text-blue-700 p-3">▣</div>
            </div>
        </article>

        <article class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-500">Riesgo crítico</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $riesgosCriticos }}</p>
                </div>
                <div class="rounded-xl bg-orange-50 text-orange-700 p-3">!</div>
            </div>
        </article>

        <article class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-500">Incidentes abiertos</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $incidentesAbiertos }}</p>
                </div>
                <div class="rounded-xl bg-amber-50 text-amber-700 p-3">⟲</div>
            </div>
        </article>

        <article class="rounded-2xl bg-white p-5 shadow-sm border border-slate-100 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-500">Mantenimientos pendientes</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ $mantenimientosPendientes }}</p>
                </div>
                <div class="rounded-xl bg-emerald-50 text-emerald-700 p-3">⚙</div>
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <article class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Activos por categoría</h3>
                    <p class="text-sm text-slate-500">Distribución principal del inventario</p>
                </div>
            </div>
            <div class="space-y-4">
                @forelse($activosPorCategoria as $item)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="font-medium text-slate-700">{{ $item->nombre }}</span>
                            <span class="text-slate-500">{{ $item->total }}</span>
                        </div>
                        <div class="h-3 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-blue-600 to-sky-400" style="width: {{ max(8, round(($item->total / $categoriaMax) * 100)) }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Todavía no hay datos de categorías.</p>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Estado de activos</h3>
                    <p class="text-sm text-slate-500">Situación operativa actual</p>
                </div>
            </div>
            <div class="space-y-4">
                @forelse($activosPorEstado as $item)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="font-medium text-slate-700">{{ $item->nombre }}</span>
                            <span class="text-slate-500">{{ $item->total }}</span>
                        </div>
                        <div class="h-3 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-600 to-lime-400" style="width: {{ max(8, round(($item->total / $estadoMax) * 100)) }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Todavía no hay datos de estados.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <article class="xl:col-span-2 rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Últimas alertas</h3>
                    <p class="text-sm text-slate-500">Alertas generadas desde el módulo de riesgos</p>
                </div>
            </div>
            <div class="space-y-3">
                @forelse($alertasRecientes as $alerta)
                    <div class="flex items-start gap-4 rounded-xl border border-slate-100 p-4 hover:bg-slate-50 transition">
                        <div class="mt-1 h-3 w-3 rounded-full {{ strtolower($alerta->tipo_alerta) === 'amarilla' ? 'bg-yellow-500' : (strtolower($alerta->tipo_alerta) === 'naranja' ? 'bg-orange-500' : 'bg-red-500') }}"></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $alertaBadge($alerta->tipo_alerta) }}">{{ $alerta->tipo_alerta }}</span>
                                <span class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($alerta->fecha_generacion)->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-700">
                                {{ $alerta->evaluacion->activo->nombre_equipo ?? 'Activo sin nombre' }}
                                <span class="text-slate-400">·</span>
                                Nivel {{ $alerta->evaluacion->nivel_riesgo ?? '-' }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No hay alertas registradas todavía.</p>
                @endforelse
            </div>
        </article>

        <article class="rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
            <div class="mb-5">
                <h3 class="text-lg font-semibold text-slate-900">Resumen de alertas</h3>
                <p class="text-sm text-slate-500">Clasificación por tipo de notificación</p>
            </div>
            <div class="space-y-4">
                <div class="flex items-center justify-between rounded-xl bg-slate-50 p-4">
                    <span class="text-sm font-medium text-slate-700">Rojas</span>
                    <span class="text-lg font-bold text-red-600">{{ $alertasResumen['rojas'] }}</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-slate-50 p-4">
                    <span class="text-sm font-medium text-slate-700">Naranjas</span>
                    <span class="text-lg font-bold text-orange-600">{{ $alertasResumen['naranjas'] }}</span>
                </div>
                <div class="flex items-center justify-between rounded-xl bg-slate-50 p-4">
                    <span class="text-sm font-medium text-slate-700">Amarillas</span>
                    <span class="text-lg font-bold text-yellow-600">{{ $alertasResumen['amarillas'] }}</span>
                </div>
            </div>
        </article>
    </section>
</div>
@endsection
