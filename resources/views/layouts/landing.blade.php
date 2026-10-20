<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sistema de gestión de inventario, ventas y servicio técnico de M&amp;A Computers. Disponibilidad en tiempo real para las dos sucursales.">
    <meta property="og:title" content="M&amp;A Computers · Sistema de inventario">
    <meta property="og:description" content="Todo el stock de las dos sucursales, en una sola pantalla.">
    <meta property="og:type" content="website">
    <title>@yield('titulo', 'Sistema de inventario') · M&amp;A Computers</title>
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
<body class="min-h-screen bg-canvas font-sans text-ink antialiased">
<a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-50 focus:rounded-md focus:bg-ink focus:px-4 focus:py-2 focus:text-on-primary">Saltar al contenido</a>

<header class="sticky top-0 z-40 border-b border-hairline bg-canvas/95 backdrop-blur">
    <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-3 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('landing') }}" class="flex min-w-0 items-center gap-2.5" aria-label="M&amp;A Computers · inicio">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-hairline bg-white">
                <img src="{{ asset('images/logo-mark.jpg') }}" alt="" class="h-full w-full object-cover">
            </span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold leading-tight">M&amp;A Computers</span>
                <span class="hidden text-[11px] leading-tight text-ink-muted sm:block">Sistema de gestión</span>
            </span>
        </a>

        <nav class="hidden items-center gap-1 md:flex" aria-label="Secciones">
            <a href="#sistema" class="landing-nav-link">El sistema</a>
            <a href="#buscador" class="landing-nav-link">Buscador</a>
            <a href="#modulos" class="landing-nav-link">Módulos</a>
            <a href="#como-funciona" class="landing-nav-link">Cómo funciona</a>
        </nav>

        <div class="flex items-center gap-1.5">
            <button type="button" data-toggle-tema class="btn btn-ghost btn-icon" aria-label="Cambiar tema">
                <x-icon name="sun" class="size-5 dark:hidden" />
                <x-icon name="moon" class="hidden size-5 dark:block" />
            </button>
            <a href="{{ route('login') }}" class="btn btn-primary btn-sm">
                <span class="hidden sm:inline">Ingresar al sistema</span>
                <span class="sm:hidden">Ingresar</span>
            </a>
            <button type="button" data-landing-menu-abrir class="btn btn-ghost btn-icon md:hidden" aria-label="Abrir menú" aria-controls="menu-landing" aria-expanded="false">
                <x-icon name="menu" class="size-5" />
            </button>
        </div>
    </div>
</header>

<div id="menu-landing" data-landing-menu hidden class="fixed inset-0 z-50 md:hidden">
    <div class="absolute inset-0 bg-black/50" data-landing-menu-cerrar></div>
    <div class="absolute inset-y-0 right-0 flex w-80 max-w-[85vw] flex-col border-l border-hairline bg-surface-1">
        <div class="flex items-center justify-between border-b border-hairline px-4 py-3">
            <span class="text-sm font-semibold">M&amp;A Computers</span>
            <button type="button" data-landing-menu-cerrar class="btn btn-ghost btn-icon" aria-label="Cerrar menú">
                <x-icon name="x" class="size-5" />
            </button>
        </div>
        <nav class="flex flex-col gap-1 p-4" aria-label="Secciones">
            <a href="#sistema" class="nav-link" data-landing-menu-link>El sistema</a>
            <a href="#buscador" class="nav-link" data-landing-menu-link>Buscador</a>
            <a href="#modulos" class="nav-link" data-landing-menu-link>Módulos</a>
            <a href="#como-funciona" class="nav-link" data-landing-menu-link>Cómo funciona</a>
            <div class="my-2 border-t border-hairline"></div>
            <a href="{{ route('login') }}" class="btn btn-primary mt-1">Ingresar al sistema</a>
        </nav>
    </div>
</div>

<main id="contenido">
    @yield('contenido')
</main>

<footer class="border-t border-hairline bg-canvas">
    <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1.4fr_1fr_1.2fr] lg:px-8">
        <div>
            <a href="{{ route('landing') }}" class="flex items-center gap-2.5" aria-label="M&amp;A Computers · inicio">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-hairline bg-white">
                    <img src="{{ asset('images/logo-mark.jpg') }}" alt="" class="h-full w-full object-cover">
                </span>
                <span class="text-sm font-semibold">M&amp;A Computers</span>
            </a>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-ink-muted">
                Sistema de gestión interno para las dos sucursales del Comercial Chiriguano. Inventario, ventas y servicio técnico desde 2012.
            </p>
            <p class="mt-4 text-xs text-ink-muted">Laravel · MySQL · Tailwind CSS</p>
        </div>

        <div>
            <p class="panel-title px-0 pt-0">Sistema</p>
            <ul class="mt-2 space-y-1.5 text-sm">
                <li><a href="#sistema" class="text-ink-muted transition-colors hover:text-ink">El sistema</a></li>
                <li><a href="#buscador" class="text-ink-muted transition-colors hover:text-ink">Buscador</a></li>
                <li><a href="#modulos" class="text-ink-muted transition-colors hover:text-ink">Módulos</a></li>
                <li><a href="#como-funciona" class="text-ink-muted transition-colors hover:text-ink">Cómo funciona</a></li>
                <li><a href="{{ route('login') }}" class="text-ink-muted transition-colors hover:text-ink">Ingresar al sistema</a></li>
            </ul>
        </div>

        <div>
            <p class="panel-title px-0 pt-0">Sucursales</p>
            <ul class="mt-2 space-y-3 text-sm text-ink-muted">
                <li>
                    <p class="font-medium text-ink">Sucursal principal</p>
                    <p>Pasillo 6, local 61</p>
                </li>
                <li>
                    <p class="font-medium text-ink">Segunda sucursal</p>
                    <p>Pasillo 1, local 121 C</p>
                </li>
                <li>
                    <p class="flex items-center gap-1.5"><x-icon name="map-pin" class="size-3.5" /> Comercial Chiriguano · Santa Cruz de la Sierra</p>
                </li>
            </ul>
        </div>
    </div>

    <div class="border-t border-hairline-soft">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-2 px-4 py-5 text-xs text-ink-muted sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <p>© {{ date('Y') }} M&amp;A Computers. Todos los derechos reservados.</p>
            <p>Acceso exclusivo para personal autorizado.</p>
        </div>
    </div>
</footer>
</body>
</html>
