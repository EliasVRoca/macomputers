@extends('layouts.app')

@section('titulo', $cliente->exists ? 'Editar cliente' : 'Nuevo cliente')
@section('encabezado', $cliente->exists ? 'Editar cliente' : 'Nuevo cliente')
@section('contenido')
<form method="POST" action="{{ $accion }}" class="card max-w-2xl space-y-5">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    <div>
        <label for="nombre" class="label">Nombre completo</label>
        <input id="nombre" name="nombre" class="input" value="{{ old('nombre', $cliente->nombre) }}" required maxlength="150">
        @error('nombre') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="telefono" class="label">Teléfono</label>
        <input id="telefono" name="telefono" class="input" value="{{ old('telefono', $cliente->telefono) }}" maxlength="30">
        @error('telefono') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="carnet" class="label">Carnet de identidad</label>
        <input id="carnet" name="carnet" class="input" value="{{ old('carnet', $cliente->carnet) }}" maxlength="30">
        @error('carnet') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('clientes.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
