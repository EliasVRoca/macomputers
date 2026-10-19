@extends('layouts.app')

@section('titulo', $producto->exists ? 'Editar producto' : 'Nuevo producto')
@section('encabezado', $producto->exists ? 'Editar producto' : 'Nuevo producto')
@section('subtitulo', 'Equipos, accesorios y repuestos que comercializa la empresa.')
@section('acciones')
    <a href="{{ route('productos.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<form method="POST" action="{{ $accion }}" class="card card-pad max-w-3xl space-y-8">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    <section class="space-y-5">
        <h2 class="text-sm font-medium text-ink-muted">Identificación</h2>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="codigo" class="label">Código</label>
                <input id="codigo" name="codigo" class="input font-mono" value="{{ old('codigo', $producto->codigo) }}" required maxlength="50" placeholder="Ej. LAP-001" aria-invalid="{{ $errors->has('codigo') ? 'true' : 'false' }}">
                @error('codigo') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="categoria_id" class="label">Categoría</label>
                <select id="categoria_id" name="categoria_id" class="input" required aria-invalid="{{ $errors->has('categoria_id') ? 'true' : 'false' }}">
                    <option value="">Seleccione…</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected(old('categoria_id', $producto->categoria_id) == $categoria->id)>{{ $categoria->nombre }}</option>
                    @endforeach
                </select>
                @error('categoria_id') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="nombre" class="label">Nombre</label>
                <input id="nombre" name="nombre" class="input" value="{{ old('nombre', $producto->nombre) }}" required maxlength="150" placeholder="Ej. Laptop Lenovo IdeaPad 3">
                @error('nombre') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="marca" class="label">Marca</label>
                <input id="marca" name="marca" class="input" value="{{ old('marca', $producto->marca) }}" required maxlength="100" placeholder="Ej. Lenovo">
                @error('marca') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="modelo" class="label">Modelo <span class="font-normal text-ink-subtle">(opcional)</span></label>
                <input id="modelo" name="modelo" class="input" value="{{ old('modelo', $producto->modelo) }}" maxlength="100" placeholder="Ej. 15IAU7">
                @error('modelo') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="condicion" class="label">Condición</label>
                <select id="condicion" name="condicion" class="input" aria-invalid="{{ $errors->has('condicion') ? 'true' : 'false' }}">
                    @foreach ($condiciones as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(old('condicion', $producto->condicion?->value ?? 'nuevo') === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                @error('condicion') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="space-y-5 border-t border-hairline pt-8">
        <h2 class="text-sm font-medium text-ink-muted">Precios y stock</h2>
        <div class="grid gap-5 sm:grid-cols-3">
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
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="activo" value="1" class="h-4 w-4 rounded border-hairline accent-brand" @checked(old('activo', $producto->activo))>
            Producto activo <span class="text-ink-subtle">(disponible para la venta)</span>
        </label>
    </section>

    <div class="flex gap-2 border-t border-hairline pt-6">
        <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar</button>
        <a href="{{ route('productos.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
