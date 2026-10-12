@extends('layouts.app')

@section('titulo', 'Roles')
@section('encabezado', 'Roles y permisos')
@section('subtitulo', 'Cada rol define a qué módulos puede acceder su personal.')
@section('acciones')
    <a href="{{ route('roles.create') }}" class="btn btn-primary">Nuevo rol</a>
@endsection

@section('contenido')
<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead><tr><th>Rol</th><th>Descripción</th><th>Módulos</th><th>Usuarios</th><th class="text-right">Acciones</th></tr></thead>
        <tbody>
        @forelse ($items as $rol)
            <tr>
                <td class="font-medium">{{ $rol->nombre }} @if ($rol->es_sistema)<span class="badge ml-1">Sistema</span>@endif</td>
                <td>{{ $rol->descripcion ?? '—' }}</td>
                <td>{{ $rol->es_sistema ? 'Todos' : count($rol->permisos ?? []) }}</td>
                <td>{{ $rol->users_count }}</td>
                <td class="flex justify-end gap-1">
                    <a href="{{ route('roles.edit', $rol) }}" class="btn btn-ghost !py-1.5 text-sm">{{ $rol->es_sistema ? 'Ver' : 'Editar' }}</a>
                    @unless ($rol->es_sistema)
                        <form method="POST" action="{{ route('roles.destroy', $rol) }}" onsubmit="return confirm('¿Eliminar el rol {{ $rol->nombre }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost !py-1.5 text-sm text-semantic-error">Eliminar</button>
                        </form>
                    @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-ink-muted">No hay roles registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@error('eliminar') <p class="error-texto mt-3">{{ $message }}</p> @enderror
@error('rol') <p class="error-texto mt-3">{{ $message }}</p> @enderror
<div class="mt-4">{{ $items->links() }}</div>
@endsection
