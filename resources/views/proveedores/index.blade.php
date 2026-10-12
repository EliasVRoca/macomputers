@extends('layouts.app')

@section('titulo', 'Proveedores')
@section('encabezado', 'Proveedores')
@section('subtitulo', 'Empresas que abastecen de mercadería a M&A Computers.')
@section('acciones')
    <a href="{{ route('proveedores.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo proveedor</a>
@endsection

@section('contenido')
@error('eliminar') <p class="error-texto mb-4">{{ $message }}</p> @enderror

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Nombre</th><th>Contacto</th><th>País de origen</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $proveedor)
            <tr>
                <td class="font-medium">{{ $proveedor->nombre }}</td>
                <td>{{ $proveedor->contacto ?? '—' }}</td>
                <td>{{ $proveedor->pais_origen ?? '—' }}</td>
                <td>
                    <div class="flex justify-end gap-1">
                        <a href="{{ route('proveedores.edit', $proveedor) }}" class="btn btn-ghost btn-icon" title="Editar" aria-label="Editar"><x-icon name="pencil" class="size-4" /></a>
                        <form method="POST" action="{{ route('proveedores.destroy', $proveedor) }}" onsubmit="return confirm('¿Eliminar al proveedor {{ $proveedor->nombre }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Eliminar" aria-label="Eliminar"><x-icon name="trash" class="size-4" /></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="4">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="truck" class="size-5" /></span>
                    <div>
                        <p class="font-medium">No hay proveedores registrados</p>
                        <p class="text-sm text-ink-muted">Registre a quién le compra la mercadería.</p>
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
