@extends('layouts.app')

@section('titulo', 'Cambios y devoluciones')
@section('encabezado', 'Cambios y devoluciones')
@section('subtitulo', 'Cambios o devoluciones por falla, a partir de una venta registrada.')
@section('acciones')
    <a href="{{ route('cambios.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo cambio</a>
@endsection

@section('contenido')
<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Fecha</th><th>Venta</th><th>Producto</th><th>Tipo</th><th>Motivo</th><th class="text-right">Detalle</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $cambio)
            <tr>
                <td>{{ $cambio->fecha->format('d/m/Y') }}</td>
                <td class="font-mono text-xs">{{ $cambio->venta?->numero() }}</td>
                <td>
                    {{ $cambio->producto?->nombre }}
                    @if ($cambio->reemplazo)<span class="block text-xs text-ink-muted">Reemplazo: {{ $cambio->reemplazo->nombre }}</span>@endif
                </td>
                <td>
                    @if ($cambio->tipo === \App\Models\Cambio::TIPO_DEVOLUCION)
                        <span class="badge badge-danger"><span class="dot"></span>Devolución</span>
                    @else
                        <span class="badge badge-brand"><span class="dot"></span>Cambio</span>
                    @endif
                </td>
                <td class="max-w-xs truncate text-ink-muted">{{ $cambio->motivo }}</td>
                <td>
                    <div class="flex justify-end">
                        <a href="{{ route('cambios.show', $cambio) }}" class="btn btn-ghost btn-icon" title="Ver detalle" aria-label="Ver detalle"><x-icon name="eye" class="size-4" /></a>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="repeat" class="size-5" /></span>
                    <div>
                        <p class="font-medium">No hay cambios registrados</p>
                        <p class="text-sm text-ink-muted">Registre un cambio o devolución a partir de una venta.</p>
                    </div>
                    <a href="{{ route('cambios.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nuevo cambio</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
