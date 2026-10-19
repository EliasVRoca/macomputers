@extends('layouts.app')

@section('titulo', 'Clientes')
@section('encabezado', 'Clientes')
@section('subtitulo', 'Clientes asociados a ventas, apartados, encargos y servicios.')
@section('acciones')
    <a href="{{ route('clientes.export', request()->query()) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Exportar CSV</a>
    <a href="{{ route('clientes.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo cliente</a>
@endsection

@section('contenido')
@error('eliminar') <p class="error-texto mb-4">{{ $message }}</p> @enderror

<form method="GET" action="{{ route('clientes.index') }}" class="card card-pad mb-4 grid gap-3 sm:grid-cols-4">
    <div class="sm:col-span-2">
        <label for="buscar" class="label">Buscar</label>
        <input type="search" id="buscar" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Nombre, carnet o teléfono" class="input">
    </div>
    <div>
        <label for="f-activo" class="label">Estado</label>
        <select id="f-activo" name="activo" class="input">
            <option value="">Todos</option>
            <option value="1" @selected(($filtros['activo'] ?? null) === true || ($filtros['activo'] ?? null) === '1')>Activos</option>
            <option value="0" @selected(($filtros['activo'] ?? null) === false || ($filtros['activo'] ?? null) === '0')>Inactivos</option>
        </select>
    </div>
    <div class="flex items-end gap-2">
        <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Filtrar</button>
        <a href="{{ route('clientes.index') }}" class="btn btn-ghost">Limpiar</a>
    </div>
</form>

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Nombre</th><th>Teléfono</th><th>Carnet</th><th>Estado</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $cliente)
            <tr>
                <td class="font-medium">{{ $cliente->nombre }}</td>
                <td>{{ $cliente->telefono ?? '—' }}</td>
                <td>{{ $cliente->carnet ?? '—' }}</td>
                <td>
                    @if ($cliente->activo)
                        <span class="badge badge-ok"><span class="dot"></span>Activo</span>
                    @else
                        <span class="badge badge-neutral"><span class="dot"></span>Inactivo</span>
                    @endif
                </td>
                <td>
                    <div class="flex justify-end gap-1">
                        <a href="{{ route('clientes.show', $cliente) }}" class="btn btn-ghost btn-icon" title="Ver historial" aria-label="Ver historial"><x-icon name="eye" class="size-4" /></a>
                        <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-ghost btn-icon" title="Editar" aria-label="Editar"><x-icon name="pencil" class="size-4" /></a>
                        <form method="POST" action="{{ route('clientes.estado', $cliente) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="activo" value="{{ $cliente->activo ? 0 : 1 }}">
                            <button type="submit" class="btn btn-ghost btn-icon" title="{{ $cliente->activo ? 'Desactivar' : 'Activar' }}" aria-label="{{ $cliente->activo ? 'Desactivar' : 'Activar' }}"><x-icon name="{{ $cliente->activo ? 'lock' : 'unlock' }}" class="size-4" /></button>
                        </form>
                        <form method="POST" action="{{ route('clientes.destroy', $cliente) }}" data-confirm="¿Eliminar al cliente {{ $cliente->nombre }}? Si tiene historial, se desactivará.">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Eliminar" aria-label="Eliminar"><x-icon name="trash" class="size-4" /></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">
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
