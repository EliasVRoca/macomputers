@extends('layouts.app')

@section('titulo', 'Nueva compra')
@section('encabezado', 'Nueva compra')
@section('subtitulo', 'Registre el ingreso de mercadería recibida del proveedor. Suma al stock.')
@section('acciones')
    <a href="{{ route('compras.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<form method="POST" action="{{ route('compras.store') }}" class="card card-pad max-w-4xl space-y-8">
    @csrf

    <section class="space-y-5">
        <h2 class="text-sm font-medium text-ink-muted">Proveedor y destino</h2>
        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <label for="proveedor_id" class="label">Proveedor</label>
                <select id="proveedor_id" name="proveedor_id" class="input" required>
                    <option value="">Seleccione…</option>
                    @foreach ($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}" @selected(old('proveedor_id') == $proveedor->id)>{{ $proveedor->nombre }}</option>
                    @endforeach
                </select>
                @error('proveedor_id') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="sucursal_id" class="label">Sucursal que recibe</label>
                <select id="sucursal_id" name="sucursal_id" class="input" {{ $puedeElegirSucursal ? 'required' : 'disabled' }}>
                    @foreach ($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $sucursales->first()->id) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                    @endforeach
                </select>
                @error('sucursal_id') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="fecha" class="label">Fecha</label>
                <input id="fecha" name="fecha" type="date" class="input" value="{{ old('fecha', now()->toDateString()) }}" required>
                @error('fecha') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="space-y-4 border-t border-hairline pt-8">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-medium text-ink-muted">Productos recibidos</h2>
            <button type="button" id="agregar-fila" class="btn btn-secondary btn-sm"><x-icon name="plus" class="size-4" /> Agregar producto</button>
        </div>
        <div id="filas" class="space-y-3"></div>
        @error('items') <p class="error-texto">{{ $message }}</p> @enderror
        @error('stock') <p class="error-texto">{{ $message }}</p> @enderror
    </section>

    <template id="fila-plantilla">
        <div class="fila grid items-end gap-3 rounded-lg border border-hairline-soft p-3 sm:grid-cols-12">
            <div class="sm:col-span-6">
                <label class="label">Producto</label>
                <select name="items[__IDX__][producto_id]" class="input" required>
                    <option value="">Seleccione…</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}">{{ $producto->nombre }} ({{ $producto->codigo }})</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="label">Cantidad</label>
                <input name="items[__IDX__][cantidad]" type="number" min="1" value="1" class="input" required>
            </div>
            <div class="sm:col-span-3">
                <label class="label">Precio compra (Bs)</label>
                <input name="items[__IDX__][precio_compra]" type="number" min="0" step="0.01" value="0" class="input" required>
            </div>
            <div class="sm:col-span-1">
                <button type="button" data-quitar class="btn btn-ghost btn-icon w-full text-danger" aria-label="Quitar producto"><x-icon name="trash" class="size-4" /></button>
            </div>
        </div>
    </template>

    <div class="flex gap-2 border-t border-hairline pt-6">
        <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar compra</button>
        <a href="{{ route('compras.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<script>
    (function () {
        var contenedor = document.getElementById('filas');
        var plantilla = document.getElementById('fila-plantilla');
        var indice = 0;

        function agregar() {
            contenedor.insertAdjacentHTML('beforeend', plantilla.innerHTML.replaceAll('__IDX__', indice++));
        }

        document.getElementById('agregar-fila').addEventListener('click', agregar);
        contenedor.addEventListener('click', function (e) {
            if (e.target.closest('[data-quitar]')) {
                e.target.closest('.fila').remove();
            }
        });
        agregar();
    })();
</script>
@endsection
