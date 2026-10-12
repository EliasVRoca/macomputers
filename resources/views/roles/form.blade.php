@extends('layouts.app')

@section('titulo', $rol->exists ? 'Editar rol' : 'Nuevo rol')
@section('encabezado', $rol->exists ? 'Editar rol' : 'Nuevo rol')
@section('contenido')
@php($esSistema = (bool) $rol->es_sistema)
<form method="POST" action="{{ $accion }}" class="card max-w-3xl space-y-6">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    @if ($esSistema)
        <p class="rounded-md bg-surface-2 px-3 py-2 text-sm text-ink-muted">Este es el rol de sistema: siempre tiene acceso a todos los módulos y no puede modificarse.</p>
    @endif

    <div>
        <label for="nombre" class="label">Nombre del rol</label>
        <input id="nombre" name="nombre" class="input" value="{{ old('nombre', $rol->nombre) }}" required maxlength="60" @disabled($esSistema)>
        @error('nombre') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="descripcion" class="label">Descripción</label>
        <input id="descripcion" name="descripcion" class="input" value="{{ old('descripcion', $rol->descripcion) }}" maxlength="255" @disabled($esSistema)>
        @error('descripcion') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <fieldset>
        <legend class="label">Módulos permitidos</legend>
        <div class="grid gap-2 sm:grid-cols-2">
            @foreach (config('permisos.modulos') as $clave => $modulo)
                <label class="flex items-center gap-2 rounded-md border border-hairline-soft px-3 py-2 text-sm">
                    <input type="checkbox" name="permisos[]" value="{{ $clave }}" class="h-4 w-4 accent-ink"
                           @checked($esSistema || in_array($clave, old('permisos', $rol->permisos ?? []), true))
                           @disabled($esSistema)>
                    {{ $modulo['label'] }}
                </label>
            @endforeach
        </div>
        @error('permisos') <p class="error-texto">{{ $message }}</p> @enderror
    </fieldset>

    <div class="flex gap-2 pt-2">
        @unless ($esSistema)
            <button type="submit" class="btn btn-primary">Guardar</button>
        @endunless
        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</form>
@endsection
