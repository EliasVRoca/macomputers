@extends('layouts.app')

@section('titulo', $cliente->exists ? 'Editar cliente' : 'Nuevo cliente')
@section('encabezado', $cliente->exists ? 'Editar cliente' : 'Nuevo cliente')
@section('subtitulo', 'Datos del cliente para ventas, apartados, encargos y servicios.')
@section('acciones')
    <a href="{{ route('clientes.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<form method="POST" action="{{ $accion }}" class="card card-pad max-w-2xl space-y-6">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="nombre" class="label">Nombre completo</label>
            <input id="nombre" name="nombre" class="input" value="{{ old('nombre', $cliente->nombre) }}" required maxlength="150" placeholder="Ej. Juan Pérez" aria-invalid="{{ $errors->has('nombre') ? 'true' : 'false' }}">
            @error('nombre') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="telefono" class="label">Teléfono</label>
            <input id="telefono" name="telefono" class="input" value="{{ old('telefono', $cliente->telefono) }}" maxlength="30" placeholder="Ej. 70011223">
            @error('telefono') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="carnet" class="label">Carnet de identidad</label>
            <input id="carnet" name="carnet" class="input" value="{{ old('carnet', $cliente->carnet) }}" maxlength="30" placeholder="Ej. 7845123">
            @error('carnet') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex gap-2 border-t border-hairline pt-6">
        <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar</button>
        <a href="{{ route('clientes.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
