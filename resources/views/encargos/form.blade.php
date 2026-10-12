@extends('layouts.app')

@section('titulo', $encargo->exists ? 'Registrar pago del encargo' : 'Nuevo encargo')
@section('encabezado', $encargo->exists ? 'Registrar pago del saldo' : 'Nuevo encargo')
@section('contenido')
<form method="POST" action="{{ $accion }}" class="card max-w-2xl space-y-5">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    @if ($encargo->exists)
        <div class="rounded-md bg-surface-2 px-4 py-3 text-sm">
            <p><span class="text-ink-muted">Cliente:</span> {{ $encargo->cliente?->nombre }}</p>
            <p><span class="text-ink-muted">Producto:</span> {{ $encargo->descripcion }}</p>
            <p><span class="text-ink-muted">Anticipo:</span> Bs {{ number_format($encargo->anticipo, 2, ',', '.') }}</p>
            <p class="mt-1 font-medium">Saldo pendiente: Bs {{ number_format($encargo->saldo, 2, ',', '.') }}</p>
        </div>

        <div>
            <label for="abono" class="label">Monto del pago (Bs)</label>
            <input id="abono" name="abono" type="number" step="0.01" min="0.01" max="{{ $encargo->saldo }}" class="input" value="{{ old('abono', $encargo->saldo) }}" required>
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
                <label for="descripcion" class="label">Descripción del producto a conseguir</label>
                <input id="descripcion" name="descripcion" class="input" value="{{ old('descripcion') }}" required maxlength="255">
                @error('descripcion') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="anticipo" class="label">Anticipo (Bs)</label>
                <input id="anticipo" name="anticipo" type="number" step="0.01" min="0" class="input" value="{{ old('anticipo', $encargo->anticipo) }}" required>
                @error('anticipo') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="saldo" class="label">Saldo a cancelar (Bs)</label>
                <input id="saldo" name="saldo" type="number" step="0.01" min="0" class="input" value="{{ old('saldo', $encargo->saldo) }}" required>
                @error('saldo') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="fecha" class="label">Fecha</label>
                <input id="fecha" name="fecha" type="date" class="input" value="{{ old('fecha', $encargo->fecha?->toDateString()) }}" required>
                @error('fecha') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        </div>
    @endif

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('encargos.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
