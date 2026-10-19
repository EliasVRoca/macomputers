@extends('layouts.app')

@section('titulo', $producto->nombre)
@section('encabezado', $producto->nombre)
@section('subtitulo', $producto->codigo.' · '.$producto->marca.($producto->modelo ? ' · '.$producto->modelo : ''))
@section('acciones')
    <a href="{{ route('productos.edit', $producto) }}" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Editar</a>
    <a href="{{ route('productos.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
@php
    $condicion = $producto->condicion ?? \App\Enums\CondicionProducto::Nuevo;
    $stockTotal = $existencias->sum('cantidad');
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="card card-pad">
            <h2 class="mb-4 text-sm font-medium text-ink-muted">Datos del producto</h2>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="text-xs text-ink-muted">Código</dt><dd class="font-mono">{{ $producto->codigo }}</dd></div>
                <div><dt class="text-xs text-ink-muted">Categoría</dt><dd>{{ $producto->categoria?->nombre ?? '—' }}</dd></div>
                <div><dt class="text-xs text-ink-muted">Marca</dt><dd>{{ $producto->marca }}</dd></div>
                <div><dt class="text-xs text-ink-muted">Modelo</dt><dd>{{ $producto->modelo ?? '—' }}</dd></div>
                <div><dt class="text-xs text-ink-muted">Condición</dt><dd><span class="badge {{ $condicion->colorBadge() }}">{{ $condicion->etiqueta() }}</span></dd></div>
                <div>
                    <dt class="text-xs text-ink-muted">Estado</dt>
                    <dd>@if ($producto->activo)<span class="badge badge-ok">Activo</span>@else<span class="badge badge-neutral">Inactivo</span>@endif</dd>
                </div>
                <div><dt class="text-xs text-ink-muted">Precio de compra</dt><dd class="tabular-nums">Bs {{ number_format($producto->precio_compra, 2, ',', '.') }}</dd></div>
                <div><dt class="text-xs text-ink-muted">Precio de venta</dt><dd class="tabular-nums">Bs {{ number_format($producto->precio_venta, 2, ',', '.') }}</dd></div>
            </dl>
        </div>

        <section>
            <h2 class="mb-3 text-sm font-medium text-ink">Existencias por sucursal</h2>
            <div class="card card-flush overflow-x-auto">
                <table class="tabla">
                    <thead><tr><th>Sucursal</th><th class="text-right">Cantidad</th></tr></thead>
                    <tbody>
                    @forelse ($existencias as $existencia)
                        <tr>
                            <td>{{ $existencia->sucursal?->nombre ?? '—' }}</td>
                            <td class="text-right tabular-nums">{{ $existencia->cantidad }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2">
                            <div class="empty">
                                <span class="kpi-icon"><x-icon name="warehouse" class="size-5" /></span>
                                <div>
                                    <p class="font-medium">Sin existencias</p>
                                    <p class="text-sm text-ink-muted">El producto aún no tiene stock en ninguna sucursal.</p>
                                </div>
                            </div>
                        </td></tr>
                    @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="px-4 py-3 text-right font-medium">Total</td>
                            <td class="px-4 py-3 text-right font-medium tabular-nums">{{ $stockTotal }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <section>
            <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
                <h2 class="text-sm font-medium text-ink">Últimos movimientos</h2>
                <a href="{{ route('inventario.index', ['tab' => 'historial', 'producto_id' => $producto->id]) }}" class="btn btn-ghost btn-sm">Ver kardex completo</a>
            </div>
            <div class="card card-flush overflow-x-auto">
                <table class="tabla">
                    <thead><tr><th>Fecha</th><th>Sucursal</th><th>Tipo</th><th class="text-right">Cant.</th><th class="text-right">Saldo</th></tr></thead>
                    <tbody>
                    @forelse ($movimientos as $mov)
                        <tr>
                            <td class="whitespace-nowrap text-xs text-ink-muted">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-ink-muted">{{ $mov->sucursal?->nombre ?? '—' }}</td>
                            <td><span class="badge badge-neutral">{{ \App\Enums\TipoMovimiento::desde($mov->tipo)->etiqueta() }}</span></td>
                            <td class="text-right tabular-nums {{ $mov->cantidad < 0 ? 'text-danger' : 'text-ok' }}">{{ $mov->cantidad > 0 ? '+' : '' }}{{ $mov->cantidad }}</td>
                            <td class="text-right tabular-nums">{{ $mov->saldo }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">
                            <div class="empty">
                                <span class="kpi-icon"><x-icon name="list" class="size-5" /></span>
                                <div>
                                    <p class="font-medium">Sin movimientos</p>
                                    <p class="text-sm text-ink-muted">Aún no hay cambios de stock registrados.</p>
                                </div>
                            </div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        <div class="card card-pad">
            <h2 class="mb-4 text-sm font-medium text-ink-muted">Resumen de stock</h2>
            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between"><span class="text-ink-muted">Stock total</span><span class="font-medium tabular-nums">{{ $stockTotal }}</span></div>
                <div class="flex items-center justify-between"><span class="text-ink-muted">Stock mínimo</span><span class="font-medium tabular-nums">{{ $producto->stock_minimo }}</span></div>
                @if ($stockTotal <= $producto->stock_minimo)
                    <p class="flex items-center gap-2 rounded-lg border border-danger px-3 py-2 text-xs">
                        <span class="badge badge-danger">Bajo mínimo</span> Conviene reponer mercadería.
                    </p>
                @endif
            </div>
        </div>
    </aside>
</div>
@endsection
