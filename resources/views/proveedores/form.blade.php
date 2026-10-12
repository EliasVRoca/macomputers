@extends('layouts.app')

@section('titulo', $proveedor->exists ? 'Editar proveedor' : 'Nuevo proveedor')
@section('encabezado', $proveedor->exists ? 'Editar proveedor' : 'Nuevo proveedor')
@section('subtitulo', 'Empresas que abastecen de mercadería a M&A Computers.')
@section('acciones')
    <a href="{{ route('proveedores.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<form method="POST" action="{{ $accion }}" class="card card-pad max-w-2xl space-y-6">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    <div class="grid gap-5 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <label for="nombre" class="label">Nombre</label>
            <input id="nombre" name="nombre" class="input" value="{{ old('nombre', $proveedor->nombre) }}" required maxlength="150" placeholder="Ej. Importadora XYZ" aria-invalid="{{ $errors->has('nombre') ? 'true' : 'false' }}">
            @error('nombre') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="contacto" class="label">Contacto</label>
            <input id="contacto" name="contacto" class="input" value="{{ old('contacto', $proveedor->contacto) }}" maxlength="150" placeholder="Nombre o teléfono">
            @error('contacto') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="pais_origen" class="label">País de origen</label>
            <input id="pais_origen" name="pais_origen" class="input" value="{{ old('pais_origen', $proveedor->pais_origen) }}" maxlength="100" placeholder="Estados Unidos, China…">
            @error('pais_origen') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label for="direccion" class="label">Dirección</label>
            <input id="direccion" name="direccion" class="input" value="{{ old('direccion', $proveedor->direccion) }}" maxlength="255">
            @error('direccion') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex gap-2 border-t border-hairline pt-6">
        <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar</button>
        <a href="{{ route('proveedores.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
