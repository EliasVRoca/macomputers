@extends('layouts.app')

@section('titulo', $proveedor->exists ? 'Editar proveedor' : 'Nuevo proveedor')
@section('encabezado', $proveedor->exists ? 'Editar proveedor' : 'Nuevo proveedor')
@section('contenido')
<form method="POST" action="{{ $accion }}" class="card max-w-2xl space-y-5">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    <div>
        <label for="nombre" class="label">Nombre</label>
        <input id="nombre" name="nombre" class="input" value="{{ old('nombre', $proveedor->nombre) }}" required maxlength="150">
        @error('nombre') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="contacto" class="label">Contacto</label>
        <input id="contacto" name="contacto" class="input" value="{{ old('contacto', $proveedor->contacto) }}" maxlength="150">
        @error('contacto') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="direccion" class="label">Dirección</label>
        <input id="direccion" name="direccion" class="input" value="{{ old('direccion', $proveedor->direccion) }}" maxlength="255">
        @error('direccion') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="pais_origen" class="label">País de origen</label>
        <input id="pais_origen" name="pais_origen" class="input" value="{{ old('pais_origen', $proveedor->pais_origen) }}" maxlength="100" placeholder="Estados Unidos, China...">
        @error('pais_origen') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('proveedores.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
