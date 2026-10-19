<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nota de venta {{ $venta->numero() }} · M&amp;A Computers</title>
    <link rel="icon" href="{{ asset('images/logo-mark.jpg') }}">
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-imprimir { display: none !important; }
            body { background: #fff; }
            .hoja { box-shadow: none; border: none; margin: 0; }
        }
    </style>
</head>
<body class="min-h-screen bg-neutral-100 px-4 py-8 font-sans text-neutral-900">
@php $empresa = 'M&A Computers'; @endphp

<div class="no-imprimir mx-auto mb-4 flex max-w-3xl justify-end gap-2">
    <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">Imprimir</button>
    <a href="{{ route('ventas.show', $venta) }}" class="btn btn-secondary btn-sm">Volver</a>
</div>

<div class="hoja mx-auto max-w-3xl rounded-lg border border-neutral-300 bg-white p-8 text-black shadow-sm">
    <header class="flex items-start justify-between gap-6 border-b border-neutral-300 pb-4">
        <div>
            <p class="text-lg font-semibold tracking-tight">{{ $empresa }}</p>
            <p class="text-sm text-neutral-600">Venta y soporte de equipos de cómputo</p>
            <p class="text-sm text-neutral-600">{{ $venta->sucursal?->direccion }}</p>
            @if ($venta->sucursal?->telefono)
                <p class="text-sm text-neutral-600">Tel. {{ $venta->sucursal->telefono }}</p>
            @endif
        </div>
        <div class="text-right">
            <p class="text-xs font-medium tracking-widest text-neutral-500 uppercase">Nota de venta</p>
            <p class="text-2xl font-semibold">{{ $venta->numero() }}</p>
            <p class="mt-1 text-sm text-neutral-600">{{ $venta->fecha?->format('d/m/Y H:i') }}</p>
            @if ($venta->estado === \App\Models\Venta::ESTADO_ANULADA)
                <p class="mt-1 text-sm font-semibold text-red-600">ANULADA</p>
            @endif
        </div>
    </header>

    <section class="grid grid-cols-2 gap-6 border-b border-neutral-200 py-4 text-sm">
        <div>
            <p class="text-xs font-medium tracking-wide text-neutral-500 uppercase">Cliente</p>
            <p class="font-medium">{{ $venta->cliente?->nombre ?? 'Sin cliente registrado' }}</p>
            @if ($venta->cliente?->telefono) <p class="text-neutral-600">Tel. {{ $venta->cliente->telefono }}</p> @endif
            @if ($venta->cliente?->carnet) <p class="text-neutral-600">CI {{ $venta->cliente->carnet }}</p> @endif
        </div>
        <div class="text-right">
            <p class="text-xs font-medium tracking-wide text-neutral-500 uppercase">Medio de pago</p>
            <p class="font-medium">{{ $venta->medio_pago ?? '—' }}</p>
            <p class="text-neutral-600">Atendió: {{ $venta->usuario?->nombre() ?? $venta->usuario?->username }}</p>
        </div>
    </section>

    <section class="border-b border-neutral-200 py-4">
        <table class="w-full border-collapse text-sm">
            <thead>
                <tr class="border-b border-neutral-300 text-left text-neutral-500">
                    <th class="py-1.5">Descripción</th>
                    <th class="py-1.5 text-right">Cant.</th>
                    <th class="py-1.5 text-right">P. unit.</th>
                    <th class="py-1.5 text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
            @foreach ($venta->items as $item)
                <tr class="border-b border-neutral-100">
                    <td class="py-1.5">{{ $item->descripcion }}</td>
                    <td class="py-1.5 text-right">{{ $item->cantidad }}</td>
                    <td class="py-1.5 text-right">Bs {{ number_format($item->precio_unitario, 2, ',', '.') }}</td>
                    <td class="py-1.5 text-right">Bs {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <section class="flex justify-end py-4">
        <dl class="w-full max-w-xs space-y-1 text-sm">
            <div class="flex justify-between border-t border-neutral-300 pt-1.5 text-base font-semibold"><dt>Total</dt><dd>Bs {{ number_format($venta->total, 2, ',', '.') }}</dd></div>
        </dl>
    </section>

    <p class="mt-6 text-center text-xs text-neutral-500">
        Documento interno, no fiscal. Conserve esta nota para cualquier reclamo o cambio.
    </p>
</div>
</body>
</html>
