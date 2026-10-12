@extends('layouts.app')

@section('titulo', $categoria->exists ? 'Editar categoría' : 'Nueva categoría')
@section('encabezado', $categoria->exists ? 'Editar categoría' : 'Nueva categoría')
@section('contenido')
<form method="POST" action="{{ $accion }}" class="card max-w-2xl space-y-5">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    <div>
        <label for="nombre" class="label">Nombre</label>
        <input id="nombre" name="nombre" class="input" value="{{ old('nombre', $categoria->nombre) }}" required maxlength="100">
        @error('nombre') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="descripcion" class="label">Descripción (opcional)</label>
        <input id="descripcion" name="descripcion" class="input" value="{{ old('descripcion', $categoria->descripcion) }}" maxlength="255">
        @error('descripcion') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('categorias.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
