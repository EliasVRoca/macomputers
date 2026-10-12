@props(['codigo', 'titulo', 'mensaje'])

<div class="w-full max-w-md text-center">
    <p class="text-6xl font-medium tracking-tight text-brand">{{ $codigo }}</p>
    <h1 class="mt-4 text-xl font-medium">{{ $titulo }}</h1>
    <p class="mt-2 text-sm text-ink-muted">{{ $mensaje }}</p>
    <div class="mt-8 flex justify-center gap-2">
        <a href="{{ auth()->check() ? route('inicio') : route('login') }}" class="btn btn-primary">
            <x-icon name="home" class="size-4" />
            {{ auth()->check() ? 'Volver al inicio' : 'Iniciar sesión' }}
        </a>
    </div>
</div>
