@extends('layouts.app')

@section('titulo', 'Compras')
@section('encabezado', 'Compras')
@section('subtitulo', 'Ingreso de mercadería desde proveedores. Suma al stock de la sucursal.')
@section('acciones')
    <a href="{{ route('compras.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nueva compra</a>
@endsection

@section('contenido')
<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Fecha</th><th>Proveedor</th><th>Sucursal</th><th class="text-right">Total</th><th>Registró</th><th class="text-right">Detalle</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $compra)
            <tr>
                <td>{{ $compra->fecha->format('d/m/Y') }}</td>
                <td class="font-medium">{{ $compra->proveedor?->nombre }}</td>
                <td class="text-ink-muted">{{ $compra->sucursal?->nombre }}</td>
                <td class="text-right tabular-nums">Bs {{ number_format($compra->total, 2, ',', '.') }}</td>
                <td class="text-ink-muted">{{ $compra->usuario?->username }}</td>
                <td>
                    <div class="flex justify-end">
                        <a href="{{ route('compras.show', $compra) }}" class="btn btn-ghost btn-icon" title="Ver detalle" aria-label="Ver detalle"><x-icon name="eye" class="size-4" /></a>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">
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
