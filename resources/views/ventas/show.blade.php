@extends('layouts.app')

@section('titulo', 'Nota de venta '.$venta->numero())
@section('encabezado', 'Nota de venta '.$venta->numero())
@section('subtitulo', $venta->fecha->format('d/m/Y H:i').' · '.$venta->sucursal?->nombre.' · '.($venta->cliente?->nombre ?? 'Sin cliente'))
@section('acciones')
    <a href="{{ route('ventas.index') }}" class="btn btn-secondary">Volver</a>
    @if ($venta->estado === \App\Models\Venta::ESTADO_ACTIVA)
        <form method="POST" action="{{ route('ventas.anular', $venta) }}" onsubmit="return confirm('¿Anular la venta {{ $venta->numero() }}? El stock volverá a la sucursal.')">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-danger">Anular venta</button>
        </form>
    @endif
@endsection

@section('contenido')
@error('anular') <p class="error-texto mb-4">{{ $message }}</p> @enderror
<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead><tr><th>Producto</th><th class="text-right">Cantidad</th><th class="text-right">Precio</th><th class="text-right">Subtotal</th></tr></thead>
        <tbody>
        @foreach ($venta->items as $item)
            <tr>
                <td>{{ $item->descripcion }}</td>
                <td class="text-right">{{ $item->cantidad }}</td>
                <td class="text-right">Bs {{ number_format($item->precio_unitario, 2, ',', '.') }}</td>
                <td class="text-right">Bs {{ number_format($item->subtotal, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="px-4 py-3 text-right font-medium">Total ({{ $venta->medio_pago }})</td>
                <td class="px-4 py-3 text-right font-medium">Bs {{ number_format($venta->total, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<p class="mt-3 text-sm text-ink-muted">Estado: {{ ucfirst($venta->estado) }}. Registró {{ $venta->usuario?->username }}.</p>
@endsection
