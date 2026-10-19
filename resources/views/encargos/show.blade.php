@extends('layouts.app')

@section('titulo', 'Encargo #'.$encargo->id)
@section('encabezado', 'Encargo #'.$encargo->id)
@section('subtitulo', $encargo->cliente?->nombre.' · '.$encargo->fecha?->format('d/m/Y'))
@section('acciones')
    @if ($encargo->estado === \App\Models\Encargo::ESTADO_PENDIENTE)
        <a href="{{ route('encargos.edit', $encargo) }}" class="btn btn-primary"><x-icon name="coins" class="size-4" /> Registrar pago</a>
    @endif
    <a href="{{ route('encargos.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<div class="card card-pad max-w-2xl">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-sm font-medium text-ink-muted">Detalle</h2>
        <span class="badge {{ $encargo->estado === \App\Models\Encargo::ESTADO_COMPLETADO ? 'badge-ok' : 'badge-warn' }}"><span class="dot"></span>{{ ucfirst($encargo->estado) }}</span>
    </div>
    <dl class="grid gap-4 text-sm sm:grid-cols-2">
        <div><dt class="text-xs text-ink-muted">Cliente</dt><dd class="font-medium">{{ $encargo->cliente?->nombre }}</dd></div>
        <div><dt class="text-xs text-ink-muted">Sucursal</dt><dd>{{ $encargo->sucursal?->nombre }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-xs text-ink-muted">Producto</dt><dd>{{ $encargo->producto?->nombre ?? $encargo->descripcion }}</dd></div>
        <div><dt class="text-xs text-ink-muted">Total</dt><dd class="tabular-nums">Bs {{ number_format($encargo->total, 2, ',', '.') }}</dd></div>
        <div><dt class="text-xs text-ink-muted">Anticipo</dt><dd class="tabular-nums">Bs {{ number_format($encargo->anticipo, 2, ',', '.') }}</dd></div>
        <div><dt class="text-xs text-ink-muted">Saldo</dt><dd class="font-medium tabular-nums">Bs {{ number_format($encargo->saldo, 2, ',', '.') }}</dd></div>
        <div><dt class="text-xs text-ink-muted">Registró</dt><dd>{{ $encargo->usuario?->username }}</dd></div>
    </dl>
</div>
@endsection
