@extends('layouts.app')

@section('titulo', 'Ventas')
@section('encabezado', 'Ventas')
@section('subtitulo', 'Ventas de productos. Descuentan stock y emiten nota de venta.')
@section('acciones')
    <a href="{{ route('ventas.export', request()->query()) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Exportar CSV</a>
    <a href="{{ route('ventas.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nueva venta</a>
@endsection

@section('contenido')
<form method="GET" action="{{ route('ventas.index') }}" class="card card-pad mb-4 grid gap-3 sm:grid-cols-3 lg:grid-cols-8">
    <div class="sm:col-span-3 lg:col-span-2">
        <label for="buscar" class="label">Buscar</label>
        <input type="search" id="buscar" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Nota, cliente o producto" class="input">
    </div>
    <div>
        <label for="f-cliente" class="label">Cliente</label>
        <select id="f-cliente" name="cliente_id" class="input">
            <option value="">Todos</option>
            @foreach ($clientes as $cliente)
                <option value="{{ $cliente->id }}" @selected(($filtros['cliente_id'] ?? null) == $cliente->id)>{{ $cliente->nombre }}</option>
            @endforeach
        </select>
    </div>
    @if ($puedeElegirSucursal)
        <div>
            <label for="f-sucursal" class="label">Sucursal</label>
            <select id="f-sucursal" name="sucursal_id" class="input">
                <option value="">Todas</option>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}" @selected(($filtros['sucursal_id'] ?? null) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div>
        <label for="f-estado" class="label">Estado</label>
        <select id="f-estado" name="estado" class="input">
            <option value="">Todos</option>
            <option value="activa" @selected(($filtros['estado'] ?? null) === 'activa')>Activa</option>
            <option value="anulada" @selected(($filtros['estado'] ?? null) === 'anulada')>Anulada</option>
        </select>
    </div>
    <div>
        <label for="f-desde" class="label">Desde</label>
        <input id="f-desde" name="desde" type="date" value="{{ $filtros['desde'] ?? '' }}" class="input">
    </div>
    <div>
        <label for="f-hasta" class="label">Hasta</label>
        <input id="f-hasta" name="hasta" type="date" value="{{ $filtros['hasta'] ?? '' }}" class="input">
    </div>
    <div class="flex items-end gap-2">
        <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Filtrar</button>
        <a href="{{ route('ventas.index') }}" class="btn btn-ghost">Limpiar</a>
    </div>
</form>

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Nota</th><th>Fecha</th><th>Cliente</th><th>Sucursal</th><th class="text-right">Total</th><th>Estado</th><th class="text-right">Detalle</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $venta)
            <tr>
                <td class="font-mono text-xs">{{ $venta->numero() }}</td>
                <td class="whitespace-nowrap">{{ $venta->fecha->format('d/m/Y H:i') }}</td>
                <td>{{ $venta->cliente?->nombre ?? 'Sin cliente' }}</td>
                <td class="text-ink-muted">{{ $venta->sucursal?->nombre }}</td>
                <td class="text-right font-medium tabular-nums">Bs {{ number_format($venta->total, 2, ',', '.') }}</td>
                <td>
                    @if ($venta->estado === \App\Models\Venta::ESTADO_ANULADA)
                        <span class="badge badge-danger"><span class="dot"></span>Anulada</span>
                    @else
                        <span class="badge badge-ok"><span class="dot"></span>Activa</span>
                    @endif
                </td>
                <td>
                    <div class="flex justify-end">
                        <a href="{{ route('ventas.show', $venta) }}" class="btn btn-ghost btn-icon" title="Ver nota" aria-label="Ver nota"><x-icon name="eye" class="size-4" /></a>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="receipt" class="size-5" /></span>
                    <div>
                        <p class="font-medium">No hay ventas registradas</p>
                        <p class="text-sm text-ink-muted">Registre una venta para descontar stock y emitir la nota.</p>
                    </div>
                    <a href="{{ route('ventas.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nueva venta</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
