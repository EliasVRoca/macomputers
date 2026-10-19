<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Inicio') · M&amp;A Computers</title>
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
<body class="min-h-screen font-sans">
@php
    $usuario = auth()->user();
    $grupos = \App\Support\Menu::para($usuario);
    $grupoActivo = collect($grupos)->firstWhere('activo', true) ?? ($grupos[0] ?? null);
    $moduloActivo = $grupoActivo ? collect($grupoActivo['modulos'])->firstWhere('activo', true) : null;
@endphp

<div class="flex min-h-screen">
    {{-- Riel de iconos --}}
    <nav class="rail sticky top-0 hidden h-screen md:flex" data-rail aria-label="Grupos de navegación">
        <a href="{{ route('inicio') }}" class="mb-2 flex h-11 w-11 items-center justify-center" aria-label="Inicio">
            <img src="{{ asset('images/logo-mark.jpg') }}" alt="M&amp;A Computers" class="h-9 w-9 rounded-lg object-cover">
        </a>

        <a href="{{ route('inicio') }}"
           class="rail-link {{ request()->routeIs('inicio') ? 'active' : '' }}"
           title="Inicio" aria-label="Inicio">
            <x-icon name="home" />
        </a>

        @foreach ($grupos as $grupo)
            <a href="{{ route($grupo['modulos'][0]['route']) }}"
               data-grupo="{{ $grupo['nombre'] }}"
               @if ($grupo['activo']) data-activo="1" @endif
               class="rail-link {{ $grupo['activo'] ? 'active' : '' }}"
               title="{{ $grupo['nombre'] }}" aria-label="{{ $grupo['nombre'] }}">
                <x-icon :name="$grupo['icon']" />
            </a>
        @endforeach

        <div class="mt-auto flex flex-col items-center gap-1">
            <button type="button" data-toggle-tema class="rail-link" title="Cambiar tema" aria-label="Cambiar tema">
                <x-icon name="sun" class="size-5 dark:hidden" />
                <x-icon name="moon" class="hidden size-5 dark:block" />
            </button>

            <details class="relative" data-cuenta>
                <summary class="rail-link cursor-pointer list-none" title="Mi cuenta" aria-label="Mi cuenta">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand text-sm font-medium text-on-primary">
                        {{ mb_strtoupper(mb_substr($usuario->nombre(), 0, 1)) }}
                    </span>
                </summary>
                <div class="absolute bottom-0 left-14 z-30 w-64 rounded-xl border border-hairline bg-surface-1 p-4">
                    <p class="truncate text-sm font-medium">{{ $usuario->nombre() }}</p>
                    <p class="mb-3 truncate text-xs text-ink-muted">{{ $usuario->role?->nombre }} · {{ $usuario->sucursal?->nombre ?? 'Todas las sucursales' }}</p>
                    <div class="space-y-0.5">
                        <x-cuenta-links />
                    </div>
                </div>
            </details>
        </div>
    </nav>

    {{-- Panel contextual --}}
    <aside class="panel sticky top-0 hidden h-screen overflow-y-auto md:flex" data-panel aria-label="Módulos">
        @foreach ($grupos as $grupo)
            <div data-panel-grupo="{{ $grupo['nombre'] }}" @if (! $grupo['activo']) hidden @endif>
                <p class="panel-title">{{ $grupo['nombre'] }}</p>
                <ul class="space-y-0.5 px-2 pb-4">
                    @foreach ($grupo['modulos'] as $modulo)
                        <li>
                            <a href="{{ route($modulo['route']) }}"
                               @if ($modulo['activo']) aria-current="page" @endif
                               class="nav-link {{ $modulo['activo'] ? 'active' : '' }}">
                                <x-icon :name="$modulo['icon']" class="size-4 shrink-0" />
                                {{ $modulo['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </aside>

    {{-- Contenido --}}
    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Barra superior móvil --}}
        <header class="flex items-center justify-between gap-3 border-b border-hairline bg-surface-1 px-4 py-3 md:hidden">
            <a href="{{ route('inicio') }}" class="flex items-center gap-2">
                <img src="{{ asset('images/logo-mark.jpg') }}" alt="" class="h-8 w-8 rounded-lg object-cover">
                <span class="text-sm font-semibold">M&amp;A Computers</span>
            </a>
            <div class="flex items-center gap-1">
                <button type="button" data-toggle-tema class="btn btn-ghost btn-icon" aria-label="Cambiar tema">
                    <x-icon name="sun" class="size-5 dark:hidden" />
                    <x-icon name="moon" class="hidden size-5 dark:block" />
                </button>
                <button type="button" data-menu-abrir class="btn btn-ghost btn-icon" aria-label="Abrir menú">
                    <x-icon name="menu" class="size-5" />
                </button>
            </div>
        </header>

        {{-- Barra superior de escritorio --}}
        <header class="topbar sticky top-0 z-20 hidden md:flex">
            <nav class="flex min-w-0 items-center gap-2 text-sm" aria-label="Migas de pan">
                <span class="text-ink-muted">{{ $grupoActivo['nombre'] ?? 'Inicio' }}</span>
                @if ($moduloActivo)
                    <x-icon name="chevron-right" class="size-4 shrink-0 text-ink-tertiary" />
                    <span class="truncate font-medium">{{ $moduloActivo['label'] }}</span>
                @endif
            </nav>
            <div class="flex items-center gap-2">
                <button type="button" data-paleta-abrir class="search-btn">
                    <x-icon name="search" class="size-4" />
                    <span class="hidden lg:inline">Buscar o ir a…</span>
                    <span class="kbd ml-2 hidden lg:inline">Ctrl K</span>
                </button>

                <details class="relative" data-cuenta>
                    <summary class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-full bg-brand text-sm font-medium text-on-primary" title="Mi cuenta" aria-label="Mi cuenta">
                        {{ mb_strtoupper(mb_substr($usuario->nombre(), 0, 1)) }}
                    </summary>
                    <div class="absolute right-0 z-30 mt-2 w-64 rounded-xl border border-hairline bg-surface-1 p-4">
                        <p class="truncate text-sm font-medium">{{ $usuario->nombre() }}</p>
                        <p class="mb-3 truncate text-xs text-ink-muted">{{ $usuario->role?->nombre }} · {{ $usuario->sucursal?->nombre ?? 'Todas las sucursales' }}</p>
                        <div class="space-y-0.5">
                            <x-cuenta-links />
                        </div>
                    </div>
                </details>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 p-4 sm:p-6 lg:p-8">
            @if (session('status'))
                <div role="status" class="mb-6 flex items-center gap-2 rounded-lg border border-hairline bg-surface-1 px-4 py-3 text-sm">
                    <span class="h-2 w-2 shrink-0 rounded-full bg-ok"></span>{{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mb-6 rounded-lg border border-danger px-4 py-3 text-sm text-danger">
                    <p class="font-medium">Revise los datos ingresados:</p>
                    <ul class="mt-1 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <header class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-medium tracking-tight lg:text-3xl">@yield('encabezado', 'Inicio')</h1>
                    @hasSection('subtitulo')
                        <p class="mt-1 text-sm text-ink-muted">@yield('subtitulo')</p>
                    @endif
                </div>
                @hasSection('acciones')
                    <div class="flex flex-wrap items-center gap-2">@yield('acciones')</div>
                @endif
            </header>

            @yield('contenido')
        </main>
    </div>
</div>

{{-- Navegación móvil --}}
<div data-menu-movil hidden class="fixed inset-0 z-40 md:hidden">
    <div class="absolute inset-0 bg-black/50" data-menu-cerrar></div>
    <div class="absolute inset-y-0 right-0 flex w-80 max-w-[85vw] flex-col overflow-y-auto border-l border-hairline bg-surface-1">
        <div class="flex items-center justify-between border-b border-hairline px-4 py-3">
            <span class="text-sm font-semibold">Módulos</span>
            <button type="button" data-menu-cerrar class="btn btn-ghost btn-icon" aria-label="Cerrar menú">
                <x-icon name="x" class="size-5" />
            </button>
        </div>
        <nav class="p-3">
            @foreach ($grupos as $grupo)
                <p class="px-2 pt-4 pb-1 text-xs font-medium tracking-wide text-ink-tertiary uppercase">{{ $grupo['nombre'] }}</p>
                @foreach ($grupo['modulos'] as $modulo)
                    <a href="{{ route($modulo['route']) }}" class="nav-link {{ $modulo['activo'] ? 'active' : '' }}">
                        <x-icon :name="$modulo['icon']" class="size-4 shrink-0" /> {{ $modulo['label'] }}
                    </a>
                @endforeach
            @endforeach
        </nav>
        <div class="mt-auto border-t border-hairline p-4">
            <p class="truncate text-sm font-medium">{{ $usuario->nombre() }}</p>
            <p class="mb-3 truncate text-xs text-ink-muted">{{ $usuario->role?->nombre }}</p>
            <div class="space-y-0.5">
                <x-cuenta-links />
            </div>
        </div>
    </div>
</div>

{{-- Paleta de comandos --}}
<dialog id="paleta" class="paleta">
    <input type="search" data-paleta-input class="paleta-input" placeholder="Buscar módulo o acción…" aria-label="Buscar módulo o acción" autocomplete="off">
    <div data-paleta-lista class="paleta-lista"></div>
</dialog>
<script type="application/json" id="paleta-datos">@json(\App\Support\Menu::paleta($usuario))</script>
</body>
</html>
