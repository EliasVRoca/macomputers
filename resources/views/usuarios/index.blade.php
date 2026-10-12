@extends('layouts.app')

@section('titulo', 'Usuarios')
@section('encabezado', 'Usuarios')
@section('subtitulo', 'Personal con acceso al sistema, su rol y sucursal.')
@section('acciones')
    <a href="{{ route('usuarios.create') }}" class="btn btn-primary">Nuevo usuario</a>
@endsection

@section('contenido')
<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead><tr><th>Usuario</th><th>Persona</th><th>Correo</th><th>Rol</th><th>Sucursal</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
        <tbody>
        @forelse ($items as $usuario)
            <tr>
                <td class="font-medium">{{ $usuario->username }}</td>
                <td>{{ $usuario->persona?->nombre ?? '—' }}</td>
                <td>{{ $usuario->email }}</td>
                <td>{{ $usuario->role?->nombre }}</td>
                <td>{{ $usuario->sucursal?->nombre ?? '—' }}</td>
                <td><span class="badge">{{ $usuario->activo ? 'Activo' : 'Inactivo' }}</span></td>
                <td class="flex justify-end gap-1">
                    <a href="{{ route('usuarios.edit', $usuario) }}" class="btn btn-ghost !py-1.5 text-sm">Editar</a>
                    @if (auth()->id() !== $usuario->id)
                        <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}" onsubmit="return confirm('¿Eliminar al usuario {{ $usuario->username }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost !py-1.5 text-sm text-semantic-error">Eliminar</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-ink-muted">No hay usuarios registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@error('eliminar') <p class="error-texto mt-3">{{ $message }}</p> @enderror
<div class="mt-4">{{ $items->links() }}</div>
@endsection
