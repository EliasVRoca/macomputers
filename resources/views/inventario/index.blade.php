@extends('layouts.app')

@section('titulo', 'Inventario')
@section('encabezado', 'Inventario')
@section('subtitulo', 'Existencias por sucursal, movimientos y kardex.')
@section('acciones')
    @if ($puedeElegirSucursal && $sucursales->isNotEmpty())
        <a href="{{ route('inventario.sucursal', $sucursalFiltro ?? $sucursales->first()) }}" class="btn btn-secondary"><x-icon name="building" class="size-4" /> Ver sucursal</a>
    @endif
@endsection

@section('contenido')
<div class="grid gap-6 lg:grid-cols-3">
    {{-- Registrar movimiento --}}
    <form method="POST" action="{{ route('inventario.movimientos.store') }}" class="card card-pad h-fit space-y-5 lg:col-span-1">
        @csrf
        <h2 class="text-sm font-medium text-ink-muted">Registrar movimiento</h2>

        <div>
            <label for="producto_id" class="label">Producto</label>
            <select id="producto_id" name="producto_id" class="input" required>
                <option value="">Seleccione…</option>
                @foreach ($productos as $producto)
                    <option value="{{ $producto->id }}" @selected(old('producto_id') == $producto->id)>{{ $producto->nombre }} ({{ $producto->codigo }})</option>
                @endforeach
            </select>
            @error('producto_id') <p class="error-texto">{{ $message }}</p> @enderror
        </div>

        @if ($puedeElegirSucursal)
            <div>
                <label for="sucursal_id" class="label">Sucursal</label>
                <select id="sucursal_id" name="sucursal_id" class="input" required>
                    <option value="">Seleccione…</option>
                    @foreach ($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $sucursalFiltro) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                    @endforeach
                </select>
                @error('sucursal_id') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        @endif

        <div>
            <label for="tipo" class="label">Tipo</label>
            <select id="tipo" name="tipo" class="input" required>
                <option value="entrada" @selected(old('tipo') === 'entrada')>Entrada (suma stock)</option>
                <option value="salida" @selected(old('tipo') === 'salida')>Salida (resta stock)</option>
                <option value="ajuste" @selected(old('tipo') === 'ajuste')>Ajuste (cantidad final)</option>
            </select>
        </div>

        <div>
            <label for="cantidad" class="label">Cantidad</label>
            <input id="cantidad" name="cantidad" type="number" min="0" class="input" value="{{ old('cantidad') }}" required aria-invalid="{{ $errors->has('cantidad') ? 'true' : 'false' }}">
            @error('cantidad') <p class="error-texto">{{ $message }}</p> @enderror
            @error('stock') <p class="error-texto">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="referencia" class="label">Motivo o referencia <span class="font-normal text-ink-subtle">(opcional)</span></label>
            <input id="referencia" name="referencia" class="input" value="{{ old('referencia') }}" maxlength="150">
        </div>

        <button type="submit" class="btn btn-primary w-full"><x-icon name="check" class="size-4" /> Guardar movimiento</button>
    </form>

    <div class="space-y-6 lg:col-span-2">
        {{-- Existencias --}}
        <section>
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-sm font-medium text-ink-muted">Existencias</h2>
                @if ($puedeElegirSucursal)
                    <form method="GET" action="{{ route('inventario.index') }}">
                        <select name="sucursal_id" class="input !w-auto" aria-label="Filtrar por sucursal" onchange="this.form.submit()">
                            <option value="">Todas las sucursales</option>
                            @foreach ($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}" @selected($sucursalFiltro == $sucursal->id)>{{ $sucursal->nombre }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>
            <div class="card card-flush overflow-x-auto">
                <table class="tabla">
                    <thead><tr><th>Producto</th><th>Sucursal</th><th class="text-right">Cantidad</th></tr></thead>
                    <tbody>
                    @forelse ($existencias as $fila)
                        <tr>
                            <td>{{ $fila->producto?->nombre }}</td>
                            <td class="text-ink-muted">{{ $fila->sucursal?->nombre }}</td>
                            <td class="text-right">
                                @if ($fila->cantidad <= ($fila->producto?->stock_minimo ?? 0))
                                    <span class="badge badge-danger">{{ $fila->cantidad }} · bajo mínimo</span>
                                @else
                                    <span class="font-medium tabular-nums">{{ $fila->cantidad }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><div class="empty py-10"><p class="text-sm text-ink-muted">Sin existencias registradas.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $existencias->links() }}</div>
        </section>

        {{-- Kardex --}}
        <section>
            <h2 class="mb-3 text-sm font-medium text-ink-muted">Kardex (movimientos)</h2>
            <div class="card card-flush overflow-x-auto">
                <table class="tabla">
                    <thead><tr><th>Fecha</th><th>Producto</th><th>Sucursal</th><th>Tipo</th><th class="text-right">Cant.</th><th class="text-right">Saldo</th><th>Usuario</th></tr></thead>
                    <tbody>
                    @forelse ($kardex as $mov)
                        <tr>
                            <td class="whitespace-nowrap text-xs text-ink-muted">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $mov->producto?->nombre }}</td>
                            <td class="text-ink-muted">{{ $mov->sucursal?->nombre }}</td>
                            <td><span class="badge badge-neutral">{{ str_replace('_', ' ', $mov->tipo) }}</span></td>
                            <td class="text-right tabular-nums {{ $mov->cantidad < 0 ? 'text-danger' : 'text-ok' }}">{{ $mov->cantidad > 0 ? '+' : '' }}{{ $mov->cantidad }}</td>
                            <td class="text-right tabular-nums">{{ $mov->saldo }}</td>
                            <td class="text-ink-muted">{{ $mov->usuario?->username ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty py-10"><p class="text-sm text-ink-muted">Sin movimientos.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $kardex->links() }}</div>
        </section>
    </div>
</div>
@endsection
