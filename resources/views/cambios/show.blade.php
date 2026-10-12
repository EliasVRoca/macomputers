@extends('layouts.app')

@section('titulo', 'Cambio #'.$cambio->id)
@section('encabezado', ucfirst($cambio->tipo).' #'.$cambio->id)
@section('subtitulo', 'Venta '.$cambio->venta?->numero().' · '.$cambio->fecha->format('d/m/Y'))
@section('acciones')
    <a href="{{ route('cambios.index') }}" class="btn btn-secondary">Volver</a>
@endsection

@section('contenido')
<div class="card space-y-3 text-sm">
    <p><span class="text-ink-muted">Producto con falla:</span> {{ $cambio->producto?->nombre }}</p>
    @if ($cambio->reemplazo)
        <p><span class="text-ink-muted">Producto de reemplazo:</span> {{ $cambio->reemplazo->nombre }}</p>
    @endif
    <p><span class="text-ink-muted">Motivo:</span> {{ $cambio->motivo }}</p>
    <p><span class="text-ink-muted">Sucursal:</span> {{ $cambio->sucursal?->nombre }}</p>
    <p><span class="text-ink-muted">Registró:</span> {{ $cambio->usuario?->username }}</p>
</div>
@endsection
