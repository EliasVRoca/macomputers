@extends('layouts.app')

@section('titulo', $servicio->exists ? 'Actualizar servicio' : 'Nuevo servicio')
@section('encabezado', $servicio->exists ? 'Actualizar servicio' : 'Nuevo servicio técnico')
@section('contenido')
<form method="POST" action="{{ $accion }}" class="card max-w-2xl space-y-5">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    @if (! $servicio->exists)
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
    @else
        <p class="rounded-md bg-surface-2 px-3 py-2 text-sm">
            Cliente: <span class="font-medium">{{ $servicio->cliente?->nombre }}</span> · Sucursal: <span class="font-medium">{{ $servicio->sucursal?->nombre }}</span>
        </p>
    @endif

    <div>
        <label for="equipo" class="label">Equipo</label>
        <input id="equipo" name="equipo" class="input" value="{{ old('equipo', $servicio->equipo) }}" required maxlength="150" placeholder="Ej. Laptop Lenovo IdeaPad 3">
        @error('equipo') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="diagnostico" class="label">Diagnóstico</label>
        <textarea id="diagnostico" name="diagnostico" rows="3" class="input" maxlength="1000">{{ old('diagnostico', $servicio->diagnostico) }}</textarea>
        @error('diagnostico') <p class="error-texto">{{ $message }}</p> @enderror
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label for="costo" class="label">Costo (Bs)</label>
            <input id="costo" name="costo" type="number" step="0.01" min="0" class="input" value="{{ old('costo', $costo) }}" required>
            @error('costo') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="estado" class="label">Estado</label>
            <select id="estado" name="estado" class="input" required>
                @foreach (\App\Models\Servicio::ESTADOS as $clave => $texto)
                    <option value="{{ $clave }}" @selected(old('estado', $servicio->estado) === $clave)>{{ $texto }}</option>
                @endforeach
            </select>
            @error('estado') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="flex gap-2 pt-2">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <a href="{{ route('servicios.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
@endsection
