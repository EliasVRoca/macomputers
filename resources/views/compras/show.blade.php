@extends('layouts.app')

@section('titulo', 'Compra #'.$compra->id)
@section('encabezado', 'Compra #'.$compra->id)
@section('subtitulo', $compra->fecha->format('d/m/Y').' · '.$compra->proveedor?->nombre.' · '.$compra->sucursal?->nombre)
@section('acciones')
    <a href="{{ route('compras.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Producto</th><th class="text-right">Cantidad</th><th class="text-right">Precio</th><th class="text-right">Subtotal</th></tr></thead>
        <tbody>
        @foreach ($compra->items as $item)
            <tr>
                <td>
                    {{ $item->producto?->nombre }}
                    <span class="block text-xs text-ink-muted">{{ $item->producto?->codigo }}</span>
                </td>
                <td class="text-right tabular-nums">{{ $item->cantidad }}</td>
                <td class="text-right tabular-nums">Bs {{ number_format($item->precio_compra, 2, ',', '.') }}</td>
                <td class="text-right tabular-nums">Bs {{ number_format($item->subtotal, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="px-4 py-3 text-right font-medium">Total</td>
                <td class="px-4 py-3 text-right font-medium tabular-nums">Bs {{ number_format($compra->total, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<p class="mt-3 text-sm text-ink-muted">Registrado por {{ $compra->usuario?->username }}.</p>
@endsection
