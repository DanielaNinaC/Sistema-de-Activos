<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-950 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sistema de Activos Tecnológicos')</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 flex flex-col justify-between antialiased selection:bg-indigo-500 selection:text-white">

    <main class="flex-grow flex items-center justify-center relative overflow-hidden">
        <!-- Glow accents -->
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"></div>

        @yield('content')
    </main>

    <footer class="py-4 text-center text-xs text-slate-500 border-t border-slate-800/50 backdrop-blur">
        &copy; {{ date('Y') }} Sistema de Activos Tecnológicos - Todos los derechos reservados.
    </footer>

</body>
</html>
