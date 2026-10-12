@extends('layouts.app')

@section('titulo', 'Ventas')
@section('encabezado', 'Ventas')
@section('subtitulo', 'Ventas de productos. Descuentan stock y emiten nota de venta.')
@section('acciones')
    <a href="{{ route('ventas.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nueva venta</a>
@endsection

@section('contenido')
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
