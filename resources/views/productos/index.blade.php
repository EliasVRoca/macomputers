@extends('layouts.app')

@section('titulo', 'Productos')
@section('encabezado', 'Productos')
@section('subtitulo', 'Catálogo de equipos, accesorios y repuestos.')
@section('acciones')
    <a href="{{ route('productos.export', request()->query()) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-4" /> Exportar CSV</a>
    <a href="{{ route('productos.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo producto</a>
@endsection

@section('contenido')
@error('eliminar') <p class="error-texto mb-4">{{ $message }}</p> @enderror

<form method="GET" action="{{ route('productos.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
    <div class="min-w-56">
        <label for="buscar" class="label">Buscar</label>
        <input id="buscar" name="buscar" type="search" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Nombre, código o marca" class="input" aria-label="Buscar producto">
    </div>
    <div class="min-w-44">
        <label for="categoria_id" class="label">Categoría</label>
        <select id="categoria_id" name="categoria_id" class="input">
            <option value="">Todas</option>
            @foreach ($categorias as $categoria)
                <option value="{{ $categoria->id }}" @selected(($filtros['categoria_id'] ?? null) == $categoria->id)>{{ $categoria->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="min-w-36">
        <label for="marca" class="label">Marca</label>
        <input id="marca" name="marca" type="search" value="{{ $filtros['marca'] ?? '' }}" placeholder="Marca" class="input">
    </div>
    <div class="min-w-40">
        <label for="condicion" class="label">Condición</label>
        <select id="condicion" name="condicion" class="input">
            <option value="">Todas</option>
            @foreach ($condiciones as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(($filtros['condicion'] ?? null) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>
    <div class="min-w-32">
        <label for="activo" class="label">Estado</label>
        <select id="activo" name="activo" class="input">
            <option value="">Todos</option>
            <option value="1" @selected(($filtros['activo'] ?? null) === '1')>Activos</option>
            <option value="0" @selected(($filtros['activo'] ?? null) === '0')>Inactivos</option>
        </select>
    </div>
    <div class="mb-0.5 flex gap-2">
        <a href="{{ route('productos.index') }}" class="btn btn-ghost">Limpiar</a>
        <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Buscar</button>
    </div>
</form>

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Código</th><th>Producto</th><th>Categoría</th><th>Condición</th><th class="text-right">Precio venta</th><th class="text-right">Stock total</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $producto)
            @php($bajo = ($producto->stock_total ?? 0) <= $producto->stock_minimo)
            <tr>
                <td class="font-mono text-xs">{{ $producto->codigo }}</td>
                <td>
                    <a href="{{ route('productos.show', $producto) }}" class="font-medium hover:underline">{{ $producto->nombre }}</a>
                    <span class="block text-xs text-ink-muted">{{ $producto->marca }}{{ $producto->modelo ? ' · '.$producto->modelo : '' }}</span>
                </td>
                <td>{{ $producto->categoria?->nombre }}</td>
                <td>
                    @php($condicion = $producto->condicion ?? \App\Enums\CondicionProducto::Nuevo)
                    <span class="badge {{ $condicion->colorBadge() }}">{{ $condicion->etiqueta() }}</span>
                    @unless ($producto->activo)
                        <span class="badge badge-neutral">Inactivo</span>
                    @endunless
                </td>
                <td class="text-right tabular-nums">Bs {{ number_format($producto->precio_venta, 2, ',', '.') }}</td>
                <td class="text-right">
                    @if ($bajo)
                        <span class="badge badge-danger">{{ (int) ($producto->stock_total ?? 0) }} · bajo mínimo</span>
                    @else
                        <span class="tabular-nums">{{ (int) ($producto->stock_total ?? 0) }}</span>
                    @endif
                </td>
                <td>
                    <div class="flex justify-end gap-1">
                        <a href="{{ route('productos.show', $producto) }}" class="btn btn-ghost btn-icon" title="Ver detalle" aria-label="Ver detalle"><x-icon name="eye" class="size-4" /></a>
                        <a href="{{ route('productos.edit', $producto) }}" class="btn btn-ghost btn-icon" title="Editar" aria-label="Editar"><x-icon name="pencil" class="size-4" /></a>
                        <form method="POST" action="{{ route('productos.destroy', $producto) }}" data-confirm="¿Eliminar el producto {{ $producto->nombre }}?">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Eliminar" aria-label="Eliminar"><x-icon name="trash" class="size-4" /></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="package" class="size-5" /></span>
                    <div>
                        <p class="font-medium">No se encontraron productos</p>
                        <p class="text-sm text-ink-muted">Registre el catálogo o ajuste los filtros.</p>
                    </div>
                    <a href="{{ route('productos.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nuevo producto</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
