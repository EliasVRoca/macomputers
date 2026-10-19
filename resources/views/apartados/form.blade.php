@extends('layouts.app')

@section('titulo', $apartado->exists ? 'Registrar pago del apartado' : 'Nuevo apartado')
@section('encabezado', $apartado->exists ? 'Registrar pago del saldo' : 'Nuevo apartado')
@section('acciones')
    <a href="{{ route('apartados.index') }}" class="btn btn-secondary">
        <x-icon name="chevron-left" class="size-4" /> Volver
    </a>
@endsection

@section('contenido')
<form method="POST" action="{{ $accion }}" class="card card-pad max-w-2xl space-y-6">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    @if ($apartado->exists)
        <section class="space-y-4">
            <h2 class="text-sm font-medium text-ink-muted">Detalle del apartado</h2>
            <dl class="grid gap-3 rounded-lg bg-surface-2 p-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-ink-muted">Cliente</dt>
                    <dd class="font-medium">{{ $apartado->cliente?->nombre }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Producto</dt>
                    <dd class="font-medium">{{ $apartado->cantidad }} × {{ $apartado->producto?->nombre }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Seña pagada</dt>
                    <dd class="font-medium tabular-nums">Bs {{ number_format($apartado->sena, 2, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-ink-muted">Saldo pendiente</dt>
                    <dd class="font-medium tabular-nums text-brand">Bs {{ number_format($apartado->saldo, 2, ',', '.') }}</dd>
                </div>
            </dl>
        </section>

        <section class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="abono" class="label">Monto del pago (Bs)</label>
                <input id="abono" name="abono" type="number" step="0.01" min="0.01" max="{{ $apartado->saldo }}" class="input" value="{{ old('abono', $apartado->saldo) }}" required aria-invalid="{{ $errors->has('abono') ? 'true' : 'false' }}">
                @error('abono') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="medio_pago" class="label">Medio de pago</label>
                <select id="medio_pago" name="medio_pago" class="input">
                    @foreach (($medios ?? []) as $medio)
                        <option value="{{ $medio }}" @selected(old('medio_pago') === $medio)>{{ $medio }}</option>
                    @endforeach
                </select>
                @error('medio_pago') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        </section>
        <p class="text-xs text-ink-subtle">Al completar el saldo se genera la nota de venta del apartado.</p>
    @else
        <section class="space-y-4">
            <h2 class="text-sm font-medium text-ink-muted">Datos del apartado</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="sucursal_id" class="label">Sucursal</label>
                    <select id="sucursal_id" name="sucursal_id" class="input" {{ $puedeElegirSucursal ? 'required' : 'disabled' }}>
                        @foreach ($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $sucursales->first()->id) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                        @endforeach
                    </select>
                </div>
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

            <div>
                <label for="producto_id" class="label">Producto</label>
                <select id="producto_id" name="producto_id" class="input" required>
                    <option value="">Seleccione…</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}" @selected(old('producto_id') == $producto->id)>{{ $producto->nombre }} · Bs {{ number_format($producto->precio_venta, 2, ',', '.') }}</option>
                    @endforeach
                </select>
                @error('producto_id') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="text-sm font-medium text-ink-muted">Reserva y pago</h2>
            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <label for="cantidad" class="label">Cantidad</label>
                    <input id="cantidad" name="cantidad" type="number" min="1" class="input" value="{{ old('cantidad', $apartado->cantidad) }}" required>
                    @error('cantidad') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sena" class="label">Seña (Bs)</label>
                    <input id="sena" name="sena" type="number" step="0.01" min="0" class="input" value="{{ old('sena', $apartado->sena) }}" required>
                    @error('sena') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="fecha" class="label">Fecha</label>
                    <input id="fecha" name="fecha" type="date" class="input" value="{{ old('fecha', $apartado->fecha?->toDateString()) }}" required>
                    @error('fecha') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
            </div>
            <p class="text-xs text-ink-subtle">La seña no puede superar el valor total. El producto queda reservado y sale del stock disponible. Si la seña cubre el total, el apartado se completa y se emite la nota.</p>
        </section>
    @endif

    <div class="flex gap-2 border-t border-hairline pt-6">
        <button type="submit" class="btn btn-primary">
            <x-icon name="check" class="size-4" /> Guardar
        </button>
        <a href="{{ route('apartados.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

@if (! $apartado->exists)
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
    })();
</script>
@endif
@endsection
