@extends('layouts.app')

@section('titulo', 'Usuarios')
@section('encabezado', 'Usuarios')
@section('subtitulo', 'Personal con acceso al sistema, su rol y sucursal.')
@section('acciones')
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo usuario</a>
@endsection

@section('contenido')
@error('eliminar') <p class="error-texto mb-4">{{ $message }}</p> @enderror
@error('estado') <p class="error-texto mb-4">{{ $message }}</p> @enderror

<form method="GET" action="{{ route('usuarios.index') }}" class="card card-pad mb-4 grid gap-3 sm:grid-cols-6">
    <div class="sm:col-span-2">
        <label for="buscar" class="label">Buscar</label>
        <input type="search" id="buscar" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Usuario, nombre, correo o C.I." class="input">
    </div>
    <div>
        <label for="f-rol" class="label">Rol</label>
        <select id="f-rol" name="role_id" class="input">
            <option value="">Todos</option>
            @foreach ($roles as $rol)
                <option value="{{ $rol->id }}" @selected((string) ($filtros['role_id'] ?? '') === (string) $rol->id)>{{ $rol->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="f-sucursal" class="label">Sucursal</label>
        <select id="f-sucursal" name="sucursal_id" class="input">
            <option value="">Todas</option>
            @foreach ($sucursales as $sucursal)
                <option value="{{ $sucursal->id }}" @selected((string) ($filtros['sucursal_id'] ?? '') === (string) $sucursal->id)>{{ $sucursal->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="f-activo" class="label">Estado</label>
        <select id="f-activo" name="activo" class="input">
            <option value="">Todos</option>
            <option value="1" @selected(($filtros['activo'] ?? null) === true)>Activos</option>
            <option value="0" @selected(($filtros['activo'] ?? null) === false)>Inactivos</option>
        </select>
    </div>
    <div class="flex items-end gap-2">
        <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Filtrar</button>
        <a href="{{ route('usuarios.index') }}" class="btn btn-ghost">Limpiar</a>
    </div>
</form>

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Usuario</th><th>Persona</th><th>Correo</th><th>Rol</th><th>Sucursal</th><th>Estado</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $usuario)
            <tr>
                <td class="font-medium">{{ $usuario->username }}</td>
                <td>{{ $usuario->persona?->nombre ?? '—' }}</td>
                <td class="text-ink-muted">{{ $usuario->email }}</td>
                <td><span class="badge badge-brand">{{ $usuario->role?->nombre }}</span></td>
                <td>{{ $usuario->sucursal?->nombre ?? '—' }}</td>
                <td>
                    @if ($usuario->activo)
                        <span class="badge badge-ok"><span class="dot"></span>Activo</span>
                    @else
                        <span class="badge badge-neutral"><span class="dot"></span>Inactivo</span>
                    @endif
                </td>
                <td>
                    <div class="flex justify-end gap-1">
                        <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-ghost btn-icon" title="Editar" aria-label="Editar"><x-icon name="pencil" class="size-4" /></a>
                        @if (auth()->id() !== $usuario->id)
                            <form method="POST" action="{{ route('usuarios.estado', $usuario) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="activo" value="{{ $usuario->activo ? 0 : 1 }}">
                                <button type="submit" class="btn btn-ghost btn-icon" title="{{ $usuario->activo ? 'Dar de baja' : 'Reactivar' }}" aria-label="{{ $usuario->activo ? 'Dar de baja' : 'Reactivar' }}"><x-icon name="{{ $usuario->activo ? 'lock' : 'unlock' }}" class="size-4" /></button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="users" class="size-5" /></span>
                    <div>
                        <p class="font-medium">No se encontraron usuarios</p>
                        <p class="text-sm text-ink-muted">Registre al personal o ajuste los filtros.</p>
                    </div>
                    <a href="{{ route('usuarios.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nuevo usuario</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
