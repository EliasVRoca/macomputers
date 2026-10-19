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

        <section id="imagenes" class="card card-pad">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-medium text-ink-muted">Imágenes para el buscador</h2>
                    <p class="text-xs text-ink-subtle">La imagen principal se muestra en los resultados de búsqueda.</p>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" data-imagenes-abrir>
                    <x-icon name="search" class="size-4" /> Buscar en internet
                </button>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                @forelse ($imagenes as $imagen)
                    <div class="overflow-hidden rounded-lg border border-hairline bg-surface-2">
                        <div class="relative aspect-[4/3]">
                            @if ($imagen->src())
                                <img src="{{ $imagen->src() }}" alt="{{ $producto->nombre }}" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full items-center justify-center text-ink-tertiary"><x-icon name="package" class="size-8" /></div>
                            @endif
                            @if ($imagen->es_principal)
                                <span class="badge badge-brand absolute top-2 left-2">Principal</span>
                            @endif
                        </div>
                        @if ($imagen->origen_url || $imagen->autor || $imagen->licencia)
                            <div class="space-y-0.5 px-2 pt-2 text-[11px] leading-tight text-ink-subtle">
                                @if ($imagen->origenDominio())
                                    <a href="{{ $imagen->origen_url }}" target="_blank" rel="noopener noreferrer" class="block truncate hover:text-brand" title="Ver la fuente original">Fuente: {{ $imagen->origenDominio() }}</a>
                                @endif
                                @if ($imagen->autor || $imagen->licencia)
                                    <p class="truncate">
                                        @if ($imagen->autor) Foto: {{ $imagen->autor }} @endif
                                        @if ($imagen->autor && $imagen->licencia) · @endif
                                        @if ($imagen->licencia_url)
                                            <a href="{{ $imagen->licencia_url }}" target="_blank" rel="noopener noreferrer" class="hover:text-brand">{{ $imagen->licencia }}</a>
                                        @else
                                            {{ $imagen->licencia }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-1 p-2">
                            @unless ($imagen->es_principal)
                                <form method="POST" action="{{ route('productos.imagenes.principal', [$producto, $imagen]) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-ghost btn-sm">Principal</button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('productos.imagenes.destroy', [$producto, $imagen]) }}" onsubmit="return confirm('¿Quitar esta imagen?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Quitar" aria-label="Quitar imagen"><x-icon name="trash" class="size-4" /></button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-ink-muted sm:col-span-3">Sin imágenes. Agregá una para que el producto se vea en el buscador.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('productos.imagenes.store', $producto) }}" enctype="multipart/form-data" class="mt-5 grid gap-3 border-t border-hairline pt-5 sm:grid-cols-2">
                @csrf
                <div class="sm:col-span-2">
                    <label for="archivo" class="label">Subir imagen</label>
                    <input id="archivo" name="archivo" type="file" accept="image/jpeg,image/png,image/webp" class="input file:mr-3 file:rounded-md file:border-0 file:bg-surface-2 file:px-3 file:py-1.5 file:text-sm file:text-ink">
                    @error('archivo') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="url" class="label">…o pegar una URL</label>
                    <input id="url" name="url" type="url" class="input" placeholder="https://…" maxlength="500">
                    @error('url') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end gap-3">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="es_principal" value="1" class="h-4 w-4 rounded border-hairline accent-brand"> Principal</label>
                    <button type="submit" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Agregar</button>
                </div>
            </form>
        </section>

        <section class="card card-pad">
            <div class="mb-4">
                <h2 class="text-sm font-medium text-ink-muted">Ficha técnica</h2>
                <p class="text-xs text-ink-subtle">Atributos clave/valor que el buscador usa para encontrar el producto.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="tabla">
                    <thead><tr><th>Atributo</th><th>Valor</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($atributos as $atributo)
                        <tr>
                            <td class="font-medium">{{ $atributo->atributo }}</td>
                            <td>{{ $atributo->valor }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('productos.atributos.destroy', [$producto, $atributo]) }}" onsubmit="return confirm('¿Quitar este atributo?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Quitar" aria-label="Quitar atributo"><x-icon name="trash" class="size-4" /></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><p class="py-4 text-sm text-ink-muted">Sin atributos. Ej.: memoria = 8GB, tamaño = 60%.</p></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <form method="POST" action="{{ route('productos.atributos.store', $producto) }}" class="mt-4 grid gap-3 border-t border-hairline pt-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                @csrf
                <div>
                    <label for="atributo" class="label">Atributo</label>
                    <input id="atributo" name="atributo" class="input" placeholder="ej. memoria" maxlength="50" required>
                    @error('atributo') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="valor" class="label">Valor</label>
                    <input id="valor" name="valor" class="input" placeholder="ej. 8GB" maxlength="100" required>
                    @error('valor') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Agregar</button>
            </form>
        </section>

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

@include('productos._modal-imagenes')

@vite('resources/js/producto-imagenes.js')
@endsection
