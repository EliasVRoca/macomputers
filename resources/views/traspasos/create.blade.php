@extends('layouts.app')

@section('titulo', 'Nuevo traspaso')
@section('encabezado', 'Nuevo traspaso')
@section('subtitulo', 'Agregue los productos y cantidades a trasladar.')
@section('contenido')
<form method="POST" action="{{ route('traspasos.store') }}" class="card max-w-4xl space-y-6">
    @csrf

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <label for="sucursal_origen_id" class="label">Sucursal de origen</label>
            <select id="sucursal_origen_id" name="sucursal_origen_id" class="input" {{ $puedeElegirOrigen ? 'required' : 'disabled' }}>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}" @selected(old('sucursal_origen_id', $sucursales->first()->id) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                @endforeach
            </select>
            @error('sucursal_origen_id') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="sucursal_destino_id" class="label">Sucursal de destino</label>
            <select id="sucursal_destino_id" name="sucursal_destino_id" class="input" required>
                <option value="">Seleccione…</option>
                @foreach (\App\Models\Sucursal::where('activa', true)->orderBy('nombre')->get() as $sucursal)
                    <option value="{{ $sucursal->id }}" @selected(old('sucursal_destino_id') == $sucursal->id)>{{ $sucursal->nombre }}</option>
                @endforeach
            </select>
            @error('sucursal_destino_id') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="fecha" class="label">Fecha</label>
            <input id="fecha" name="fecha" type="date" class="input" value="{{ old('fecha', now()->toDateString()) }}" required>
            @error('fecha') <p class="error-texto">{{ $message }}</p> @enderror
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
                        <option value="{{ $producto->id }}">{{ $producto->nombre }} ({{ $producto->codigo }})</option>
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
        <button type="submit" class="btn btn-primary">Guardar traspaso</button>
        <a href="{{ route('traspasos.index') }}" class="btn btn-secondary">Cancelar</a>
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
