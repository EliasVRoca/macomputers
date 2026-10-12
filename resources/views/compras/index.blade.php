@extends('layouts.app')

@section('titulo', 'Compras')
@section('encabezado', 'Compras')
@section('subtitulo', 'Ingreso de mercadería desde proveedores. Suma al stock de la sucursal.')
@section('acciones')
    <a href="{{ route('compras.create') }}" class="btn btn-primary">Nueva compra</a>
@endsection

@section('contenido')
<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead><tr><th>Fecha</th><th>Proveedor</th><th>Sucursal</th><th class="text-right">Total</th><th>Registró</th><th class="text-right">Detalle</th></tr></thead>
        <tbody>
        @forelse ($items as $compra)
            <tr>
                <td>{{ $compra->fecha->format('d/m/Y') }}</td>
                <td>{{ $compra->proveedor?->nombre }}</td>
                <td>{{ $compra->sucursal?->nombre }}</td>
                <td class="text-right">Bs {{ number_format($compra->total, 2, ',', '.') }}</td>
                <td>{{ $compra->usuario?->username }}</td>
                <td class="text-right"><a href="{{ route('compras.show', $compra) }}" class="btn btn-ghost !py-1.5 text-sm">Ver</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-ink-muted">No hay compras registradas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
