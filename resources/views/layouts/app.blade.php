<!DOCTYPE html>
<html lang="es" class="min-h-dvh bg-slate-950 text-slate-100">
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
{{--
    Shell mínimo para las pantallas de autenticación: la vista hija controla su
    propio ancho/alto (el login usa un layout a pantalla completa de dos
    paneles), por eso aquí no se centra ni se recorta el contenido —
    `overflow-hidden` a este nivel impedía hacer scroll y recortaba el
    formulario en pantallas de poca altura.
--}}
<body class="min-h-dvh bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 antialiased selection:bg-indigo-500 selection:text-white">

    @yield('content')

</body>
</html>
