<section>
    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-sm font-medium text-ink">Historial de movimientos</h2>
            <p class="text-xs text-ink-muted">Cada entrada, salida, venta, compra o traspaso que cambió el stock, y quién lo hizo.</p>
        </div>
        <a href="{{ route('inventario.kardex.export', request()->query()) }}" class="btn btn-secondary btn-sm">
            <x-icon name="download" class="size-4" /> Exportar CSV
        </a>
    </div>

    <form method="GET" action="{{ route('inventario.index') }}" class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
        <input type="hidden" name="tab" value="historial">
        <div>
            <label for="h-producto" class="label">Producto</label>
            <select id="h-producto" name="producto_id" class="input">
                <option value="">Todos</option>
                @foreach ($productos as $producto)
                    <option value="{{ $producto->id }}" @selected(($filtros['producto_id'] ?? null) == $producto->id)>{{ $producto->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="h-tipo" class="label">Tipo</label>
            <select id="h-tipo" name="tipo" class="input">
                <option value="">Todos</option>
                @foreach ($tipos as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(($filtros['tipo'] ?? null) === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="h-desde" class="label">Desde</label>
            <input id="h-desde" name="desde" type="date" class="input" value="{{ $filtros['desde'] ?? '' }}">
        </div>
        <div>
            <label for="h-hasta" class="label">Hasta</label>
            <input id="h-hasta" name="hasta" type="date" class="input" value="{{ $filtros['hasta'] ?? '' }}">
        </div>
        @if ($puedeElegirSucursal)
            <div>
                <label for="h-sucursal" class="label">Sucursal</label>
                <select id="h-sucursal" name="sucursal_id" class="input">
                    <option value="">Todas</option>
                    @foreach ($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" @selected(($filtros['sucursal_id'] ?? null) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="flex gap-2 sm:col-span-2 lg:col-span-5">
            <a href="{{ route('inventario.index', ['tab' => 'historial']) }}" class="btn btn-ghost">Limpiar</a>
            <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Filtrar</button>
        </div>
    </form>

    <div class="card card-flush overflow-x-auto">
        <table class="tabla">
            <thead><tr><th>Fecha</th><th>Producto</th><th>Sucursal</th><th>Tipo</th><th class="text-right">Cant.</th><th class="text-right">Saldo</th><th>Usuario</th></tr></thead>
            <tbody>
            @forelse ($kardex as $mov)
                <tr>
                    <td class="whitespace-nowrap text-xs text-ink-muted">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $mov->producto?->nombre }}</td>
                    <td class="text-ink-muted">{{ $mov->sucursal?->nombre }}</td>
                    <td><span class="badge badge-neutral">{{ \App\Enums\TipoMovimiento::desde($mov->tipo)->etiqueta() }}</span></td>
                    <td class="text-right tabular-nums {{ $mov->cantidad < 0 ? 'text-danger' : 'text-ok' }}">{{ $mov->cantidad > 0 ? '+' : '' }}{{ $mov->cantidad }}</td>
                    <td class="text-right tabular-nums">{{ $mov->saldo }}</td>
                    <td class="text-ink-muted">{{ $mov->usuario?->username ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">
                    <div class="empty">
                        <span class="kpi-icon"><x-icon name="list" class="size-5" /></span>
                        <div>
                            <p class="font-medium">Sin movimientos</p>
                            <p class="text-sm text-ink-muted">Aquí verás cada cambio de stock. Registra uno para empezar.</p>
                        </div>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $kardex->links() }}</div>
</section>
