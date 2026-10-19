@extends('layouts.app')

@section('titulo', ucfirst($cambio->tipo).' #'.$cambio->id)
@section('encabezado', ucfirst($cambio->tipo).' #'.$cambio->id)
@section('subtitulo', 'Venta '.$cambio->venta?->numero().' · '.$cambio->fecha->format('d/m/Y'))
@section('acciones')
    <a href="{{ route('cambios.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<div class="card card-pad max-w-2xl space-y-4 text-sm">
    <div class="flex items-center gap-2">
        <span class="badge {{ $cambio->tipo === \App\Models\Cambio::TIPO_DEVOLUCION ? 'badge-danger' : 'badge-brand' }}">{{ ucfirst($cambio->tipo) }}</span>
        <span class="text-ink-muted">{{ $cambio->fecha->format('d/m/Y') }}</span>
    </div>
    <dl class="grid gap-3 sm:grid-cols-2">
        <div>
            <dt class="text-ink-muted">Producto con falla</dt>
            <dd class="font-medium">{{ $cambio->producto?->nombre }}</dd>
        </div>
        <div>
            <dt class="text-ink-muted">Cantidad</dt>
            <dd class="font-medium tabular-nums">{{ $cambio->cantidad }}</dd>
        </div>
        @if ($cambio->reemplazo)
            <div>
                <dt class="text-ink-muted">Producto de reemplazo</dt>
                <dd class="font-medium">{{ $cambio->reemplazo->nombre }}</dd>
            </div>
        @endif
        <div>
            <dt class="text-ink-muted">Diferencia / reembolso</dt>
            <dd class="font-medium tabular-nums">Bs {{ number_format($cambio->monto_diferencia, 2, ',', '.') }}</dd>
        </div>
        <div>
            <dt class="text-ink-muted">Sucursal</dt>
            <dd class="font-medium">{{ $cambio->sucursal?->nombre }}</dd>
        </div>
        <div>
            <dt class="text-ink-muted">Registró</dt>
            <dd class="font-medium">{{ $cambio->usuario?->username }}</dd>
        </div>
    </dl>
    <div>
        <dt class="text-ink-muted">Motivo</dt>
        <dd>{{ $cambio->motivo }}</dd>
    </div>
</div>
@endsection
