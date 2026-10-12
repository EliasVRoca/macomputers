@extends('layouts.app')

@section('titulo', 'Clientes')
@section('encabezado', 'Clientes')
@section('subtitulo', 'Clientes asociados a ventas, apartados, encargos y servicios.')
@section('acciones')
    <a href="{{ route('clientes.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo cliente</a>
@endsection

@section('contenido')
@error('eliminar') <p class="error-texto mb-4">{{ $message }}</p> @enderror

<form method="GET" action="{{ route('clientes.index') }}" class="mb-4 flex gap-2">
    <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar por nombre o carnet" class="input max-w-sm" aria-label="Buscar cliente">
    <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Buscar</button>
</form>

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Nombre</th><th>Teléfono</th><th>Carnet</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $cliente)
            <tr>
                <td class="font-medium">{{ $cliente->nombre }}</td>
                <td>{{ $cliente->telefono ?? '—' }}</td>
                <td>{{ $cliente->carnet ?? '—' }}</td>
                <td>
                    <div class="flex justify-end gap-1">
                        <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-ghost btn-icon" title="Editar" aria-label="Editar"><x-icon name="pencil" class="size-4" /></a>
                        <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" onsubmit="return confirm('¿Eliminar al cliente {{ $cliente->nombre }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Eliminar" aria-label="Eliminar"><x-icon name="trash" class="size-4" /></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="4">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="user-round" class="size-5" /></span>
                    <div>
                        <p class="font-medium">No se encontraron clientes</p>
                        <p class="text-sm text-ink-muted">Registre un cliente o ajuste la búsqueda.</p>
                    </div>
                    <a href="{{ route('clientes.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nuevo cliente</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
