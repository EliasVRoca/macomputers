@extends('layouts.app')

@section('titulo', 'Ventas')
@section('encabezado', 'Ventas')
@section('subtitulo', 'Ventas de productos. Descuentan stock y emiten nota de venta.')
@section('acciones')
    <a href="{{ route('ventas.create') }}" class="btn btn-primary">Nueva venta</a>
@endsection

@section('contenido')
<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead><tr><th>Nota</th><th>Fecha</th><th>Cliente</th><th>Sucursal</th><th class="text-right">Total</th><th>Estado</th><th class="text-right">Detalle</th></tr></thead>
        <tbody>
        @forelse ($items as $venta)
            <tr>
                <td class="font-mono text-xs">{{ $venta->numero() }}</td>
                <td class="whitespace-nowrap">{{ $venta->fecha->format('d/m/Y H:i') }}</td>
                <td>{{ $venta->cliente?->nombre ?? 'Sin cliente' }}</td>
                <td>{{ $venta->sucursal?->nombre }}</td>
                <td class="text-right">Bs {{ number_format($venta->total, 2, ',', '.') }}</td>
                <td><span class="badge">{{ ucfirst($venta->estado) }}</span></td>
                <td class="text-right"><a href="{{ route('ventas.show', $venta) }}" class="btn btn-ghost !py-1.5 text-sm">Ver</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-ink-muted">No hay ventas registradas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
