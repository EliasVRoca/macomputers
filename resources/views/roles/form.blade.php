@extends('layouts.app')

@section('titulo', $rol->exists ? 'Editar rol' : 'Nuevo rol')
@section('encabezado', $rol->exists ? 'Editar rol' : 'Nuevo rol')
@section('subtitulo', 'Defina a qué módulos puede acceder el personal con este rol.')
@section('acciones')
    <a href="{{ route('roles.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
@php($esSistema = (bool) $rol->es_sistema)
<form method="POST" action="{{ $accion }}" class="card card-pad max-w-3xl space-y-8">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    @if ($esSistema)
        <p class="rounded-md bg-surface-2 px-3 py-2 text-sm text-ink-muted">Este es el rol de sistema: siempre tiene acceso a todos los módulos y no puede modificarse.</p>
    @endif

    <section class="space-y-5">
        <h2 class="text-sm font-medium text-ink-muted">Datos del rol</h2>
        <div class="grid gap-5 sm:grid-cols-2">
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
        </div>
    </section>

    <section class="space-y-5 border-t border-hairline pt-8">
        <h2 class="text-sm font-medium text-ink-muted">Módulos permitidos</h2>
        @error('permisos') <p class="error-texto">{{ $message }}</p> @enderror

        <div class="space-y-4">
            @foreach (config('permisos.grupos') as $grupo => $datos)
                <div>
                    <p class="mb-2 flex items-center gap-2 text-xs font-medium tracking-wide text-ink-subtle uppercase">
                        <x-icon :name="$datos['icon']" class="size-4" /> {{ $grupo }}
                    </p>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($datos['modulos'] as $clave)
                            @php($modulo = config("permisos.modulos.{$clave}"))
                            <label class="flex cursor-pointer items-center gap-2 rounded-md border border-hairline-soft px-3 py-2 text-sm transition-colors hover:bg-surface-2">
                                <input type="checkbox" name="permisos[]" value="{{ $clave }}" class="h-4 w-4 rounded border-hairline accent-brand"
                                       @checked($esSistema || in_array($clave, old('permisos', $rol->permisos ?? []), true))
                                       @disabled($esSistema)>
                                <x-icon :name="$modulo['icon']" class="size-4 text-ink-muted" />
                                {{ $modulo['label'] }}
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="flex gap-2 border-t border-hairline pt-6">
        @unless ($esSistema)
            <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar</button>
        @endunless
        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Volver</a>
    </div>
</form>
@endsection
