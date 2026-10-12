@extends('layouts.app')

@section('titulo', 'Inventario · '.$sucursal->nombre)
@section('encabezado', $sucursal->nombre)
@section('subtitulo', $sucursal->direccion)
@section('acciones')
    <a href="{{ route('inventario.index') }}" class="btn btn-secondary">Volver al inventario</a>
@endsection

@section('contenido')
<div class="grid gap-6 lg:grid-cols-2">
    <section>
        <h2 class="mb-3 text-lg font-medium">Existencias</h2>
        <div class="card overflow-x-auto p-0">
            <table class="tabla w-full">
                <thead><tr><th>Producto</th><th class="text-right">Cantidad</th></tr></thead>
                <tbody>
                @forelse ($existencias as $fila)
                    <tr>
                        <td>{{ $fila->producto?->nombre }} <span class="block text-xs text-ink-muted">{{ $fila->producto?->codigo }}</span></td>
                        <td class="text-right font-medium {{ $fila->cantidad <= ($fila->producto?->stock_minimo ?? 0) ? 'text-semantic-error' : '' }}">{{ $fila->cantidad }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-center text-ink-muted">Sin existencias en esta sucursal.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $existencias->links() }}</div>
    </section>

    <section>
        <h2 class="mb-3 text-lg font-medium">Últimos movimientos</h2>
        <div class="card overflow-x-auto p-0">
            <table class="tabla w-full">
                <thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th class="text-right">Cant.</th></tr></thead>
                <tbody>
                @forelse ($kardex as $mov)
                    <tr>
                        <td class="whitespace-nowrap text-xs">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $mov->producto?->nombre }}</td>
                        <td><span class="badge">{{ str_replace('_', ' ', $mov->tipo) }}</span></td>
                        <td class="text-right">{{ $mov->cantidad > 0 ? '+' : '' }}{{ $mov->cantidad }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-ink-muted">Sin movimientos.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
