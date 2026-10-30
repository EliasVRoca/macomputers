@props([
    'config' => [],
    'titulo' => null,
    'subtitulo' => null,
    'enlace' => null,
    'enlaceEtiqueta' => null,
    'altura' => 'h-56',
])

<div class="card card-pad" data-grafico-tarjeta>
    <div class="mb-4 flex items-start justify-between gap-3">
        <div>
            <h3 class="text-sm font-semibold">{{ $titulo ?? ($config['titulo'] ?? '') }}</h3>
            @if ($subtitulo)
                <p class="mt-0.5 text-xs text-ink-muted">{{ $subtitulo }}</p>
            @endif
        </div>
        @if ($enlace)
            <a href="{{ $enlace }}" class="shrink-0 text-xs font-medium text-brand hover:underline">
                {{ $enlaceEtiqueta ?? 'Ver en reportes' }}
            </a>
        @endif
    </div>
    <div class="relative {{ $altura }}">
        <canvas data-grafico="{{ json_encode($config) }}"></canvas>
        <div data-grafico-vacio hidden class="absolute inset-0 flex items-center justify-center">
            <p class="px-4 text-center text-sm text-ink-muted">Aún no hay datos suficientes en el rango.</p>
        </div>
    </div>
</div>
