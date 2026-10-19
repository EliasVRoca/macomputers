@extends('layouts.app')

@section('titulo', 'Compras')
@section('encabezado', 'Compras')
@section('subtitulo', 'Ingreso de mercadería desde proveedores. Suma al stock de la sucursal.')
@section('acciones')
    <a href="{{ route('compras.export', request()->query()) }}" class="btn btn-secondary btn-sm">
        <x-icon name="download" class="size-4" /> Exportar CSV
    </a>
    <a href="{{ route('compras.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nueva compra</a>
@endsection

@section('contenido')
<form method="GET" action="{{ route('compras.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
    <div class="min-w-48">
        <label for="proveedor_id" class="label">Proveedor</label>
        <select id="proveedor_id" name="proveedor_id" class="input">
            <option value="">Todos</option>
            @foreach ($proveedores as $proveedor)
                <option value="{{ $proveedor->id }}" @selected(($filtros['proveedor_id'] ?? null) == $proveedor->id)>{{ $proveedor->nombre }}</option>
            @endforeach
        </select>
    </div>
    @if ($puedeElegirSucursal)
        <div class="min-w-44">
            <label for="sucursal_id" class="label">Sucursal</label>
            <select id="sucursal_id" name="sucursal_id" class="input">
                <option value="">Todas</option>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}" @selected(($filtros['sucursal_id'] ?? null) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div>
        <label for="desde" class="label">Desde</label>
        <input id="desde" name="desde" type="date" value="{{ $filtros['desde'] ?? '' }}" class="input">
    </div>
    <div>
        <label for="hasta" class="label">Hasta</label>
        <input id="hasta" name="hasta" type="date" value="{{ $filtros['hasta'] ?? '' }}" class="input">
    </div>
    <label class="mb-2.5 flex items-center gap-2 text-sm text-ink-muted">
        <input type="checkbox" name="incluir_anuladas" value="1" @checked(($filtros['incluir_anuladas'] ?? null) == 1)>
        Incluir anuladas
    </label>
    <div class="mb-0.5 flex gap-2">
        <a href="{{ route('compras.index') }}" class="btn btn-ghost">Limpiar</a>
        <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Buscar</button>
    </div>
</form>

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Fecha</th><th>Proveedor</th><th>Sucursal</th><th class="text-right">Total</th><th>Registró</th><th>Estado</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $compra)
            <tr class="{{ $compra->estaAnulada() ? 'opacity-60' : '' }}">
                <td>{{ $compra->fecha->format('d/m/Y') }}</td>
                <td class="font-medium">{{ $compra->proveedor?->nombre }}</td>
                <td class="text-ink-muted">{{ $compra->sucursal?->nombre }}</td>
                <td class="text-right tabular-nums">Bs {{ number_format($compra->total, 2, ',', '.') }}</td>
                <td class="text-ink-muted">{{ $compra->usuario?->username }}</td>
                <td>
                    @if ($compra->estaAnulada())
                        <span class="badge badge-danger">Anulada</span>
                    @else
                        <span class="badge badge-ok">Activa</span>
                    @endif
                </td>
                <td>
                    <div class="flex justify-end gap-1">
                        <a href="{{ route('compras.show', $compra) }}" class="btn btn-ghost btn-icon" title="Ver detalle" aria-label="Ver detalle"><x-icon name="eye" class="size-4" /></a>
                        @unless ($compra->estaAnulada())
                            <form method="POST" action="{{ route('compras.anular', $compra) }}" onsubmit="return confirm('¿Anular la compra #{{ $compra->id }}? Se revertirá el stock que ingresó.')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Anular compra" aria-label="Anular compra"><x-icon name="x" class="size-4" /></button>
                            </form>
                        @endunless
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="clipboard-list" class="size-5" /></span>
                    <div>
                        <p class="font-medium">No hay compras registradas</p>
                        <p class="text-sm text-ink-muted">Registre el ingreso de mercadería de un proveedor.</p>
                    </div>
                    <a href="{{ route('compras.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nueva compra</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
