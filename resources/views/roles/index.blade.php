@extends('layouts.app')

@section('titulo', 'Roles')
@section('encabezado', 'Roles y permisos')
@section('subtitulo', 'Cada rol define a qué módulos puede acceder su personal.')
@section('acciones')
    @if (auth()->user()->esGerente())
        <a href="{{ route('roles.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo rol</a>
    @endif
@endsection

@section('contenido')
@error('eliminar') <p class="error-texto mb-4">{{ $message }}</p> @enderror
@error('rol') <p class="error-texto mb-4">{{ $message }}</p> @enderror

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Rol</th><th>Descripción</th><th>Módulos</th><th>Usuarios</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $rol)
            <tr>
                <td class="font-medium">
                    {{ $rol->nombre }}
                    @if ($rol->es_sistema)<span class="badge badge-brand ml-1">Sistema</span>@endif
                </td>
                <td class="text-ink-muted">{{ $rol->descripcion ?? '—' }}</td>
                <td>{{ $rol->es_sistema ? 'Todos' : count($rol->permisos ?? []) }}</td>
                <td>{{ $rol->users_count }}</td>
                <td>
                    @if (auth()->user()->esGerente())
                        <div class="flex justify-end gap-1">
                            <a href="{{ route('roles.edit', $rol) }}" class="btn btn-ghost btn-icon" title="{{ $rol->es_sistema ? 'Ver' : 'Editar' }}" aria-label="{{ $rol->es_sistema ? 'Ver' : 'Editar' }}">
                                <x-icon :name="$rol->es_sistema ? 'eye' : 'pencil'" class="size-4" />
                            </a>
                            @unless ($rol->es_sistema)
                                <form method="POST" action="{{ route('roles.destroy', $rol) }}" data-confirm="¿Eliminar el rol {{ $rol->nombre }}?">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Eliminar" aria-label="Eliminar"><x-icon name="trash" class="size-4" /></button>
                                </form>
                            @endunless
                        </div>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="key-round" class="size-5" /></span>
                    <div>
                        <p class="font-medium">Todavía no hay roles</p>
                        <p class="text-sm text-ink-muted">Cree un rol y asígnele los módulos permitidos.</p>
                    </div>
                    <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nuevo rol</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
