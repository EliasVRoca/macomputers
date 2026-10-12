@extends('layouts.app')

@section('titulo', 'Traspasos')
@section('encabezado', 'Traspasos entre sucursales')
@section('subtitulo', 'Traslados de productos: descuenta del origen y suma al destino.')
@section('acciones')
    <a href="{{ route('traspasos.create') }}" class="btn btn-primary">Nuevo traspaso</a>
@endsection

@section('contenido')
<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead><tr><th>Fecha</th><th>Origen</th><th>Destino</th><th class="text-right">Productos</th><th>Registró</th><th class="text-right">Detalle</th></tr></thead>
        <tbody>
        @forelse ($items as $traspaso)
            <tr>
                <td>{{ $traspaso->fecha->format('d/m/Y') }}</td>
                <td>{{ $traspaso->origen?->nombre }}</td>
                <td>{{ $traspaso->destino?->nombre }}</td>
                <td class="text-right">{{ $traspaso->items_count }}</td>
                <td>{{ $traspaso->usuario?->username }}</td>
                <td class="text-right"><a href="{{ route('traspasos.show', $traspaso) }}" class="btn btn-ghost !py-1.5 text-sm">Ver</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-ink-muted">No hay traspasos registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
