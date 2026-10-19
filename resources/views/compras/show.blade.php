@extends('layouts.app')

@section('titulo', 'Compra #'.$compra->id)
@section('encabezado', 'Compra #'.$compra->id)
@section('subtitulo', $compra->fecha->format('d/m/Y').' · '.$compra->proveedor?->nombre.' · '.$compra->sucursal?->nombre)
@section('acciones')
    <a href="{{ route('compras.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
@if ($compra->estaAnulada())
    <div role="status" class="mb-6 flex flex-wrap items-center gap-x-2 gap-y-1 rounded-lg border border-danger px-4 py-3 text-sm">
        <span class="badge badge-danger">Anulada</span>
        <span>
            el {{ $compra->anulada_at->format('d/m/Y H:i') }} por {{ $compra->anuladaPor?->username ?? '—' }}.@if ($compra->motivo_anulacion)
                <span class="text-ink-muted">Motivo: {{ $compra->motivo_anulacion }}</span>@endif
        </span>
    </div>
@endif

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

@unless ($compra->estaAnulada())
    <div class="card card-pad mt-6 max-w-xl space-y-4">
        <div>
            <h2 class="text-sm font-medium">Anular compra</h2>
            <p class="text-sm text-ink-muted">Revierte el stock que ingresó esta compra. No se puede anular si la mercadería ya se vendió o traspasó.</p>
        </div>
        <form method="POST" action="{{ route('compras.anular', $compra) }}" class="space-y-4" onsubmit="return confirm('¿Anular la compra #{{ $compra->id }}? Se revertirá el stock que ingresó.')">
            @csrf
            @method('PATCH')
            <div>
                <label for="motivo" class="label">Motivo (opcional)</label>
                <input id="motivo" name="motivo" type="text" maxlength="150" value="{{ old('motivo') }}" class="input" placeholder="Ej. mercadería defectuosa">
                @error('motivo') <p class="error-texto">{{ $message }}</p> @enderror
                @error('compra') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-danger"><x-icon name="x" class="size-4" /> Anular compra</button>
        </form>
    </div>
@endunless
@endsection
