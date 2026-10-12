@extends('layouts.app')

@section('titulo', 'Sucursales')
@section('encabezado', 'Sucursales')
@section('subtitulo', 'Tiendas de M&A Computers donde opera el sistema.')
@section('acciones')
    <a href="{{ route('sucursales.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nueva sucursal</a>
@endsection

@section('contenido')
<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Nombre</th><th>Dirección</th><th>Teléfono</th><th>Estado</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $sucursal)
            <tr>
                <td class="font-medium">{{ $sucursal->nombre }}</td>
                <td class="text-ink-muted">{{ $sucursal->direccion }}</td>
                <td>{{ $sucursal->telefono ?? '—' }}</td>
                <td>
                    @if ($sucursal->activa)
                        <span class="badge badge-ok"><span class="dot"></span>Activa</span>
                    @else
                        <span class="badge badge-neutral"><span class="dot"></span>Inactiva</span>
                    @endif
                </td>
                <td>
                    <div class="flex justify-end">
                        <a href="{{ route('sucursales.edit', $sucursal) }}" class="btn btn-ghost btn-icon" title="Editar" aria-label="Editar"><x-icon name="pencil" class="size-4" /></a>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="building" class="size-5" /></span>
                    <div>
                        <p class="font-medium">No hay sucursales registradas</p>
                        <p class="text-sm text-ink-muted">Registre las tiendas de la empresa.</p>
                    </div>
                    <a href="{{ route('sucursales.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nueva sucursal</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
