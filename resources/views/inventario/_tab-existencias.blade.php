<section>
    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-sm font-medium text-ink">Existencias</h2>
            <p class="text-xs text-ink-muted">Cuántas unidades hay de cada producto, en cada sucursal. El total es la suma de todas.</p>
        </div>
        <a href="{{ route('inventario.existencias.export', request()->query()) }}" class="btn btn-secondary btn-sm">
            <x-icon name="download" class="size-4" /> Exportar CSV
        </a>
    </div>

    <form method="GET" action="{{ route('inventario.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <input type="hidden" name="tab" value="existencias">
        <div class="min-w-56 flex-1">
            <label for="buscar" class="label">Buscar producto</label>
            <input id="buscar" name="buscar" type="search" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Nombre, código o marca" class="input">
        </div>
        <label class="mb-2.5 flex items-center gap-2 text-sm text-ink-muted">
            <input type="checkbox" name="bajo_minimo" value="1" @checked(($filtros['bajo_minimo'] ?? null) == 1)>
            Solo bajo stock mínimo
        </label>
        <div class="mb-0.5 flex gap-2">
            <a href="{{ route('inventario.index', ['tab' => 'existencias']) }}" class="btn btn-ghost">Limpiar</a>
            <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Buscar</button>
        </div>
    </form>

    <div class="card card-flush overflow-x-auto">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    @foreach ($sucursales as $sucursal)
                        <th class="text-right">{{ $sucursal->nombre }}</th>
                    @endforeach
                    <th class="text-right">Total</th>
                    @if ($puedeTraspasar)
                        <th class="text-right">Acción</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            @forelse ($existencias as $producto)
                @php
                    $porSucursal = $producto->inventarios->pluck('cantidad', 'sucursal_id');
                    $total = (int) $producto->inventarios->sum('cantidad');
                    $bajo = $total <= (int) $producto->stock_minimo;
                    $stock = $sucursales->mapWithKeys(fn ($s) => [$s->id => (int) ($porSucursal[$s->id] ?? 0)]);
                    $destinoId = auth()->user()->sucursal_id;
                    $hayOrigen = $puedeElegirSucursal
                        ? $stock->filter(fn ($c) => $c > 0)->isNotEmpty()
                        : $stock->filter(fn ($c, $sid) => $c > 0 && $sid !== $destinoId)->isNotEmpty();
                @endphp
                <tr>
                    <td>
                        <span class="font-medium">{{ $producto->nombre }}</span>
                        <span class="block text-xs text-ink-muted">{{ $producto->marca }} · {{ $producto->codigo }}</span>
                    </td>
                    @foreach ($sucursales as $sucursal)
                        @php($cantidad = (int) ($porSucursal[$sucursal->id] ?? 0))
                        <td class="text-right tabular-nums {{ $cantidad === 0 ? 'text-ink-tertiary' : '' }}">{{ $cantidad }}</td>
                    @endforeach
                    <td class="text-right">
                        @if ($bajo)
                            <span class="badge badge-danger">{{ $total }} · bajo mínimo</span>
                        @else
                            <span class="font-medium tabular-nums">{{ $total }}</span>
                        @endif
                    </td>
                    @if ($puedeTraspasar)
                        <td class="text-right">
                            @if ($hayOrigen)
                                <button type="button" class="btn btn-secondary btn-sm"
                                    data-traer='{!! json_encode(['producto' => $producto->id, 'nombre' => $producto->nombre, 'stock' => $stock->all()], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!}'>
                                    <x-icon name="arrow-left-right" class="size-4" /> Traer
                                </button>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ 2 + $sucursales->count() + ($puedeTraspasar ? 1 : 0) }}">
                    <div class="empty">
                        <span class="kpi-icon"><x-icon name="warehouse" class="size-5" /></span>
                        <div>
                            <p class="font-medium">Sin productos para mostrar</p>
                            <p class="text-sm text-ink-muted">Ajusta la búsqueda o registra una entrada de mercadería.</p>
                        </div>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $existencias->links() }}</div>
</section>
