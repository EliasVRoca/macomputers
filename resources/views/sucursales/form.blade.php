@extends('layouts.app')

@section('titulo', $sucursal->exists ? 'Editar sucursal' : 'Nueva sucursal')
@section('encabezado', $sucursal->exists ? 'Editar sucursal' : 'Nueva sucursal')
@section('contenido')
<form method="POST" action="{{ $accion }}" class="card max-w-2xl space-y-5">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    <div>
        <label for="nombre" class="label">Nombre</label>
        <input id="nombre" name="nombre" class="input" value="{{ old('nombre', $sucursal->nombre) }}" required maxlength="100">
        @error('nombre') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="direccion" class="label">Dirección</label>
        <input id="direccion" name="direccion" class="input" value="{{ old('direccion', $sucursal->direccion) }}" required maxlength="255">
        @error('direccion') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="telefono" class="label">Teléfono (opcional)</label>
        <input id="telefono" name="telefono" class="input" value="{{ old('telefono', $sucursal->telefono) }}" maxlength="30">
        @error('telefono') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="activa" value="1" class="h-4 w-4 accent-ink" @checked(old('activa', $sucursal->activa))>
        Sucursal activa
    </label>

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('sucursales.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
