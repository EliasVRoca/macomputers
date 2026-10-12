@extends('layouts.app')

@section('titulo', $apartado->exists ? 'Registrar pago del apartado' : 'Nuevo apartado')
@section('encabezado', $apartado->exists ? 'Registrar pago del saldo' : 'Nuevo apartado')
@section('contenido')
<form method="POST" action="{{ $accion }}" class="card max-w-2xl space-y-5">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    @if ($apartado->exists)
        <div class="rounded-md bg-surface-2 px-4 py-3 text-sm">
            <p><span class="text-ink-muted">Cliente:</span> {{ $apartado->cliente?->nombre }}</p>
            <p><span class="text-ink-muted">Producto:</span> {{ $apartado->cantidad }} × {{ $apartado->producto?->nombre }}</p>
            <p><span class="text-ink-muted">Seña pagada:</span> Bs {{ number_format($apartado->sena, 2, ',', '.') }}</p>
            <p class="mt-1 font-medium">Saldo pendiente: Bs {{ number_format($apartado->saldo, 2, ',', '.') }}</p>
        </div>

        <div>
            <label for="abono" class="label">Monto del pago (Bs)</label>
            <input id="abono" name="abono" type="number" step="0.01" min="0.01" max="{{ $apartado->saldo }}" class="input" value="{{ old('abono', $apartado->saldo) }}" required>
            @error('abono') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
    @else
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="cliente_id" class="label">Cliente</label>
                <select id="cliente_id" name="cliente_id" class="input" required>
                    <option value="">Seleccione…</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->nombre }}</option>
                    @endforeach
                </select>
                @error('cliente_id') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="sucursal_id" class="label">Sucursal</label>
                <select id="sucursal_id" name="sucursal_id" class="input" {{ $puedeElegirSucursal ? 'required' : 'disabled' }}>
                    @foreach ($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $sucursales->first()->id) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label for="producto_id" class="label">Producto</label>
                <select id="producto_id" name="producto_id" class="input" required>
                    <option value="">Seleccione…</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}" @selected(old('producto_id') == $producto->id)>{{ $producto->nombre }} · Bs {{ number_format($producto->precio_venta, 2, ',', '.') }}</option>
                    @endforeach
                </select>
                @error('producto_id') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
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
    @endif

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('apartados.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
