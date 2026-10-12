@extends('layouts.app')

@section('titulo', $producto->exists ? 'Editar producto' : 'Nuevo producto')
@section('encabezado', $producto->exists ? 'Editar producto' : 'Nuevo producto')
@section('contenido')
<form method="POST" action="{{ $accion }}" class="card max-w-3xl space-y-5">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="codigo" class="label">Código</label>
            <input id="codigo" name="codigo" class="input font-mono" value="{{ old('codigo', $producto->codigo) }}" required maxlength="50">
            @error('codigo') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="categoria_id" class="label">Categoría</label>
            <select id="categoria_id" name="categoria_id" class="input" required>
                <option value="">Seleccione…</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected(old('categoria_id', $producto->categoria_id) == $categoria->id)>{{ $categoria->nombre }}</option>
                @endforeach
            </select>
            @error('categoria_id') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div class="sm:col-span-2">
            <label for="nombre" class="label">Nombre</label>
            <input id="nombre" name="nombre" class="input" value="{{ old('nombre', $producto->nombre) }}" required maxlength="150">
            @error('nombre') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="marca" class="label">Marca</label>
            <input id="marca" name="marca" class="input" value="{{ old('marca', $producto->marca) }}" required maxlength="100">
            @error('marca') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="modelo" class="label">Modelo (opcional)</label>
            <input id="modelo" name="modelo" class="input" value="{{ old('modelo', $producto->modelo) }}" maxlength="100">
            @error('modelo') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="precio_compra" class="label">Precio de compra (Bs)</label>
            <input id="precio_compra" name="precio_compra" type="number" step="0.01" min="0" class="input" value="{{ old('precio_compra', $producto->precio_compra) }}" required>
            @error('precio_compra') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="precio_venta" class="label">Precio de venta (Bs)</label>
            <input id="precio_venta" name="precio_venta" type="number" step="0.01" min="0" class="input" value="{{ old('precio_venta', $producto->precio_venta) }}" required>
            @error('precio_venta') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="stock_minimo" class="label">Stock mínimo</label>
            <input id="stock_minimo" name="stock_minimo" type="number" min="0" class="input" value="{{ old('stock_minimo', $producto->stock_minimo) }}" required>
            @error('stock_minimo') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 self-end text-sm">
            <input type="checkbox" name="activo" value="1" class="h-4 w-4 accent-ink" @checked(old('activo', $producto->activo))>
            Producto activo
        </label>
    </div>

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('productos.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
