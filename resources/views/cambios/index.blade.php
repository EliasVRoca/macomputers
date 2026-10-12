@extends('layouts.app')

@section('titulo', 'Cambios y devoluciones')
@section('encabezado', 'Cambios y devoluciones')
@section('subtitulo', 'Cambios o devoluciones por falla, a partir de una venta registrada.')
@section('acciones')
    <a href="{{ route('cambios.create') }}" class="btn btn-primary">Nuevo cambio</a>
@endsection

@section('contenido')
<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead><tr><th>Fecha</th><th>Venta</th><th>Producto</th><th>Tipo</th><th>Motivo</th><th class="text-right">Detalle</th></tr></thead>
        <tbody>
        @forelse ($items as $cambio)
            <tr>
                <td>{{ $cambio->fecha->format('d/m/Y') }}</td>
                <td class="font-mono text-xs">{{ $cambio->venta?->numero() }}</td>
                <td>
                    {{ $cambio->producto?->nombre }}
                    @if ($cambio->reemplazo)<span class="block text-xs text-ink-muted">Reemplazo: {{ $cambio->reemplazo->nombre }}</span>@endif
                </td>
                <td><span class="badge">{{ ucfirst($cambio->tipo) }}</span></td>
                <td class="max-w-xs truncate">{{ $cambio->motivo }}</td>
                <td class="text-right"><a href="{{ route('cambios.show', $cambio) }}" class="btn btn-ghost !py-1.5 text-sm">Ver</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-ink-muted">No hay cambios registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
