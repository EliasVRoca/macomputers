@extends('layouts.app')

@section('titulo', 'Traspasos')
@section('encabezado', 'Traspasos entre sucursales')
@section('subtitulo', 'Traslados de productos: descuenta del origen y suma al destino.')
@section('acciones')
    <a href="{{ route('traspasos.export', request()->query()) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-4" /> Exportar CSV</a>
    <a href="{{ route('traspasos.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo traspaso</a>
@endsection

@section('contenido')
<form method="GET" action="{{ route('traspasos.index') }}" class="card card-pad mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
    <div>
        <label for="f-producto" class="label">Producto</label>
        <select id="f-producto" name="producto_id" class="input">
            <option value="">Todos</option>
            @foreach ($productos as $producto)
                <option value="{{ $producto->id }}" @selected(($filtros['producto_id'] ?? null) == $producto->id)>{{ $producto->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="f-desde" class="label">Desde</label>
        <input id="f-desde" name="desde" type="date" class="input" value="{{ $filtros['desde'] ?? '' }}">
    </div>
    <div>
        <label for="f-hasta" class="label">Hasta</label>
        <input id="f-hasta" name="hasta" type="date" class="input" value="{{ $filtros['hasta'] ?? '' }}">
    </div>
    @if ($puedeElegirSucursal)
        <div>
            <label for="f-origen" class="label">Origen</label>
            <select id="f-origen" name="sucursal_origen_id" class="input">
                <option value="">Todas</option>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}" @selected(($filtros['sucursal_origen_id'] ?? null) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f-destino" class="label">Destino</label>
            <select id="f-destino" name="sucursal_destino_id" class="input">
                <option value="">Todas</option>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}" @selected(($filtros['sucursal_destino_id'] ?? null) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="flex gap-2 {{ $puedeElegirSucursal ? 'sm:col-span-2 lg:col-span-5' : 'sm:col-span-2 lg:col-span-2' }}">
        <a href="{{ route('traspasos.index') }}" class="btn btn-ghost">Limpiar</a>
        <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Filtrar</button>
    </div>
</form>

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Fecha</th><th>Nº</th><th>Origen</th><th>Destino</th><th class="text-right">Productos</th><th class="text-right">Unidades</th><th>Registró</th><th class="text-right">Detalle</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $traspaso)
            <tr>
                <td class="whitespace-nowrap">{{ $traspaso->fecha->format('d/m/Y') }}</td>
                <td class="font-mono text-xs text-ink-muted">{{ $traspaso->numero() }}</td>
                <td>{{ $traspaso->origen?->nombre }}</td>
                <td>{{ $traspaso->destino?->nombre }}</td>
                <td class="text-right tabular-nums">{{ $traspaso->items_count }}</td>
                <td class="text-right tabular-nums">{{ (int) $traspaso->unidades }}</td>
                <td class="text-ink-muted">{{ $traspaso->usuario?->username }}</td>
                <td>
                    <div class="flex justify-end">
                        <a href="{{ route('traspasos.show', $traspaso) }}" class="btn btn-ghost btn-icon" title="Ver detalle" aria-label="Ver detalle"><x-icon name="eye" class="size-4" /></a>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="8">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="arrow-left-right" class="size-5" /></span>
                    <div>
                        <p class="font-medium">No hay traspasos para mostrar</p>
                        <p class="text-sm text-ink-muted">Traslade productos de una sucursal a otra, o ajuste los filtros.</p>
                    </div>
                    <a href="{{ route('traspasos.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nuevo traspaso</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
