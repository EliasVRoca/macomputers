@extends('layouts.app')

@section('titulo', 'Proveedores')
@section('encabezado', 'Proveedores')
@section('subtitulo', 'Empresas que abastecen de mercadería a M&A Computers.')
@section('acciones')
    <a href="{{ route('proveedores.export', request()->query()) }}" class="btn btn-secondary btn-sm"><x-icon name="download" class="size-4" /> Exportar CSV</a>
    <a href="{{ route('proveedores.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo proveedor</a>
@endsection

@section('contenido')
@error('eliminar') <p class="error-texto mb-4">{{ $message }}</p> @enderror

<form method="GET" action="{{ route('proveedores.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
    <div class="min-w-56">
        <label for="buscar" class="label">Buscar</label>
        <input id="buscar" name="buscar" type="search" value="{{ $buscar }}" placeholder="Nombre, contacto o país" class="input" aria-label="Buscar proveedor">
    </div>
    <div class="mb-0.5 flex gap-2">
        <a href="{{ route('proveedores.index') }}" class="btn btn-ghost">Limpiar</a>
        <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Buscar</button>
    </div>
</form>

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Nombre</th><th>Contacto</th><th>País de origen</th><th class="text-right">Compras</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $proveedor)
            <tr>
                <td>
                    <a href="{{ route('proveedores.show', $proveedor) }}" class="font-medium hover:underline">{{ $proveedor->nombre }}</a>
                </td>
                <td>{{ $proveedor->contacto ?? '—' }}</td>
                <td>{{ $proveedor->pais_origen ?? '—' }}</td>
                <td class="text-right tabular-nums">{{ $proveedor->compras_count }}</td>
                <td>
                    <div class="flex justify-end gap-1">
                        <a href="{{ route('proveedores.show', $proveedor) }}" class="btn btn-ghost btn-icon" title="Ver detalle" aria-label="Ver detalle"><x-icon name="eye" class="size-4" /></a>
                        <a href="{{ route('proveedores.edit', $proveedor) }}" class="btn btn-ghost btn-icon" title="Editar" aria-label="Editar"><x-icon name="pencil" class="size-4" /></a>
                        <form method="POST" action="{{ route('proveedores.destroy', $proveedor) }}" onsubmit="return confirm('¿Eliminar al proveedor {{ $proveedor->nombre }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Eliminar" aria-label="Eliminar"><x-icon name="trash" class="size-4" /></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="truck" class="size-5" /></span>
                    <div>
                        <p class="font-medium">{{ $buscar !== '' ? 'No se encontraron proveedores' : 'No hay proveedores registrados' }}</p>
                        <p class="text-sm text-ink-muted">{{ $buscar !== '' ? 'Ajuste la búsqueda.' : 'Registre a quién le compra la mercadería.' }}</p>
                    </div>
                    <a href="{{ route('proveedores.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nuevo proveedor</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
