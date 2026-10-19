@extends('layouts.app')

@section('titulo', $encargo->exists ? 'Registrar pago del encargo' : 'Nuevo encargo')
@section('encabezado', $encargo->exists ? 'Registrar pago del saldo' : 'Nuevo encargo')
@section('subtitulo', 'Productos no disponibles en stock, pedidos con anticipo.')
@section('acciones')
    <a href="{{ route('encargos.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<form method="POST" action="{{ $accion }}" class="card card-pad max-w-2xl space-y-8">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    @if ($encargo->exists)
        <section class="space-y-4">
            <h2 class="text-sm font-medium text-ink-muted">Detalle del encargo</h2>
            <dl class="grid gap-3 rounded-lg bg-surface-2 p-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-ink-muted">Cliente</dt>
                    <dd class="font-medium">{{ $encargo->cliente?->nombre }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Producto</dt>
                    <dd class="font-medium">{{ $encargo->producto?->nombre ?? $encargo->descripcion }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Total</dt>
                    <dd class="font-medium tabular-nums">Bs {{ number_format($encargo->total, 2, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Anticipo</dt>
                    <dd class="font-medium tabular-nums">Bs {{ number_format($encargo->anticipo, 2, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Saldo pendiente</dt>
                    <dd class="font-medium tabular-nums text-brand">Bs {{ number_format($encargo->saldo, 2, ',', '.') }}</dd>
                </div>
            </dl>
        </section>

        <section>
            <label for="abono" class="label">Monto del pago (Bs)</label>
            <input id="abono" name="abono" type="number" step="0.01" min="0.01" max="{{ $encargo->saldo }}" class="input" value="{{ old('abono', $encargo->saldo) }}" required aria-invalid="{{ $errors->has('abono') ? 'true' : 'false' }}">
            @error('abono') <p class="error-texto">{{ $message }}</p> @enderror
        </section>
    @else
        <section class="space-y-5">
            <h2 class="text-sm font-medium text-ink-muted">Cliente y sucursal</h2>
            <div>
                <label for="sucursal_id" class="label">Sucursal</label>
                <select id="sucursal_id" name="sucursal_id" class="input" {{ $puedeElegirSucursal ? 'required' : 'disabled' }}>
                    @foreach ($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $sucursales->first()->id) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-4 rounded-lg border border-hairline-soft p-4">
                <input type="hidden" name="modo_cliente" id="modo_cliente" value="{{ old('modo_cliente', 'existente') }}">
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="cliente-nuevo" class="h-4 w-4 accent-brand" @checked(old('modo_cliente') === 'nuevo')>
                    <label for="cliente-nuevo" class="text-sm text-ink-muted">Registrar un cliente nuevo</label>
                </div>
                <div id="bloque-cliente-existente">
                    <label for="cliente_id" class="label">Cliente</label>
                    <select id="cliente_id" name="cliente_id" class="input">
                        <option value="">Seleccione…</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->nombre }}</option>
                        @endforeach
                    </select>
                    @error('cliente_id') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
                <div id="bloque-cliente-nuevo" class="hidden grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-3">
                        <label for="cliente_nombre" class="label">Nombre completo</label>
                        <input id="cliente_nombre" name="cliente_nombre" class="input" value="{{ old('cliente_nombre') }}" maxlength="150">
                        @error('cliente_nombre') <p class="error-texto">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="cliente_telefono" class="label">Teléfono</label>
                        <input id="cliente_telefono" name="cliente_telefono" class="input" value="{{ old('cliente_telefono') }}" maxlength="30">
                    </div>
                    <div>
                        <label for="cliente_carnet" class="label">Carnet</label>
                        <input id="cliente_carnet" name="cliente_carnet" class="input" value="{{ old('cliente_carnet') }}" maxlength="30">
                        @error('cliente_carnet') <p class="error-texto">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="space-y-5 border-t border-hairline pt-8">
            <h2 class="text-sm font-medium text-ink-muted">Producto y anticipo</h2>
            <x-producto-picker
                label="Producto del catálogo"
                :value="old('producto_id')"
                :producto="$productoSeleccionado"
                :url="route('productos.opciones')"
                optional
            />
            <div>
                <label for="descripcion" class="label">Descripción <span class="font-normal text-ink-subtle">(si no está en el catálogo)</span></label>
                <input id="descripcion" name="descripcion" class="input" value="{{ old('descripcion', $encargo->descripcion) }}" maxlength="255" placeholder="Ej. Monitor Samsung 27 pulgadas">
                @error('descripcion') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="total" class="label">Total (Bs)</label>
                    <input id="total" name="total" type="number" step="0.01" min="0" class="input" value="{{ old('total', $encargo->total) }}" required>
                    @error('total') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="anticipo" class="label">Anticipo (Bs)</label>
                    <input id="anticipo" name="anticipo" type="number" step="0.01" min="0" class="input" value="{{ old('anticipo', $encargo->anticipo) }}" required>
                    @error('anticipo') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="saldo" class="label">Saldo a cancelar (Bs)</label>
                    <input id="saldo" type="number" class="input" value="0" readonly tabindex="-1" aria-readonly="true">
                    <p class="mt-1 text-xs text-ink-subtle">Se calcula: total − anticipo.</p>
                </div>
            </div>
            <div>
                <label for="fecha" class="label">Fecha</label>
                <input id="fecha" name="fecha" type="date" class="input" value="{{ old('fecha', $encargo->fecha?->toDateString()) }}" required>
                @error('fecha') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        </section>
    @endif

    <div class="flex gap-2 border-t border-hairline pt-6">
        <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar</button>
        <a href="{{ route('encargos.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

@if (! $encargo->exists)
@vite('resources/js/producto-picker.js')
<script>
    (function () {
        var nuevo = document.getElementById('cliente-nuevo');
        var modo = document.getElementById('modo_cliente');
        function alternar() {
            var esNuevo = nuevo.checked;
            modo.value = esNuevo ? 'nuevo' : 'existente';
            document.getElementById('bloque-cliente-existente').classList.toggle('hidden', esNuevo);
            var bloque = document.getElementById('bloque-cliente-nuevo');
            bloque.classList.toggle('hidden', !esNuevo);
            bloque.classList.toggle('grid', esNuevo);
        }
        nuevo.addEventListener('change', alternar);
        alternar();

        var total = document.getElementById('total');
        var anticipo = document.getElementById('anticipo');
        var saldo = document.getElementById('saldo');
        function recalcular() {
            var s = (parseFloat(total.value) || 0) - (parseFloat(anticipo.value) || 0);
            saldo.value = (s > 0 ? s : 0).toFixed(2);
        }
        total.addEventListener('input', recalcular);
        anticipo.addEventListener('input', recalcular);
        document.addEventListener('producto-seleccionado', function (event) {
            var producto = event.detail && event.detail.producto;
            if (producto && producto.precio_venta && (! total.value || parseFloat(total.value) === 0)) {
                total.value = Number(producto.precio_venta).toFixed(2);
            }
            recalcular();
        });
        recalcular();
    })();
</script>
@endif
@endsection
