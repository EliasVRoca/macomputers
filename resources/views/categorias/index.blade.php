@extends('layouts.app')

@section('titulo', 'Categorías')
@section('encabezado', 'Categorías')
@section('subtitulo', 'Computadoras, accesorios, repuestos y otros.')
@section('acciones')
    <a href="{{ route('categorias.export', request()->query()) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-4" /> Exportar CSV</a>
    <a href="{{ route('categorias.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nueva categoría</a>
@endsection

@section('contenido')
@error('eliminar') <p class="error-texto mb-4">{{ $message }}</p> @enderror

<form method="GET" action="{{ route('categorias.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
    <div class="min-w-56">
        <label for="buscar" class="label">Buscar</label>
        <input id="buscar" name="buscar" type="search" value="{{ $buscar }}" placeholder="Nombre de la categoría" class="input" aria-label="Buscar categoría">
    </div>
    <div class="mb-0.5 flex gap-2">
        <a href="{{ route('categorias.index') }}" class="btn btn-ghost">Limpiar</a>
        <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Buscar</button>
    </div>
</form>

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Nombre</th><th>Descripción</th><th class="text-right">Productos</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $categoria)
            <tr>
                <td class="font-medium">{{ $categoria->nombre }}</td>
                <td class="text-ink-muted">{{ $categoria->descripcion ?? '—' }}</td>
                <td class="text-right">{{ $categoria->productos_count }}</td>
                <td>
                    <div class="flex justify-end gap-1">
                        <a href="{{ route('categorias.edit', $categoria) }}" class="btn btn-ghost btn-icon" title="Editar" aria-label="Editar"><x-icon name="pencil" class="size-4" /></a>
                        <form method="POST" action="{{ route('categorias.destroy', $categoria) }}" data-confirm="¿Eliminar la categoría {{ $categoria->nombre }}?">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Eliminar" aria-label="Eliminar"><x-icon name="trash" class="size-4" /></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="4">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="tag" class="size-5" /></span>
                    <div>
                        <p class="font-medium">{{ $buscar !== '' ? 'No se encontraron categorías' : 'No hay categorías registradas' }}</p>
                        <p class="text-sm text-ink-muted">{{ $buscar !== '' ? 'Ajuste la búsqueda.' : 'Agrupe los productos por tipo.' }}</p>
                    </div>
                    <a href="{{ route('categorias.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nueva categoría</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
