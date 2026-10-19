@extends('layouts.app')

@section('titulo', 'Nota de venta '.$venta->numero())
@section('encabezado', 'Nota de venta '.$venta->numero())
@section('subtitulo', $venta->fecha->format('d/m/Y H:i').' · '.$venta->sucursal?->nombre.' · '.($venta->cliente?->nombre ?? 'Sin cliente'))
@section('acciones')
    <a href="{{ route('ventas.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
    <a href="{{ route('ventas.imprimir', $venta) }}" target="_blank" class="btn btn-secondary"><x-icon name="printer" class="size-4" /> Imprimir</a>
    @if ($venta->tipo === \App\Models\Venta::TIPO_VENTA && $venta->estado === \App\Models\Venta::ESTADO_ACTIVA && ! $venta->apartado && $venta->cambios->isEmpty())
        <form method="POST" action="{{ route('ventas.anular', $venta) }}" data-confirm="¿Anular la venta {{ $venta->numero() }}? El stock volverá a la sucursal.">
            @csrf @method('PATCH')
            <button type="submit" class="btn btn-danger"><x-icon name="x" class="size-4" /> Anular venta</button>
        </form>
    @endif
@endsection

@section('contenido')
@error('anular') <p class="error-texto mb-4">{{ $message }}</p> @enderror

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead><tr><th>Producto</th><th class="text-right">Cantidad</th><th class="text-right">Precio</th><th class="text-right">Subtotal</th></tr></thead>
        <tbody>
        @foreach ($venta->items as $item)
            <tr>
                <td>{{ $item->descripcion }}</td>
                <td class="text-right tabular-nums">{{ $item->cantidad }}</td>
                <td class="text-right tabular-nums">Bs {{ number_format($item->precio_unitario, 2, ',', '.') }}</td>
                <td class="text-right tabular-nums">Bs {{ number_format($item->subtotal, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="px-4 py-3 text-right font-medium">Total ({{ $venta->medio_pago }})</td>
                <td class="px-4 py-3 text-right font-medium tabular-nums">Bs {{ number_format($venta->total, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<p class="mt-3 flex items-center gap-3 text-sm text-ink-muted">
    <span class="badge {{ $venta->estado === \App\Models\Venta::ESTADO_ANULADA ? 'badge-danger' : 'badge-ok' }}"><span class="dot"></span>{{ ucfirst($venta->estado) }}</span>
    @if ($venta->apartado)
        <span class="badge badge-brand"><span class="dot"></span>Generada desde apartado #{{ $venta->apartado->id }}</span>
    @endif
    Registró {{ $venta->usuario?->username }}.
</p>
@endsection
