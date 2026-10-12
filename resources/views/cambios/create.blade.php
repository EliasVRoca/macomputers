@extends('layouts.app')

@section('titulo', 'Nuevo cambio')
@section('encabezado', 'Nuevo cambio o devolución')
@section('subtitulo', 'Elija la venta; luego el producto con falla que pertenece a esa venta.')
@section('contenido')
<form method="POST" action="{{ route('cambios.store') }}" class="card max-w-3xl space-y-5">
    @csrf

    <div>
        <label for="venta_id" class="label">Venta</label>
        <select id="venta_id" name="venta_id" class="input" required>
            <option value="">Seleccione…</option>
            @foreach ($ventas as $venta)
                <option value="{{ $venta->id }}" @selected(old('venta_id') == $venta->id)>
                    {{ $venta->numero() }} · {{ $venta->fecha->format('d/m/Y') }} · Bs {{ number_format($venta->total, 2, ',', '.') }}
                </option>
            @endforeach
        </select>
        @error('venta_id') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="producto_id" class="label">Producto con falla</label>
        <select id="producto_id" name="producto_id" class="input" required>
            <option value="">Primero seleccione una venta…</option>
            @foreach ($ventas as $venta)
                @foreach ($venta->items->whereNotNull('producto_id') as $item)
                    <option value="{{ $item->producto_id }}" data-venta="{{ $venta->id }}" hidden>{{ $item->descripcion }}</option>
                @endforeach
            @endforeach
        </select>
        @error('producto_id') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <fieldset>
            <legend class="label">Tipo</legend>
            <label class="mr-4 inline-flex items-center gap-2 text-sm">
                <input type="radio" name="tipo" value="cambio" class="accent-ink" @checked(old('tipo', 'cambio') === 'cambio') required> Cambio por otro producto
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="radio" name="tipo" value="devolucion" class="accent-ink" @checked(old('tipo') === 'devolucion')> Devolución
            </label>
            @error('tipo') <p class="error-texto">{{ $message }}</p> @enderror
        </fieldset>

        <div>
            <label for="producto_reemplazo_id" class="label">Producto de reemplazo (solo cambio)</label>
            <select id="producto_reemplazo_id" name="producto_reemplazo_id" class="input">
                <option value="">Seleccione…</option>
                @foreach ($productos as $producto)
                    <option value="{{ $producto->id }}" @selected(old('producto_reemplazo_id') == $producto->id)>{{ $producto->nombre }}</option>
                @endforeach
            </select>
            @error('producto_reemplazo_id') <p class="error-texto">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="fecha" class="label">Fecha</label>
            <input id="fecha" name="fecha" type="date" class="input" value="{{ old('fecha', now()->toDateString()) }}" required>
            @error('fecha') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="motivo" class="label">Motivo (falla confirmada)</label>
        <textarea id="motivo" name="motivo" rows="3" class="input" required maxlength="500">{{ old('motivo') }}</textarea>
        @error('motivo') <p class="error-texto">{{ $message }}</p> @enderror
    </div>
    @error('stock') <p class="error-texto">{{ $message }}</p> @enderror

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('cambios.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<script>
    (function () {
        var venta = document.getElementById('venta_id');
        var producto = document.getElementById('producto_id');
        var opciones = Array.prototype.slice.call(producto.options).filter(function (o) { return o.dataset.venta; });

        function filtrar() {
            opciones.forEach(function (o) { o.hidden = o.dataset.venta !== venta.value; o.disabled = o.hidden; });
            producto.value = '';
        }

        venta.addEventListener('change', filtrar);
        filtrar();
    })();
</script>
@endsection
