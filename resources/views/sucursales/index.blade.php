@extends('layouts.app')

@section('titulo', 'Sucursales')
@section('encabezado', 'Sucursales')
@section('subtitulo', 'Tiendas de M&A Computers en el Comercial Chiriguano.')
@section('acciones')
    <a href="{{ route('sucursales.create') }}" class="btn btn-primary">Nueva sucursal</a>
@endsection

@section('contenido')
<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead>
            <tr><th>Nombre</th><th>Dirección</th><th>Teléfono</th><th>Estado</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $sucursal)
            <tr>
                <td class="font-medium">{{ $sucursal->nombre }}</td>
                <td>{{ $sucursal->direccion }}</td>
                <td>{{ $sucursal->telefono ?? '—' }}</td>
                <td><span class="badge">{{ $sucursal->activa ? 'Activa' : 'Inactiva' }}</span></td>
                <td class="text-right"><a href="{{ route('sucursales.edit', $sucursal) }}" class="btn btn-ghost !py-1.5 text-sm">Editar</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-ink-muted">No hay sucursales registradas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
