@php($params = collect(request()->query())->except(['page', 'existencias_page', 'kardex_page'])->all())

<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="kpi">
        <p class="text-xs text-ink-muted">Productos activos</p>
        <p class="mt-1 text-2xl font-medium tabular-nums">{{ $metricas['productos'] }}</p>
    </div>

    <div class="kpi">
        <p class="text-xs text-ink-muted">Unidades en stock</p>
        <p class="mt-1 text-2xl font-medium tabular-nums">{{ $metricas['unidades'] }}</p>
    </div>

    <a href="{{ route('inventario.index', array_merge($params, ['tab' => 'existencias', 'bajo_minimo' => 1])) }}"
       class="kpi transition-colors hover:border-ink-tertiary {{ $metricas['bajo_minimo'] > 0 ? 'border-danger' : '' }}">
        <p class="text-xs text-ink-muted">Bajo stock mínimo</p>
        <p class="mt-1 text-2xl font-medium tabular-nums {{ $metricas['bajo_minimo'] > 0 ? 'text-danger' : '' }}">{{ $metricas['bajo_minimo'] }}</p>
        <p class="mt-1 text-xs text-ink-subtle">Ver productos bajo mínimo</p>
    </a>

    <a href="{{ route('inventario.index', array_merge($params, ['tab' => 'historial'])) }}"
       class="kpi transition-colors hover:border-ink-tertiary">
        <p class="text-xs text-ink-muted">Movimientos de hoy</p>
        <p class="mt-1 text-2xl font-medium tabular-nums">{{ $metricas['movimientos_hoy'] }}</p>
        <p class="mt-1 text-xs text-ink-subtle">Ver historial</p>
    </a>
</div>
