@extends('layouts.app')

@section('title', 'Sesión Expirada - SIATEC')

@section('content')
<div class="min-h-dvh flex items-center justify-center p-4 sm:p-6 bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950">
    <div class="max-w-md w-full bg-white rounded-3xl p-8 sm:p-10 shadow-2xl border border-slate-100 text-center space-y-6">
        
        <!-- Logo SIATEC & Colegio -->
        <div class="flex items-center justify-center gap-3">
            <div class="h-12 w-28 bg-white rounded-xl p-1.5 shadow-sm border border-slate-100 flex items-center justify-center">
                <img src="{{ asset('imagenes/logoFranco.jpg') }}" alt="Franco Bolivien" class="h-full w-full object-contain">
            </div>
            <div class="h-12 w-28 bg-white rounded-xl p-1.5 shadow-sm border border-slate-100 flex items-center justify-center">
                <img src="{{ asset('imagenes/siatec.png') }}" alt="SIATEC" class="h-full w-full object-contain">
            </div>
        </div>

        <!-- Icono con animación -->
        <div class="mx-auto w-20 h-20 bg-amber-50 text-amber-500 rounded-3xl flex items-center justify-center shadow-inner border border-amber-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <div class="space-y-2">
            <span class="inline-block px-3 py-1 bg-amber-100 text-amber-800 text-xs font-bold uppercase tracking-wider rounded-full">
                Tiempo de espera finalizado (419)
            </span>
            <h1 class="text-2xl font-black text-slate-900">Sesión Expirada</h1>
            <p class="text-sm text-slate-500 leading-relaxed">
                Por motivos de seguridad, la sesión caduca automáticamente tras <strong>30 minutos de inactividad</strong>.
            </p>
        </div>

        <div class="pt-2">
            <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md hover:shadow-indigo-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                <span>Iniciar Sesión Nuevamente</span>
            </a>
        </div>
    </div>
</div>
@endsection
