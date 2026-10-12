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
<body class="min-h-screen bg-canvas font-sans text-ink antialiased">
@php
    $usuario = auth()->user();
    $menu = \App\Support\Menu::para($usuario);
@endphp

<div class="flex min-h-screen">
    <aside class="sticky top-0 hidden h-screen w-64 shrink-0 flex-col border-r border-hairline bg-surface-1 md:flex">
        <div class="flex items-center gap-3 px-5 py-5">
            <img src="{{ asset('images/logo-mark.jpg') }}" alt="" class="h-10 w-10 rounded-md object-cover">
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold leading-tight">M&amp;A Computers</p>
                <p class="truncate text-xs text-ink-muted">Gestión de inventario</p>
            </div>
        </div>

        <nav class="flex-1 space-y-5 overflow-y-auto px-3 pb-6" aria-label="Navegación principal">
            @forelse ($menu as $grupo => $items)
                <div>
                    <p class="mb-1 px-3 text-xs font-medium uppercase tracking-wide text-ink-subtle">{{ $grupo }}</p>
                    <ul class="space-y-0.5">
                        @foreach ($items as $item)
                            <li>
                                <a href="{{ route($item['route']) }}"
                                   @if ($item['activo']) aria-current="page" @endif
                                   class="flex items-center rounded-md px-3 py-2 text-sm transition-colors {{ $item['activo'] ? 'bg-surface-2 font-medium text-ink' : 'text-ink-muted hover:bg-surface-2 hover:text-ink' }}">
                                    {{ $item['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="px-3 text-sm text-ink-muted">Su rol no tiene módulos asignados.</p>
            @endforelse
        </nav>

        <div class="space-y-3 border-t border-hairline p-4">
            <div class="min-w-0">
                <p class="truncate text-sm font-medium">{{ $usuario->nombre() }}</p>
                <p class="truncate text-xs text-ink-muted">
                    {{ $usuario->role?->nombre }} · {{ $usuario->sucursal?->nombre ?? 'Todas las sucursales' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" data-toggle-tema class="btn btn-secondary flex-1 !py-2 text-sm">Tema</button>
                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <button type="submit" class="btn btn-secondary w-full !py-2 text-sm">Salir</button>
                </form>
            </div>
        </div>
    </aside>

    <div class="min-w-0 flex-1">
        {{-- Navegación móvil --}}
        <header class="flex items-center justify-between gap-3 border-b border-hairline bg-surface-1 px-4 py-3 md:hidden">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/logo-mark.jpg') }}" alt="" class="h-8 w-8 rounded-md object-cover">
                <span class="text-sm font-semibold">M&amp;A Computers</span>
            </div>
            <details class="relative">
                <summary class="btn btn-secondary cursor-pointer !py-1.5 text-sm">Menú</summary>
                <div class="absolute right-0 z-20 mt-2 w-64 space-y-4 rounded-xl border border-hairline bg-surface-1 p-3 shadow-lg">
                    @foreach ($menu as $grupo => $items)
                        <div>
                            <p class="px-2 text-xs font-medium uppercase tracking-wide text-ink-subtle">{{ $grupo }}</p>
                            @foreach ($items as $item)
                                <a href="{{ route($item['route']) }}" class="block rounded-md px-2 py-1.5 text-sm {{ $item['activo'] ? 'bg-surface-2 font-medium' : 'text-ink-muted' }}">{{ $item['label'] }}</a>
                            @endforeach
                        </div>
                    @endforeach
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary w-full text-sm">Salir</button>
                    </form>
                </div>
            </details>
        </header>

        <main class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-10">
            @if (session('status'))
                <div role="status" class="mb-6 rounded-lg border border-hairline bg-surface-1 px-4 py-3 text-sm">
                    <span class="mr-2 inline-block h-2 w-2 rounded-full bg-semantic-success"></span>{{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="mb-6 rounded-lg border border-semantic-error px-4 py-3 text-sm text-semantic-error">
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
                    <h1 class="text-3xl font-medium tracking-tight">@yield('encabezado', 'Inicio')</h1>
                    @hasSection('subtitulo')
                        <p class="mt-1 text-sm text-ink-muted">@yield('subtitulo')</p>
                    @endif
                </div>
                @hasSection('acciones')
                    <div class="flex flex-wrap gap-2">@yield('acciones')</div>
                @endif
            </header>

            @yield('contenido')
        </main>
    </div>
</div>
</body>
</html>
