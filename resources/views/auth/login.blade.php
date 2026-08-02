@extends('layouts.app')

@section('title', 'Iniciar Sesión - Sistema de Activos')

@section('content')
@if (session('registered_success'))
    <!-- Modal Emergente de Registro Exitoso (flotante, ancho idéntico al formulario) -->
    <div id="registerSuccessModal" onclick="closeRegisterModal()" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 transition-all duration-300 pointer-events-auto">
        <div class="w-full max-w-md">
            <div onclick="event.stopPropagation()" class="relative bg-white rounded-3xl shadow-2xl border border-slate-200 w-full p-5 sm:p-6 text-center animate-in fade-in zoom-in-95">
                <!-- Botón cerrar (X) -->
                <button type="button" onclick="closeRegisterModal()" aria-label="Cerrar" class="absolute top-4 right-4 p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Icono animado -->
                <div class="mx-auto w-16 h-16 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mb-4 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                
                <h3 class="text-xl font-bold text-slate-900 mb-2">¡Registro Exitoso!</h3>
                <p class="text-sm text-slate-600 leading-relaxed">
                    {{ session('registered_success') }}
                </p>
            </div>
        </div>
    </div>
@endif

<div id="authCardContainer" class="w-full max-w-md transition-all duration-300 p-4 sm:p-6">
    <div class="bg-white backdrop-blur-sm border border-gray-200 shadow-md rounded-3xl p-5 sm:p-6 space-y-4">
        
        <!-- Header -->
        <div class="text-center space-y-2">
            <h1 id="authTitle" class="text-2xl font-bold tracking-tight text-slate-900 transition-all duration-200">Sistema de Activos</h1>
            <p id="authSubtitle" class="text-sm text-slate-500 transition-all duration-200">Ingresa tus credenciales para acceder</p>
        </div>

        <!-- Session Status Alert -->
        @if (session('status'))
            <div class="p-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-xl text-center flex items-center justify-center gap-2 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
        @endif

        <!-- Form (white panel) -->
        <div class="bg-white rounded-xl">
            <form id="loginForm" method="POST" action="{{ route('login') }}" class="space-y-5" novalidate data-turbo-disabled="true">
            @csrf
            <input type="hidden" name="auth_mode" id="auth_mode_input" value="{{ old('auth_mode', 'login') }}">
            
            <div id="formFieldsGrid" class="space-y-4 transition-all duration-300">
                <!-- Nombre (solo en modo registro) -->
                <div id="field-nombre" class="hidden space-y-1.5">
                    <label for="nombre" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                        Nombre
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="nombre" 
                            name="nombre" 
                            value="{{ old('nombre') }}" 
                            placeholder="Ej. Juan"
                            class="w-full px-4 py-3 bg-gray-50 border @error('nombre') border-rose-500 ring-2 ring-rose-200 @else border-gray-200 @enderror rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                        >
                    </div>
                    <div id="error-nombre-container">
                        @error('nombre')
                            <p class="mt-1.5 text-xs text-rose-600 font-semibold flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Apellido (solo en modo registro) -->
                <div id="field-apellido" class="hidden space-y-1.5">
                    <label for="apellido" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                        Apellido
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="apellido" 
                            name="apellido" 
                            value="{{ old('apellido') }}" 
                            placeholder="Ej. Pérez Gómez"
                            class="w-full px-4 py-3 bg-gray-50 border @error('apellido') border-rose-500 ring-2 ring-rose-200 @else border-gray-200 @enderror rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                        >
                    </div>
                    <div id="error-apellido-container">
                        @error('apellido')
                            <p class="mt-1.5 text-xs text-rose-600 font-semibold flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Correo Electrónico -->
                <div id="field-correo" class="space-y-1.5">
                    <label for="correo" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                        Correo Electrónico
                    </label>
                    <div class="relative">
                        <input 
                            type="email" 
                            id="correo" 
                            name="correo" 
                            value="{{ old('correo') }}" 
                            autofocus
                            placeholder="usuario@dominio.com"
                            class="w-full px-4 py-3 bg-gray-50 border @error('correo') border-rose-500 ring-2 ring-rose-200 @else border-gray-200 @enderror rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                        >
                    </div>
                    <div id="error-correo-container">
                        @error('correo')
                            <p class="mt-1.5 text-xs text-rose-600 font-semibold flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Contraseña -->
                <div id="field-contrasena" class="space-y-1.5">
                    <label for="contrasena" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                        Contraseña
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="contrasena" 
                            name="contrasena" 
                            placeholder="••••••••"
                            class="w-full px-4 py-3 bg-gray-50 border @error('contrasena') border-rose-500 ring-2 ring-rose-200 @else border-gray-200 @enderror rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
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
                    <div id="error-contrasena-container">
                        @error('contrasena')
                            <p class="mt-1.5 text-xs text-rose-600 font-semibold flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Confirmar Contraseña (solo en modo registro) -->
                <div id="field-confirmar" class="hidden space-y-1.5">
                    <label for="contrasena_confirmation" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">
                        Confirmar Contraseña
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="contrasena_confirmation" 
                            name="contrasena_confirmation" 
                            placeholder="••••••••"
                            class="w-full px-4 py-3 bg-gray-50 border @error('contrasena_confirmation') border-rose-500 ring-2 ring-rose-200 @else border-gray-200 @enderror rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                        >

                        <button type="button" id="toggleConfirmPassword" aria-label="Mostrar contraseña" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 confirm-eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 confirm-eye-closed hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.958 9.958 0 012.223-3.432" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.1 6.1L17.9 17.9" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.88 9.88A3 3 0 0114.12 14.12" />
                            </svg>
                        </button>
                    </div>
                    <div id="error-contrasena_confirmation-container">
                        @error('contrasena_confirmation')
                            <p class="mt-1.5 text-xs text-rose-600 font-semibold flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Código cache / Captcha (solo en modo inicio de sesión) -->
                <div id="field-captcha" class="space-y-1.5 sm:col-span-2">
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
                            autocomplete="off"
                            placeholder="Ingresa el código de 5 caracteres"
                            class="w-full px-4 py-3 bg-gray-50 border @error('codigo_cache') border-rose-500 ring-2 ring-rose-200 @else border-gray-200 @enderror rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 transition"
                        >
                    </div>
                    <div id="error-codigo_cache-container">
                        @error('codigo_cache')
                            <p class="mt-1.5 text-xs text-rose-600 font-semibold flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ $message }}</span>
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Submit Button -->
                <div id="btnSubmitContainer" class="sm:col-span-2 pt-2">
                    <button 
                        type="submit" 
                        id="btnSubmit"
                        class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-200 hover:shadow-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white transition-all duration-200"
                    >
                        Iniciar Sesión
                    </button>
                </div>
            </div>
        </form>

        <!-- Hipervínculo para alternar entre Iniciar Sesión y Registrar Cuenta -->
        <div class="pt-4 border-t border-slate-100 text-center">
            <p id="toggleAuthText" class="text-xs text-slate-500">
                ¿No tienes cuenta? 
                <button type="button" id="toggleAuthBtn" class="font-semibold text-indigo-600 hover:text-indigo-500 underline transition focus:outline-none ml-0.5">
                    Regístrate aquí
                </button>
            </p>
        </div>

        <script>
            var currentAuthMode = '{{ (old("auth_mode") === "register" || session("auth_mode") === "register" || $errors->has("nombre") || $errors->has("apellido") || $errors->has("contrasena_confirmation")) ? "register" : "login" }}';

            function closeRegisterModal() {
                var modal = document.getElementById('registerSuccessModal');
                if (modal) {
                    modal.classList.add('opacity-0', 'pointer-events-none');
                    setTimeout(function() {
                        modal.style.display = 'none';
                    }, 300);
                }
                setAuthMode('login');
            }

            function refreshCaptcha() {
                var img = document.getElementById('captcha-img');
                if (img) {
                    img.src = "{{ route('captcha.image') }}?t=" + new Date().getTime();
                }
            }

            function setAuthMode(mode) {
                currentAuthMode = mode;
                var authModeInput = document.getElementById('auth_mode_input');
                if (authModeInput) authModeInput.value = mode;

                var cardContainer = document.getElementById('authCardContainer');
                var formGrid = document.getElementById('formFieldsGrid');
                var form = document.getElementById('loginForm');
                var titleEl = document.getElementById('authTitle');
                var subtitleEl = document.getElementById('authSubtitle');
                var submitBtn = document.getElementById('btnSubmit');
                var toggleText = document.getElementById('toggleAuthText');
                var fieldNombre = document.getElementById('field-nombre');
                var fieldApellido = document.getElementById('field-apellido');
                var fieldConfirmar = document.getElementById('field-confirmar');
                var fieldCaptcha = document.getElementById('field-captcha');

                if (mode === 'register') {
                    if (cardContainer) {
                        cardContainer.classList.remove('max-w-md');
                        cardContainer.classList.add('max-w-2xl');
                    }
                    if (formGrid) {
                        formGrid.className = 'grid grid-cols-1 sm:grid-cols-2 gap-4 transition-all duration-300';
                    }
                    if (form) form.action = "{{ route('register') }}";
                    if (titleEl) titleEl.textContent = 'Registrar Cuenta';
                    if (subtitleEl) subtitleEl.textContent = 'Ingresa tus datos para registrarte en el Sistema de Activos';
                    if (fieldNombre) fieldNombre.classList.remove('hidden');
                    if (fieldApellido) fieldApellido.classList.remove('hidden');
                    if (fieldConfirmar) fieldConfirmar.classList.remove('hidden');
                    if (fieldCaptcha) fieldCaptcha.classList.add('hidden');
                    if (submitBtn) {
                        submitBtn.textContent = 'Registrar Cuenta';
                        submitBtn.disabled = false;
                        submitBtn.className = 'w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-200 hover:shadow-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white transition-all duration-200';
                    }
                    if (toggleText) {
                        toggleText.innerHTML = '¿Ya tienes una cuenta? <button type="button" id="toggleAuthBtn" class="font-semibold text-indigo-600 hover:text-indigo-500 underline transition focus:outline-none ml-0.5">Inicia sesión aquí</button>';
                        bindToggleBtn();
                    }
                } else {
                    if (cardContainer) {
                        cardContainer.classList.remove('max-w-2xl');
                        cardContainer.classList.add('max-w-md');
                    }
                    if (formGrid) {
                        formGrid.className = 'space-y-4 transition-all duration-300';
                    }
                    if (form) form.action = "{{ route('login') }}";
                    if (titleEl) titleEl.textContent = 'Sistema de Activos';
                    if (subtitleEl) subtitleEl.textContent = 'Ingresa tus credenciales para acceder';
                    if (fieldNombre) fieldNombre.classList.add('hidden');
                    if (fieldApellido) fieldApellido.classList.add('hidden');
                    if (fieldConfirmar) fieldConfirmar.classList.add('hidden');
                    if (fieldCaptcha) fieldCaptcha.classList.remove('hidden');
                    if (submitBtn) {
                        submitBtn.textContent = 'Iniciar Sesión';
                        submitBtn.disabled = false;
                        submitBtn.className = 'w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-200 hover:shadow-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-white transition-all duration-200';
                    }
                    if (toggleText) {
                        toggleText.innerHTML = '¿No tienes cuenta? <button type="button" id="toggleAuthBtn" class="font-semibold text-indigo-600 hover:text-indigo-500 underline transition focus:outline-none ml-0.5">Regístrate aquí</button>';
                        bindToggleBtn();
                    }
                }
            }

            function toggleAuthMode() {
                setAuthMode(currentAuthMode === 'login' ? 'register' : 'login');
            }

            function bindToggleBtn() {
                var btn = document.getElementById('toggleAuthBtn');
                if (btn) {
                    btn.onclick = toggleAuthMode;
                }
            }

            function initLoginValidation() {
                var form = document.getElementById('loginForm');
                var nombreInput = document.getElementById('nombre');
                var apellidoInput = document.getElementById('apellido');
                var correoInput = document.getElementById('correo');
                var contrasenaInput = document.getElementById('contrasena');
                var confirmInput = document.getElementById('contrasena_confirmation');
                var captchaInput = document.getElementById('codigo_cache');

                // Autostart correct mode
                setAuthMode(currentAuthMode);

                // Auto modal handling if registerSuccessModal exists
                var modalEl = document.getElementById('registerSuccessModal');
                if (modalEl) {
                    setAuthMode('login');
                }

                // Password toggle
                var toggle = document.getElementById('togglePassword');
                if (contrasenaInput && toggle) {
                    var eyeOpen = toggle.querySelector('.eye-open');
                    var eyeClosed = toggle.querySelector('.eye-closed');
                    toggle.addEventListener('click', function () {
                        var type = contrasenaInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        contrasenaInput.setAttribute('type', type);
                        if (eyeOpen) eyeOpen.classList.toggle('hidden');
                        if (eyeClosed) eyeClosed.classList.toggle('hidden');
                    });
                }

                // Confirm password toggle
                var toggleConfirm = document.getElementById('toggleConfirmPassword');
                if (confirmInput && toggleConfirm) {
                    var confirmEyeOpen = toggleConfirm.querySelector('.confirm-eye-open');
                    var confirmEyeClosed = toggleConfirm.querySelector('.confirm-eye-closed');
                    toggleConfirm.addEventListener('click', function () {
                        var type = confirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        confirmInput.setAttribute('type', type);
                        if (confirmEyeOpen) confirmEyeOpen.classList.toggle('hidden');
                        if (confirmEyeClosed) confirmEyeClosed.classList.toggle('hidden');
                    });
                }

                function showError(fieldName, inputEl, message) {
                    if (inputEl) {
                        inputEl.classList.add('border-rose-500', 'ring-2', 'ring-rose-200');
                        inputEl.classList.remove('border-gray-200');
                    }
                    var container = document.getElementById('error-' + fieldName + '-container');
                    if (container) {
                        container.innerHTML = '<p class="mt-1.5 text-xs text-rose-600 font-semibold flex items-center gap-1.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span>' + message + '</span></p>';
                    }
                }

                function clearError(fieldName, inputEl) {
                    if (inputEl) {
                        inputEl.classList.remove('border-rose-500', 'ring-2', 'ring-rose-200');
                        inputEl.classList.add('border-gray-200');
                    }
                    var container = document.getElementById('error-' + fieldName + '-container');
                    if (container) {
                        container.innerHTML = '';
                    }
                }

                // Clear field errors on input
                if (nombreInput) nombreInput.addEventListener('input', function () { clearError('nombre', nombreInput); });
                if (apellidoInput) apellidoInput.addEventListener('input', function () { clearError('apellido', apellidoInput); });
                if (correoInput) correoInput.addEventListener('input', function () { clearError('correo', correoInput); });
                if (contrasenaInput) contrasenaInput.addEventListener('input', function () { clearError('contrasena', contrasenaInput); });
                if (confirmInput) confirmInput.addEventListener('input', function () { clearError('contrasena_confirmation', confirmInput); });
                if (captchaInput) captchaInput.addEventListener('input', function () { clearError('codigo_cache', captchaInput); });

                // Validate form submission
                if (form) {
                    form.addEventListener('submit', function (e) {
                        var hasErrors = false;

                        var nombreVal = nombreInput ? nombreInput.value.trim() : '';
                        var apellidoVal = apellidoInput ? apellidoInput.value.trim() : '';
                        var correoVal = correoInput ? correoInput.value.trim() : '';
                        var contrasenaVal = contrasenaInput ? contrasenaInput.value.trim() : '';
                        var confirmVal = confirmInput ? confirmInput.value.trim() : '';
                        var captchaVal = captchaInput ? captchaInput.value.trim() : '';

                        if (currentAuthMode === 'register') {
                            if (!nombreVal) {
                                showError('nombre', nombreInput, 'El nombre es obligatorio.');
                                hasErrors = true;
                            }
                            if (!apellidoVal) {
                                showError('apellido', apellidoInput, 'El apellido es obligatorio.');
                                hasErrors = true;
                            }
                            if (!correoVal) {
                                showError('correo', correoInput, 'El correo electrónico es obligatorio.');
                                hasErrors = true;
                            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correoVal)) {
                                showError('correo', correoInput, 'Ingrese una dirección de correo electrónico válida.');
                                hasErrors = true;
                            }
                            if (!contrasenaVal) {
                                showError('contrasena', contrasenaInput, 'La contraseña es obligatoria.');
                                hasErrors = true;
                            } else if (contrasenaVal.length < 6) {
                                showError('contrasena', contrasenaInput, 'La contraseña debe tener al menos 6 caracteres.');
                                hasErrors = true;
                            }
                            if (!confirmVal) {
                                showError('contrasena_confirmation', confirmInput, 'La confirmación de la contraseña es obligatoria.');
                                hasErrors = true;
                            } else if (contrasenaVal !== confirmVal) {
                                showError('contrasena_confirmation', confirmInput, 'Las contraseñas no coinciden.');
                                hasErrors = true;
                            }
                        } else {
                            if (!correoVal) {
                                showError('correo', correoInput, 'El correo electrónico es obligatorio.');
                                hasErrors = true;
                            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correoVal)) {
                                showError('correo', correoInput, 'Ingrese una dirección de correo electrónico válida.');
                                hasErrors = true;
                            }
                            if (!contrasenaVal) {
                                showError('contrasena', contrasenaInput, 'La contraseña es obligatoria.');
                                hasErrors = true;
                            }
                            if (!captchaVal) {
                                showError('codigo_cache', captchaInput, 'El código de seguridad es obligatorio.');
                                hasErrors = true;
                            }
                        }

                        if (hasErrors) {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                    });
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initLoginValidation);
            } else {
                initLoginValidation();
            }
        </script>
        </div>

    </div>
</div>
@endsection
