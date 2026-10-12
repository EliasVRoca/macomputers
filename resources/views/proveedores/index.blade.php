@extends('layouts.app')

@section('titulo', 'Proveedores')
@section('encabezado', 'Proveedores')
@section('subtitulo', 'Empresas que abastecen de mercadería a M&A Computers.')
@section('acciones')
    <a href="{{ route('proveedores.create') }}" class="btn btn-primary">Nuevo proveedor</a>
@endsection

@section('contenido')
<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead><tr><th>Nombre</th><th>Contacto</th><th>País de origen</th><th class="text-right">Acciones</th></tr></thead>
        <tbody>
        @forelse ($items as $proveedor)
            <tr>
                <td class="font-medium">{{ $proveedor->nombre }}</td>
                <td>{{ $proveedor->contacto ?? '—' }}</td>
                <td>{{ $proveedor->pais_origen ?? '—' }}</td>
                <td class="flex justify-end gap-1">
                    <a href="{{ route('proveedores.edit', $proveedor) }}" class="btn btn-ghost !py-1.5 text-sm">Editar</a>
                    <form method="POST" action="{{ route('proveedores.destroy', $proveedor) }}" onsubmit="return confirm('¿Eliminar al proveedor {{ $proveedor->nombre }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost !py-1.5 text-sm text-semantic-error">Eliminar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-ink-muted">No hay proveedores registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@error('eliminar') <p class="error-texto mt-3">{{ $message }}</p> @enderror
<div class="mt-4">{{ $items->links() }}</div>
@endsection
