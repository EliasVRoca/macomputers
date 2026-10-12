@extends('layouts.app')

@section('titulo', 'Clientes')
@section('encabezado', 'Clientes')
@section('subtitulo', 'Clientes asociados a ventas, apartados, encargos y servicios.')
@section('acciones')
    <a href="{{ route('clientes.create') }}" class="btn btn-primary">Nuevo cliente</a>
@endsection

@section('contenido')
<form method="GET" action="{{ route('clientes.index') }}" class="mb-4 flex gap-2">
    <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar por nombre o carnet" class="input max-w-sm" aria-label="Buscar cliente">
    <button type="submit" class="btn btn-secondary">Buscar</button>
</form>

<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead><tr><th>Nombre</th><th>Teléfono</th><th>Carnet</th><th class="text-right">Acciones</th></tr></thead>
        <tbody>
        @forelse ($items as $cliente)
            <tr>
                <td class="font-medium">{{ $cliente->nombre }}</td>
                <td>{{ $cliente->telefono ?? '—' }}</td>
                <td>{{ $cliente->carnet ?? '—' }}</td>
                <td class="flex justify-end gap-1">
                    <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-ghost !py-1.5 text-sm">Editar</a>
                    <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" onsubmit="return confirm('¿Eliminar al cliente {{ $cliente->nombre }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost !py-1.5 text-sm text-semantic-error">Eliminar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-ink-muted">No se encontraron clientes.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@error('eliminar') <p class="error-texto mt-3">{{ $message }}</p> @enderror
<div class="mt-4">{{ $items->links() }}</div>
@endsection
