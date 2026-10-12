@extends('layouts.app')

@section('titulo', $categoria->exists ? 'Editar categoría' : 'Nueva categoría')
@section('encabezado', $categoria->exists ? 'Editar categoría' : 'Nueva categoría')
@section('subtitulo', 'Agrupa los productos del catálogo.')
@section('acciones')
    <a href="{{ route('categorias.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<form method="POST" action="{{ $accion }}" class="card card-pad max-w-2xl space-y-6">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    <div class="space-y-5">
        <div>
            <label for="nombre" class="label">Nombre</label>
            <input id="nombre" name="nombre" class="input" value="{{ old('nombre', $categoria->nombre) }}" required maxlength="100" placeholder="Ej. Laptops" aria-invalid="{{ $errors->has('nombre') ? 'true' : 'false' }}">
            @error('nombre') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="descripcion" class="label">Descripción <span class="font-normal text-ink-subtle">(opcional)</span></label>
            <input id="descripcion" name="descripcion" class="input" value="{{ old('descripcion', $categoria->descripcion) }}" maxlength="255">
            @error('descripcion') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex gap-2 border-t border-hairline pt-6">
        <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar</button>
        <a href="{{ route('categorias.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
