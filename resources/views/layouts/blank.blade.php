<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Error') · M&amp;A Computers</title>
    <link rel="icon" href="{{ asset('images/logo-mark.jpg') }}">
    <script>
        (function () {
            var t = localStorage.getItem('tema');
            if (t === 'oscuro' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-canvas px-4 font-sans text-ink">
    <a href="{{ auth()->check() ? route('inicio') : route('login') }}" class="mb-8">
        <img src="{{ asset('images/logo.jpg') }}" alt="M&amp;A Computers" class="h-14 rounded-lg object-contain">
    </a>
    @yield('contenido')
</body>
</html>
