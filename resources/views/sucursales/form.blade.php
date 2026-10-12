@extends('layouts.app')

@section('titulo', $sucursal->exists ? 'Editar sucursal' : 'Nueva sucursal')
@section('encabezado', $sucursal->exists ? 'Editar sucursal' : 'Nueva sucursal')
@section('subtitulo', 'Tiendas de M&A Computers donde opera el sistema.')
@section('acciones')
    <a href="{{ route('sucursales.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<form method="POST" action="{{ $accion }}" class="card card-pad max-w-2xl space-y-6">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="nombre" class="label">Nombre</label>
            <input id="nombre" name="nombre" class="input" value="{{ old('nombre', $sucursal->nombre) }}" required maxlength="100" placeholder="Ej. Sucursal principal" aria-invalid="{{ $errors->has('nombre') ? 'true' : 'false' }}">
            @error('nombre') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label for="direccion" class="label">Dirección</label>
            <input id="direccion" name="direccion" class="input" value="{{ old('direccion', $sucursal->direccion) }}" required maxlength="255" placeholder="Comercial Chiriguano, pasillo, local…" aria-invalid="{{ $errors->has('direccion') ? 'true' : 'false' }}">
            @error('direccion') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="telefono" class="label">Teléfono</label>
            <input id="telefono" name="telefono" class="input" value="{{ old('telefono', $sucursal->telefono) }}" maxlength="30">
            @error('telefono') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 self-end pb-2.5 text-sm">
            <input type="checkbox" name="activa" value="1" class="h-4 w-4 rounded border-hairline accent-brand" @checked(old('activa', $sucursal->activa))>
            Sucursal activa
        </label>
    </div>

    <div class="flex gap-2 border-t border-hairline pt-6">
        <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar</button>
        <a href="{{ route('sucursales.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
