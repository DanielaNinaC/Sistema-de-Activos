@extends('layouts.principal')

@section('title', 'Configuración de Evaluación - Gestión de Riesgos')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-center gap-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline btn-icon" aria-label="Volver" title="Volver">
            <span aria-hidden="true">&#8592;</span>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-slate-900">Configuración de Evaluación</h2>
            <p class="text-sm text-slate-500">Parámetros que alimentan el motor FMEA-MSR</p>
        </div>
    </div>

    {{-- Contexto de la sección --}}
    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 flex items-start gap-3">
        <span class="w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-500 flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </span>
        <p class="text-sm text-slate-600 leading-relaxed">
            Aquí se definen los dos insumos con los que el motor calcula el riesgo de cada activo:
            los <strong class="font-semibold text-slate-800">modos de falla</strong> aportan la Severidad y el nivel de
            Monitoreo (MSR) de cada falla posible, y el <strong class="font-semibold text-slate-800">Score</strong>
            establece cuánto pesa cada factor en el resultado. La Frecuencia no se configura: el motor la deduce del
            desgaste del activo y de sus incidentes registrados.
            <span class="block mt-1.5 text-slate-500">
                Los cambios aplican a las evaluaciones que se ejecuten a partir de ese momento; las ya registradas
                conservan el score con el que fueron calculadas.
            </span>
        </p>
    </div>

    {{-- Cada opción es un botón del sistema (btn + btn-tile), no marcado a medida --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        {{-- Modos de Falla --}}
        <a href="{{ route('admin.riesgos.modos') }}" class="btn btn-outline btn-tile group hover:border-blue-400">
            <div class="flex items-center gap-3">
                <span class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:bg-blue-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19H18.93a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z" />
                    </svg>
                </span>
                <span class="flex-1 min-w-0">
                    <span class="block text-base font-bold text-slate-900 leading-tight">Modos de Falla</span>
                    <span class="block text-xs font-medium text-slate-500 mt-0.5">Catálogo por categoría de activo</span>
                </span>
                <span class="text-slate-300 group-hover:text-blue-500 group-hover:translate-x-0.5 transition text-lg shrink-0" aria-hidden="true">&#8594;</span>
            </div>

            <p class="text-xs font-normal text-slate-500 leading-relaxed">
                Define las fallas posibles y su nivel de Severidad y Monitoreo (MSR), según las Tablas 20 y 22.
            </p>

            <div class="flex flex-wrap items-center gap-1.5">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                    {{ $totalModos }} {{ $totalModos == 1 ? 'modo' : 'modos' }}
                </span>
                @if($categoriasSinModos > 0)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200"
                          title="Los activos de esas categorías no pueden evaluarse hasta que tengan modos de falla">
                        {{ $categoriasSinModos }} sin cubrir
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Todas las categorías cubiertas
                    </span>
                @endif
            </div>
        </a>

        {{-- Score (pesos AHP) --}}
        <a href="{{ route('admin.riesgos.score') }}" class="btn btn-outline btn-tile group hover:border-indigo-400">
            <div class="flex items-center gap-3">
                <span class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 group-hover:bg-indigo-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </span>
                <span class="flex-1 min-w-0">
                    <span class="block text-base font-bold text-slate-900 leading-tight">Score</span>
                    <span class="block text-xs font-medium text-slate-500 mt-0.5">Ponderación AHP</span>
                </span>
                <span class="text-slate-300 group-hover:text-indigo-500 group-hover:translate-x-0.5 transition text-lg shrink-0" aria-hidden="true">&#8594;</span>
            </div>

            <p class="text-xs font-normal text-slate-500 leading-relaxed">
                Ajusta el peso del riesgo técnico (Severidad, Frecuencia, MSR) frente a la dependencia institucional.
            </p>

            <div class="flex flex-wrap items-center gap-1.5">
                @if($pesosVigentes)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Vigente
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-mono font-bold bg-slate-100 text-slate-700 border border-slate-200">
                        S {{ $pesosVigentes->peso_severidad }} · F {{ $pesosVigentes->peso_frecuencia }} · MSR {{ $pesosVigentes->peso_msr }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                        Sin configuración vigente
                    </span>
                @endif
            </div>
        </a>

    </div>
</div>
@endsection
