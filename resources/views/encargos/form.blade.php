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
                    <dd class="font-medium">{{ $encargo->descripcion }}</dd>
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
            </div>
        </section>

        <section class="space-y-5 border-t border-hairline pt-8">
            <h2 class="text-sm font-medium text-ink-muted">Producto y anticipo</h2>
            <div class="space-y-5">
                <div>
                    <label for="descripcion" class="label">Descripción del producto a conseguir</label>
                    <input id="descripcion" name="descripcion" class="input" value="{{ old('descripcion') }}" required maxlength="255" placeholder="Ej. Monitor Samsung 27 pulgadas">
                    @error('descripcion') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-5 sm:grid-cols-3">
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
            </div>
        </section>
    @endif

    <div class="flex gap-2 border-t border-hairline pt-6">
        <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar</button>
        <a href="{{ route('encargos.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
