<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · M&amp;A Computers</title>
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
<body class="flex min-h-screen items-center justify-center bg-canvas px-4 font-sans text-ink antialiased">
<main class="w-full max-w-sm">
    <div class="mb-8 flex flex-col items-center text-center">
        <img src="{{ asset('images/logo.jpg') }}" alt="M&amp;A Computers" class="mb-4 h-16 rounded-lg object-contain">
        <h1 class="text-2xl font-medium tracking-tight">Iniciar sesión</h1>
        <p class="mt-1 text-sm text-ink-muted">Sistema de inventario · M&amp;A Computers</p>
    </div>

    <form method="POST" action="{{ route('login.store') }}" class="card space-y-5">
        @csrf

        @if ($errors->any())
            <div role="alert" class="rounded-md border border-semantic-error px-3 py-2 text-sm text-semantic-error">
                {{ $errors->first() }}
            </div>
        @endif

        <div>
            <label for="username" class="label">Usuario</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus
                   class="input" aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}">
        </div>

        <div>
            <label for="password" class="label">Contraseña</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required class="input">
        </div>

        <button type="submit" class="btn btn-primary w-full">Ingresar</button>
    </form>

    <p class="mt-6 text-center text-xs text-ink-subtle">Una sola sesión activa por usuario. Al ingresar en otro equipo se cierra la anterior.</p>
</main>
</body>
</html>
