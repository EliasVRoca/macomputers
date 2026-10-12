@extends('layouts.app')

@section('titulo', 'Categorías')
@section('encabezado', 'Categorías')
@section('subtitulo', 'Computadoras, accesorios, repuestos y otros.')
@section('acciones')
    <a href="{{ route('categorias.create') }}" class="btn btn-primary">Nueva categoría</a>
@endsection

@section('contenido')
<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead><tr><th>Nombre</th><th>Descripción</th><th>Productos</th><th class="text-right">Acciones</th></tr></thead>
        <tbody>
        @forelse ($items as $categoria)
            <tr>
                <td class="font-medium">{{ $categoria->nombre }}</td>
                <td>{{ $categoria->descripcion ?? '—' }}</td>
                <td>{{ $categoria->productos_count }}</td>
                <td class="flex justify-end gap-1">
                    <a href="{{ route('categorias.edit', $categoria) }}" class="btn btn-ghost !py-1.5 text-sm">Editar</a>
                    <form method="POST" action="{{ route('categorias.destroy', $categoria) }}" onsubmit="return confirm('¿Eliminar la categoría {{ $categoria->nombre }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost !py-1.5 text-sm text-semantic-error">Eliminar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center text-ink-muted">No hay categorías registradas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@error('eliminar') <p class="error-texto mt-3">{{ $message }}</p> @enderror
<div class="mt-4">{{ $items->links() }}</div>
@endsection
