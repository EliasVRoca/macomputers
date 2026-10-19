@extends('layouts.app')

@section('titulo', 'Apartado #'.$apartado->id)
@section('encabezado', 'Apartado #'.$apartado->id)
@section('subtitulo', $apartado->cliente?->nombre.' · '.$apartado->fecha?->format('d/m/Y'))
@section('acciones')
    @if ($apartado->estado === \App\Models\Apartado::ESTADO_ACTIVO)
        <a href="{{ route('apartados.edit', $apartado) }}" class="btn btn-primary"><x-icon name="coins" class="size-4" /> Registrar pago</a>
    @endif
    <a href="{{ route('apartados.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
@error('apartado') <p class="error-texto mb-4">{{ $message }}</p> @enderror

@php
    $colorEstado = match ($apartado->estado) {
        \App\Models\Apartado::ESTADO_COMPLETADO => 'badge-ok',
        \App\Models\Apartado::ESTADO_CANCELADO => 'badge-neutral',
        default => 'badge-warn',
    };
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="card card-pad">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-medium text-ink-muted">Detalle</h2>
                <span class="badge {{ $colorEstado }}"><span class="dot"></span>{{ ucfirst($apartado->estado) }}</span>
            </div>
            <dl class="grid gap-4 sm:grid-cols-2">
                <div><dt class="text-xs text-ink-muted">Cliente</dt><dd class="font-medium">{{ $apartado->cliente?->nombre }}</dd></div>
                <div><dt class="text-xs text-ink-muted">Producto</dt><dd>{{ $apartado->cantidad }} × {{ $apartado->producto?->nombre }}</dd></div>
                <div><dt class="text-xs text-ink-muted">Precio unitario</dt><dd class="tabular-nums">Bs {{ number_format($apartado->precio_unitario, 2, ',', '.') }}</dd></div>
                <div><dt class="text-xs text-ink-muted">Sucursal</dt><dd>{{ $apartado->sucursal?->nombre }}</dd></div>
                <div><dt class="text-xs text-ink-muted">Seña pagada</dt><dd class="tabular-nums">Bs {{ number_format($apartado->sena, 2, ',', '.') }}</dd></div>
                <div><dt class="text-xs text-ink-muted">Saldo</dt><dd class="font-medium tabular-nums">Bs {{ number_format($apartado->saldo, 2, ',', '.') }}</dd></div>
                @if ($apartado->sena_retenida > 0)
                    <div><dt class="text-xs text-ink-muted">Seña retenida</dt><dd class="tabular-nums">Bs {{ number_format($apartado->sena_retenida, 2, ',', '.') }}</dd></div>
                @endif
                @if ($apartado->motivo_cancelacion)
                    <div class="sm:col-span-2"><dt class="text-xs text-ink-muted">Motivo de cancelación</dt><dd>{{ $apartado->motivo_cancelacion }}</dd></div>
                @endif
                <div><dt class="text-xs text-ink-muted">Registró</dt><dd>{{ $apartado->usuario?->username }}</dd></div>
                @if ($apartado->venta)
                    <div><dt class="text-xs text-ink-muted">Venta generada</dt><dd><a href="{{ route('ventas.show', $apartado->venta) }}" class="text-brand hover:underline">{{ $apartado->venta->numero() }}</a></dd></div>
                @endif
            </dl>
        </div>
    </div>

    @if ($apartado->estado === \App\Models\Apartado::ESTADO_ACTIVO)
        <aside class="space-y-6">
            <div class="card card-pad">
                <h2 class="mb-4 text-sm font-medium text-ink-muted">Cancelar apartado</h2>
                <form method="POST" action="{{ route('apartados.cancelar', $apartado) }}" class="space-y-4" onsubmit="return confirm('¿Cancelar el apartado? El stock reservado se liberará.')">
                    @csrf @method('PATCH')
                    <div>
                        <label for="motivo_cancelacion" class="label">Motivo <span class="font-normal text-ink-subtle">(opcional)</span></label>
                        <input id="motivo_cancelacion" name="motivo_cancelacion" class="input" value="{{ old('motivo_cancelacion') }}" maxlength="255" placeholder="Ej. el cliente desistió">
                        @error('motivo_cancelacion') <p class="error-texto">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="sena_retenida" class="label">Seña retenida (Bs)</label>
                        <input id="sena_retenida" name="sena_retenida" type="number" step="0.01" min="0" max="{{ $apartado->sena }}" class="input" value="{{ old('sena_retenida', 0) }}">
                        @error('sena_retenida') <p class="error-texto">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-ink-subtle">Monto informativo (sin movimiento de caja).</p>
                    </div>
                    <button type="submit" class="btn btn-danger"><x-icon name="x" class="size-4" /> Cancelar apartado</button>
                </form>
            </div>
        </aside>
    @endif
</div>
@endsection
