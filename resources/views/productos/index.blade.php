@extends('layouts.app')

@section('titulo', 'Productos')
@section('encabezado', 'Productos')
@section('subtitulo', 'Catálogo de equipos, accesorios y repuestos.')
@section('acciones')
    <a href="{{ route('productos.create') }}" class="btn btn-primary">Nuevo producto</a>
@endsection

@section('contenido')
<form method="GET" action="{{ route('productos.index') }}" class="mb-4 flex gap-2">
    <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar por nombre, código o marca" class="input max-w-sm" aria-label="Buscar producto">
    <button type="submit" class="btn btn-secondary">Buscar</button>
</form>

<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead>
            <tr><th>Código</th><th>Producto</th><th>Categoría</th><th class="text-right">Precio venta</th><th class="text-right">Stock total</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $producto)
            @php($bajo = ($producto->stock_total ?? 0) <= $producto->stock_minimo)
            <tr>
                <td class="font-mono text-xs">{{ $producto->codigo }}</td>
                <td>
                    <span class="font-medium">{{ $producto->nombre }}</span>
                    <span class="block text-xs text-ink-muted">{{ $producto->marca }}{{ $producto->modelo ? ' · '.$producto->modelo : '' }}</span>
                </td>
                <td>{{ $producto->categoria?->nombre }}</td>
                <td class="text-right">Bs {{ number_format($producto->precio_venta, 2, ',', '.') }}</td>
                <td class="text-right {{ $bajo ? 'font-medium text-semantic-error' : '' }}">
                    {{ (int) ($producto->stock_total ?? 0) }}
                    @if ($bajo)<span class="block text-xs">Bajo stock mínimo</span>@endif
                </td>
                <td class="flex justify-end gap-1">
                    <a href="{{ route('productos.edit', $producto) }}" class="btn btn-ghost !py-1.5 text-sm">Editar</a>
                    <form method="POST" action="{{ route('productos.destroy', $producto) }}" onsubmit="return confirm('¿Eliminar el producto {{ $producto->nombre }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost !py-1.5 text-sm text-semantic-error">Eliminar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-ink-muted">No se encontraron productos.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@error('eliminar') <p class="error-texto mt-3">{{ $message }}</p> @enderror
<div class="mt-4">{{ $items->links() }}</div>
@endsection
