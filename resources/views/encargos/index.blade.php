@extends('layouts.app')

@section('titulo', 'Encargos')
@section('encabezado', 'Encargos')
@section('subtitulo', 'Productos no disponibles en stock, pedidos con anticipo.')
@section('acciones')
    <a href="{{ route('encargos.export', request()->query()) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Exportar CSV</a>
    <a href="{{ route('encargos.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo encargo</a>
@endsection

@section('contenido')
@error('encargo') <p class="error-texto mb-4">{{ $message }}</p> @enderror

<form method="GET" action="{{ route('encargos.index') }}" class="card card-pad mb-4 grid gap-3 sm:grid-cols-4">
    <div class="sm:col-span-2">
        <label for="buscar" class="label">Buscar</label>
        <input type="search" id="buscar" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Cliente o producto" class="input">
    </div>
    <div>
        <label for="f-estado" class="label">Estado</label>
        <select id="f-estado" name="estado" class="input">
            <option value="">Todos</option>
            <option value="pendiente" @selected(($filtros['estado'] ?? null) === 'pendiente')>Pendiente</option>
            <option value="completado" @selected(($filtros['estado'] ?? null) === 'completado')>Completado</option>
        </select>
    </div>
    <div class="flex items-end gap-2">
        <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Filtrar</button>
        <a href="{{ route('encargos.index') }}" class="btn btn-ghost">Limpiar</a>
    </div>
</form>

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Fecha</th><th>Cliente</th><th>Producto</th><th>Sucursal</th><th class="text-right">Total</th><th class="text-right">Anticipo</th><th class="text-right">Saldo</th><th>Estado</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $encargo)
            <tr>
                <td class="whitespace-nowrap">{{ $encargo->fecha?->format('d/m/Y') }}</td>
                <td class="font-medium">{{ $encargo->cliente?->nombre }}</td>
                <td>{{ $encargo->producto?->nombre ?? $encargo->descripcion }}</td>
                <td class="text-ink-muted">{{ $encargo->sucursal?->nombre }}</td>
                <td class="text-right tabular-nums">Bs {{ number_format($encargo->total, 2, ',', '.') }}</td>
                <td class="text-right tabular-nums">Bs {{ number_format($encargo->anticipo, 2, ',', '.') }}</td>
                <td class="text-right font-medium tabular-nums">Bs {{ number_format($encargo->saldo, 2, ',', '.') }}</td>
                <td>
                    @if ($encargo->estado === \App\Models\Encargo::ESTADO_COMPLETADO)
                        <span class="badge badge-ok"><span class="dot"></span>Completado</span>
                    @else
                        <span class="badge badge-warn"><span class="dot"></span>Pendiente</span>
                    @endif
                </td>
                <td>
                    <div class="flex justify-end gap-1">
                        @if ($encargo->estado === \App\Models\Encargo::ESTADO_PENDIENTE)
                            <a href="{{ route('encargos.edit', $encargo) }}" class="btn btn-ghost btn-icon" title="Registrar pago" aria-label="Registrar pago"><x-icon name="coins" class="size-4" /></a>
                        @endif
                        <a href="{{ route('encargos.show', $encargo) }}" class="btn btn-ghost btn-icon" title="Ver detalle" aria-label="Ver detalle"><x-icon name="eye" class="size-4" /></a>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="9">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="inbox" class="size-5" /></span>
                    <div>
                        <p class="font-medium">Todavía no hay encargos</p>
                        <p class="text-sm text-ink-muted">Registre un producto a conseguir para un cliente.</p>
                    </div>
                    <a href="{{ route('encargos.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nuevo encargo</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
