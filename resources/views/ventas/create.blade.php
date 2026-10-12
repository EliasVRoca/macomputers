@extends('layouts.app')

@section('titulo', 'Nueva venta')
@section('encabezado', 'Nueva venta')
@section('subtitulo', 'El precio se toma del catálogo. El stock se descuenta al guardar.')
@section('contenido')
<form method="POST" action="{{ route('ventas.store') }}" class="card max-w-4xl space-y-6">
    @csrf

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label for="sucursal_id" class="label">Sucursal</label>
            <select id="sucursal_id" name="sucursal_id" class="input" {{ $puedeElegirSucursal ? 'required' : 'disabled' }}>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $sucursales->first()->id) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                @endforeach
            </select>
            @error('sucursal_id') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="cliente_id" class="label">Cliente (opcional)</label>
            <select id="cliente_id" name="cliente_id" class="input">
                <option value="">Sin cliente registrado</option>
                @foreach ($clientes as $cliente)
                    <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->nombre }}</option>
                @endforeach
            </select>
            @error('cliente_id') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="medio_pago" class="label">Medio de pago</label>
            <select id="medio_pago" name="medio_pago" class="input" required>
                @foreach ($medios as $medio)
                    <option value="{{ $medio }}" @selected(old('medio_pago') === $medio)>{{ $medio }}</option>
                @endforeach
            </select>
            @error('medio_pago') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <div class="mb-2 flex items-center justify-between">
            <h2 class="text-sm font-medium">Productos</h2>
            <button type="button" id="agregar-fila" class="btn btn-secondary !py-1.5 text-sm">Agregar producto</button>
        </div>
        <div id="filas" class="space-y-3"></div>
        @error('items') <p class="error-texto mt-2">{{ $message }}</p> @enderror
        @error('stock') <p class="error-texto mt-2">{{ $message }}</p> @enderror
    </div>

    <template id="fila-plantilla">
        <div class="fila grid items-end gap-3 sm:grid-cols-12">
            <div class="sm:col-span-8">
                <label class="label">Producto</label>
                <select name="items[__IDX__][producto_id]" class="input" required>
                    <option value="">Seleccione…</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}">{{ $producto->nombre }} · Bs {{ number_format($producto->precio_venta, 2, ',', '.') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-3">
                <label class="label">Cantidad</label>
                <input name="items[__IDX__][cantidad]" type="number" min="1" value="1" class="input" required>
            </div>
            <div class="sm:col-span-1">
                <button type="button" data-quitar class="btn btn-ghost w-full !px-2 text-semantic-error" aria-label="Quitar producto">✕</button>
            </div>
        </div>
    </template>

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar y emitir nota</button>
        <a href="{{ route('ventas.index') }}" class="btn btn-secondary">Cancelar</a>
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
            if (e.target.matches('[data-quitar]')) {
                e.target.closest('.fila').remove();
            }
        });
        agregar();
    })();
</script>
@endsection
