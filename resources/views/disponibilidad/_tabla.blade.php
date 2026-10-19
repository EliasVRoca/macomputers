<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Código</th>
                @foreach ($sucursales as $sucursal)
                    <th class="text-right">{{ $sucursal->nombre }}</th>
                @endforeach
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($productos as $producto)
            @php($total = (int) $producto->inventarios->sum('cantidad'))
            @php($bajo = $total <= (int) $producto->stock_minimo)
            <tr>
                <td>
                    <span class="font-medium">{{ $producto->nombre }}</span>
                    <span class="block text-xs text-ink-muted">{{ $producto->marca }}</span>
                </td>
                <td class="font-mono text-xs">{{ $producto->codigo }}</td>
                @foreach ($sucursales as $sucursal)
                    @php($cantidad = (int) ($producto->inventarios->firstWhere('sucursal_id', $sucursal->id)?->cantidad ?? 0))
                    <td class="text-right tabular-nums {{ $cantidad === 0 ? 'text-ink-tertiary' : '' }}">{{ $cantidad }}</td>
                @endforeach
                <td class="text-right">
                    @if ($bajo)
                        <span class="badge badge-danger">{{ $total }} · bajo mínimo</span>
                    @else
                        <span class="font-medium tabular-nums">{{ $total }}</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ 3 + $sucursales->count() }}">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="map-pin" class="size-5" /></span>
                    <div>
                        <p class="font-medium">No hay productos para mostrar</p>
                        <p class="text-sm text-ink-muted">Registre productos o ajuste la búsqueda.</p>
                    </div>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $productos->links() }}</div>
