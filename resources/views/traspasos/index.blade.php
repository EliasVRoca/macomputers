@extends('layouts.app')

@section('titulo', 'Traspasos')
@section('encabezado', 'Traspasos entre sucursales')
@section('subtitulo', 'Traslados de productos: descuenta del origen y suma al destino.')
@section('acciones')
    <a href="{{ route('traspasos.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo traspaso</a>
@endsection

@section('contenido')
<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Fecha</th><th>Origen</th><th>Destino</th><th class="text-right">Productos</th><th>Registró</th><th class="text-right">Detalle</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $traspaso)
            <tr>
                <td>{{ $traspaso->fecha->format('d/m/Y') }}</td>
                <td>{{ $traspaso->origen?->nombre }}</td>
                <td>{{ $traspaso->destino?->nombre }}</td>
                <td class="text-right">{{ $traspaso->items_count }}</td>
                <td class="text-ink-muted">{{ $traspaso->usuario?->username }}</td>
                <td>
                    <div class="flex justify-end">
                        <a href="{{ route('traspasos.show', $traspaso) }}" class="btn btn-ghost btn-icon" title="Ver detalle" aria-label="Ver detalle"><x-icon name="eye" class="size-4" /></a>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="arrow-left-right" class="size-5" /></span>
                    <div>
                        <p class="font-medium">No hay traspasos registrados</p>
                        <p class="text-sm text-ink-muted">Traslade productos de una sucursal a otra.</p>
                    </div>
                    <a href="{{ route('traspasos.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nuevo traspaso</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
