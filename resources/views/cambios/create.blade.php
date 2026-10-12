@extends('layouts.app')

@section('titulo', 'Nuevo cambio')
@section('encabezado', 'Nuevo cambio o devolución')
@section('subtitulo', 'Elija la venta y el producto con falla que pertenece a esa venta.')
@section('acciones')
    <a href="{{ route('cambios.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<form method="POST" action="{{ route('cambios.store') }}" class="card card-pad max-w-3xl space-y-8">
    @csrf

    <section class="space-y-5">
        <h2 class="text-sm font-medium text-ink-muted">Venta y producto</h2>
        <div class="space-y-5">
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
        </div>
    </section>

    <section class="space-y-5 border-t border-hairline pt-8">
        <h2 class="text-sm font-medium text-ink-muted">Tipo de solución</h2>
        <div class="grid gap-5 sm:grid-cols-2">
            <fieldset class="sm:col-span-2">
                <legend class="label">Tipo</legend>
                <div class="flex gap-6">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="tipo" value="cambio" class="h-4 w-4 accent-brand" @checked(old('tipo', 'cambio') === 'cambio') required>
                        Cambio por otro producto
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="tipo" value="devolucion" class="h-4 w-4 accent-brand" @checked(old('tipo') === 'devolucion')>
                        Devolución
                    </label>
                </div>
                @error('tipo') <p class="error-texto">{{ $message }}</p> @enderror
            </fieldset>

            <div>
                <label for="producto_reemplazo_id" class="label">Producto de reemplazo <span class="font-normal text-ink-subtle">(solo cambio)</span></label>
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
            <div class="sm:col-span-2">
                <label for="motivo" class="label">Motivo (falla confirmada)</label>
                <textarea id="motivo" name="motivo" rows="3" class="input" required maxlength="500" placeholder="Describa la falla que motiva el cambio o la devolución">{{ old('motivo') }}</textarea>
                @error('motivo') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <div class="flex gap-2 border-t border-hairline pt-6">
        <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar</button>
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
