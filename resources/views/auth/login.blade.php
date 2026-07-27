@extends('layouts.app')

@section('title', 'Iniciar Sesión - Sistema de Activos')

@section('content')
<div class="w-full max-w-md p-6 sm:p-8">
    <div class="bg-white backdrop-blur-sm border border-gray-200 shadow-md rounded-3xl p-8 space-y-6">
        
        <!-- Header -->
        <div class="text-center space-y-2">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Sistema de Activos</h1>
            <p class="text-sm text-slate-500">Ingresa tus credenciales para acceder</p>
        </div>

        <!-- Session Status Alert -->
        @if (session('status'))
            <div class="p-3 text-sm text-emerald-700 bg-emerald-100 border border-emerald-200 rounded-xl text-center">
                {{ session('status') }}
            </div>
        @endif

        <!-- General Error Alert -->
        @if ($errors->any())
            <div class="p-4 text-sm text-rose-700 bg-rose-100 border border-rose-200 rounded-xl space-y-1">
                <p class="font-semibold">Ocurrió un error al iniciar sesión:</p>
                <ul class="list-disc list-inside text-xs space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Login Form (white panel) -->
        <div class="bg-white rounded-xl">
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf
            <!-- Correo Electrónico -->
            <div class="space-y-1.5">
                <label for="correo" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                    Correo Electrónico
                </label>
                <div class="relative">
                    <input 
                        type="email" 
                        id="correo" 
                        name="correo" 
                        value="{{ old('correo') }}" 
                        required 
                        autofocus
                        placeholder="usuario@dominio.com"
                        class="w-full px-4 py-3 bg-gray-50 border @error('correo') border-rose-500 @else border-gray-200 @enderror rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                    >
                </div>
            </div>

            <!-- Contraseña -->
            <div class="space-y-1.5">
                <label for="contrasena" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                    Contraseña
                </label>
                <div class="relative">
                    <input 
                        type="password" 
                        id="contrasena" 
                        name="contrasena" 
                        required 
                        placeholder="••••••••"
                        class="w-full px-4 py-3 bg-gray-50 border @error('contrasena') border-rose-500 @else border-gray-200 @enderror rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                    >

                    <button type="button" id="togglePassword" aria-label="Mostrar contraseña" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 eye-closed hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.958 9.958 0 012.223-3.432" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.1 6.1L17.9 17.9" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.88 9.88A3 3 0 0114.12 14.12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Código cache / Captcha -->
            <div class="space-y-1.5">
                <div class="flex justify-between items-center">
                    <label for="codigo_cache" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                        Código de Seguridad
                    </label>
                </div>

                <!-- Muestra visual de la imagen del código -->
                <div class="flex items-center gap-2">
                    <img id="captcha-img" 
                         src="{{ route('captcha.image') }}" 
                         alt="Código de seguridad" 
                         class="h-10 rounded-lg cursor-pointer hover:opacity-90 transition"
                         title="Haz clic para generar un nuevo código"
                         onclick="refreshCaptcha()"
                    >
                    <button type="button" 
                            id="btn-refresh-captcha" 
                            onclick="refreshCaptcha()"
                            title="Recargar código"
                            class="p-2 text-slate-500 hover:text-indigo-600 rounded-lg transition focus:outline-none"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>

                <div class="relative">
                    <input 
                        type="text" 
                        id="codigo_cache" 
                        name="codigo_cache" 
                        value="{{ old('codigo_cache') }}" 
                        required
                        autocomplete="off"
                        placeholder="Ingresa el código de 5 caracteres"
                        class="w-full px-4 py-3 bg-gray-50 border @error('codigo_cache') border-rose-500 @else border-gray-200 @enderror rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                    >
                </div>
            </div>

            <!-- Remember me -->
            <!-- <div class="flex items-center justify-between text-xs">
                <label class="flex items-center gap-2 text-slate-600 cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="remember" 
                        class="w-4 h-4 rounded bg-white border-gray-300 text-indigo-600 focus:ring-indigo-500"
                    >
                    <span>Recordar mi sesión</span>
                </label>
            </div> -->

            <!-- Submit Button -->
            <button 
                type="submit" 
                class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-200 hover:shadow-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white transition-all duration-200"
            >
                Iniciar Sesión
            </button>
        </form>

            <script>
                function refreshCaptcha() {
                    var img = document.getElementById('captcha-img');
                    if (img) {
                        img.src = "{{ route('captcha.image') }}?t=" + new Date().getTime();
                    }
                }

                document.addEventListener('DOMContentLoaded', function () {
                    var input = document.getElementById('contrasena');
                    var toggle = document.getElementById('togglePassword');
                    if (!input || !toggle) return;
                    var eyeOpen = toggle.querySelector('.eye-open');
                    var eyeClosed = toggle.querySelector('.eye-closed');
                    toggle.addEventListener('click', function () {
                        var type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                        input.setAttribute('type', type);
                        if (eyeOpen) eyeOpen.classList.toggle('hidden');
                        if (eyeClosed) eyeClosed.classList.toggle('hidden');
                        toggle.setAttribute('aria-pressed', type === 'text');
                    });
                });
            </script>
        </div>

    </div>
</div>
@endsection
