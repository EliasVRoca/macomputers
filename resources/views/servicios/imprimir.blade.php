<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orden {{ $servicio->numero }} · M&amp;A Computers</title>
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
@php
    use App\Enums\EstadoServicio;
    $empresa = 'M&A Computers';
@endphp

<div class="no-imprimir mx-auto mb-4 flex max-w-3xl justify-end gap-2">
    <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">Imprimir</button>
    <a href="{{ route('servicios.show', $servicio) }}" class="btn btn-secondary btn-sm">Volver</a>
</div>

<div class="hoja mx-auto max-w-3xl rounded-lg border border-neutral-300 bg-white p-8 text-black shadow-sm">
    <header class="flex items-start justify-between gap-6 border-b border-neutral-300 pb-4">
        <div>
            <p class="text-lg font-semibold tracking-tight">{{ $empresa }}</p>
            <p class="text-sm text-neutral-600">Venta y soporte de equipos de cómputo</p>
            <p class="text-sm text-neutral-600">{{ $servicio->sucursal?->direccion }}</p>
            @if ($servicio->sucursal?->telefono)
                <p class="text-sm text-neutral-600">Tel. {{ $servicio->sucursal->telefono }}</p>
            @endif
        </div>
        <div class="text-right">
            <p class="text-xs font-medium tracking-widest text-neutral-500 uppercase">Orden de trabajo</p>
            <p class="text-2xl font-semibold">{{ $servicio->numero }}</p>
            <p class="mt-1 text-sm text-neutral-600">{{ $servicio->estadoEnum()->etiqueta() }}</p>
        </div>
    </header>

    <section class="grid grid-cols-2 gap-6 border-b border-neutral-200 py-4 text-sm">
        <div>
            <p class="text-xs font-medium tracking-wide text-neutral-500 uppercase">Cliente</p>
            <p class="font-medium">{{ $servicio->cliente?->nombre ?? '—' }}</p>
            @if ($servicio->cliente?->telefono) <p class="text-neutral-600">Tel. {{ $servicio->cliente->telefono }}</p> @endif
            @if ($servicio->cliente?->carnet) <p class="text-neutral-600">CI {{ $servicio->cliente->carnet }}</p> @endif
        </div>
        <div>
            <p class="text-xs font-medium tracking-wide text-neutral-500 uppercase">Fechas</p>
            <p>Recepción: <span class="font-medium">{{ $servicio->fecha_recepcion?->format('d/m/Y') ?? '—' }}</span></p>
            <p>Prometida: <span class="font-medium">{{ $servicio->fecha_prometida?->format('d/m/Y') ?? '—' }}</span></p>
            <p>Entrega: <span class="font-medium">{{ $servicio->fecha_entrega?->format('d/m/Y') ?? '—' }}</span></p>
        </div>
    </section>

    <section class="grid grid-cols-2 gap-6 border-b border-neutral-200 py-4 text-sm">
        <div>
            <p class="text-xs font-medium tracking-wide text-neutral-500 uppercase">Equipo</p>
            <p class="font-medium">{{ $servicio->equipo }}</p>
            <p class="text-neutral-600">Accesorios: {{ $servicio->accesorios_recibidos ?: '—' }}</p>
            <p class="text-neutral-600">Prioridad: {{ \App\Models\Servicio::PRIORIDADES[$servicio->prioridad] ?? $servicio->prioridad }}</p>
        </div>
        <div>
            <p class="text-xs font-medium tracking-wide text-neutral-500 uppercase">Técnico</p>
            <p class="font-medium">{{ $servicio->tecnico?->nombre() ?? '—' }}</p>
            <p class="text-neutral-600">Sucursal: {{ $servicio->sucursal?->nombre }}</p>
        </div>
    </section>

    <section class="border-b border-neutral-200 py-4 text-sm">
        <p class="text-xs font-medium tracking-wide text-neutral-500 uppercase">Falla reportada y diagnóstico</p>
        <p class="mt-1 whitespace-pre-line">{{ $servicio->diagnostico ?: 'Pendiente' }}</p>
    </section>

    <section class="border-b border-neutral-200 py-4">
        <p class="mb-2 text-xs font-medium tracking-wide text-neutral-500 uppercase">Repuestos y materiales</p>
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
            @forelse ($servicio->repuestos as $repuesto)
                <tr class="border-b border-neutral-100">
                    <td class="py-1.5">{{ $repuesto->producto?->nombre }}</td>
                    <td class="py-1.5 text-right">{{ $repuesto->cantidad }}</td>
                    <td class="py-1.5 text-right">Bs {{ number_format($repuesto->precio_unitario, 2, ',', '.') }}</td>
                    <td class="py-1.5 text-right">Bs {{ number_format($repuesto->subtotal, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="py-2 text-neutral-500">Sin repuestos.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <section class="flex justify-end py-4">
        <dl class="w-full max-w-xs space-y-1 text-sm">
            <div class="flex justify-between"><dt class="text-neutral-600">Mano de obra</dt><dd>Bs {{ number_format($servicio->mano_de_obra, 2, ',', '.') }}</dd></div>
            <div class="flex justify-between"><dt class="text-neutral-600">Repuestos</dt><dd>Bs {{ number_format($servicio->repuestosTotal(), 2, ',', '.') }}</dd></div>
            @if ((float) $servicio->descuento > 0)
                <div class="flex justify-between"><dt class="text-neutral-600">Descuento</dt><dd>− Bs {{ number_format($servicio->descuento, 2, ',', '.') }}</dd></div>
            @endif
            <div class="flex justify-between border-t border-neutral-300 pt-1.5 text-base font-semibold"><dt>Total</dt><dd>Bs {{ number_format($servicio->totalCalculado(), 2, ',', '.') }}</dd></div>
        </dl>
    </section>

    @if ($servicio->observaciones)
        <section class="border-t border-neutral-200 py-4 text-sm">
            <p class="text-xs font-medium tracking-wide text-neutral-500 uppercase">Observaciones</p>
            <p class="mt-1 whitespace-pre-line">{{ $servicio->observaciones }}</p>
        </section>
    @endif

    <section class="grid grid-cols-2 gap-10 border-t border-neutral-200 pt-8 text-sm">
        <div class="text-center">
            <div class="mt-10 border-t border-neutral-400 pt-1 text-neutral-600">Firma del técnico</div>
        </div>
        <div class="text-center">
            <div class="mt-10 border-t border-neutral-400 pt-1 text-neutral-600">Firma del cliente (conformidad)</div>
        </div>
    </section>

    <p class="mt-6 text-center text-xs text-neutral-500">
        Garantía del trabajo: {{ $servicio->garantia_dias > 0 ? $servicio->garantia_dias.' días' : 'no aplica' }}.
        Conserve esta orden para cualquier reclamo. Documento no fiscal.
    </p>
</div>
</body>
</html>
