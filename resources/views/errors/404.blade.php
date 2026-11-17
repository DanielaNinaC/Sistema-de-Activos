@extends('layouts.app')

@section('title', 'Página no encontrada - SIATEC')

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

        <!-- Icono 404 -->
        <div class="mx-auto w-20 h-20 bg-indigo-50 text-indigo-600 rounded-3xl flex items-center justify-center shadow-inner border border-indigo-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <div class="space-y-2">
            <span class="inline-block px-3 py-1 bg-indigo-100 text-indigo-800 text-xs font-bold uppercase tracking-wider rounded-full">
                Error 404
            </span>
            <h1 class="text-2xl font-black text-slate-900">Página No Encontrada</h1>
            <p class="text-sm text-slate-500 leading-relaxed">
                El recurso o enlace al que intentas acceder no existe, fue movido o ha cambiado de dirección.
            </p>
        </div>

        <div class="pt-2 flex flex-col sm:flex-row gap-3">
            <a href="javascript:history.back()" class="w-full sm:w-1/2 inline-flex items-center justify-center px-4 py-3 rounded-2xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
                Volver Atrás
            </a>
            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="w-full sm:w-1/2 inline-flex items-center justify-center px-4 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-md hover:shadow-indigo-200 transition">
                Ir al Inicio
            </a>
        </div>
    </div>
</div>
@endsection
