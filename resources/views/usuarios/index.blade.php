@extends('layouts.app')

@section('titulo', 'Usuarios')
@section('encabezado', 'Usuarios')
@section('subtitulo', 'Personal con acceso al sistema, su rol y sucursal.')
@section('acciones')
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo usuario</a>
@endsection

@section('contenido')
@error('eliminar') <p class="error-texto mb-4">{{ $message }}</p> @enderror

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
                            <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" onsubmit="return confirm('¿Eliminar al usuario {{ $usuario->username }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Eliminar" aria-label="Eliminar"><x-icon name="trash" class="size-4" /></button>
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
                        <p class="font-medium">Todavía no hay usuarios</p>
                        <p class="text-sm text-ink-muted">Registre al personal y asígnele un rol y una sucursal.</p>
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
