@extends('layouts.app')

@section('titulo', 'Inventario · '.$sucursal->nombre)
@section('encabezado', $sucursal->nombre)
@section('subtitulo', $sucursal->direccion)
@section('acciones')
    <a href="{{ route('inventario.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver al inventario</a>
@endsection

@section('contenido')
<div class="grid gap-6 lg:grid-cols-2">
    <section>
        <h2 class="mb-3 text-sm font-medium text-ink-muted">Existencias</h2>
        <div class="card card-flush overflow-x-auto">
            <table class="tabla">
                <thead><tr><th>Producto</th><th class="text-right">Cantidad</th></tr></thead>
                <tbody>
                @forelse ($existencias as $fila)
                    <tr>
                        <td>
                            {{ $fila->producto?->nombre }}
                            <span class="block text-xs text-ink-muted">{{ $fila->producto?->codigo }}</span>
                        </td>
                        <td class="text-right">
                            @if ($fila->cantidad <= ($fila->producto?->stock_minimo ?? 0))
                                <span class="badge badge-danger">{{ $fila->cantidad }} · bajo mínimo</span>
                            @else
                                <span class="font-medium tabular-nums">{{ $fila->cantidad }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="2"><div class="empty py-10"><p class="text-sm text-ink-muted">Sin existencias en esta sucursal.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $existencias->links() }}</div>
    </section>

    <section>
        <h2 class="mb-3 text-sm font-medium text-ink-muted">Últimos movimientos</h2>
        <div class="card card-flush overflow-x-auto">
            <table class="tabla">
                <thead><tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th class="text-right">Cant.</th></tr></thead>
                <tbody>
                @forelse ($kardex as $mov)
                    <tr>
                        <td class="whitespace-nowrap text-xs text-ink-muted">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $mov->producto?->nombre }}</td>
                        <td><span class="badge badge-neutral">{{ str_replace('_', ' ', $mov->tipo) }}</span></td>
                        <td class="text-right tabular-nums {{ $mov->cantidad < 0 ? 'text-danger' : 'text-ok' }}">{{ $mov->cantidad > 0 ? '+' : '' }}{{ $mov->cantidad }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4"><div class="empty py-10"><p class="text-sm text-ink-muted">Sin movimientos.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
