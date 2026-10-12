@extends('layouts.app')

@section('titulo', 'Encargos')
@section('encabezado', 'Encargos')
@section('subtitulo', 'Productos no disponibles en stock, pedidos con anticipo.')
@section('acciones')
    <a href="{{ route('encargos.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo encargo</a>
@endsection

@section('contenido')
@error('encargo') <p class="error-texto mb-4">{{ $message }}</p> @enderror

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Cliente</th><th>Descripción</th><th>Sucursal</th><th class="text-right">Anticipo</th><th class="text-right">Saldo</th><th>Estado</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $encargo)
            <tr>
                <td class="font-medium">{{ $encargo->cliente?->nombre }}</td>
                <td>{{ $encargo->descripcion }}</td>
                <td class="text-ink-muted">{{ $encargo->sucursal?->nombre }}</td>
                <td class="text-right tabular-nums">Bs {{ number_format($encargo->anticipo, 2, ',', '.') }}</td>
                <td class="text-right font-medium tabular-nums">Bs {{ number_format($encargo->saldo, 2, ',', '.') }}</td>
                <td>
                    @if ($encargo->estado === 'completado')
                        <span class="badge badge-ok"><span class="dot"></span>Completado</span>
                    @else
                        <span class="badge badge-warn"><span class="dot"></span>Pendiente</span>
                    @endif
                </td>
                <td>
                    <div class="flex justify-end gap-1">
                        @if ($encargo->estado === 'pendiente')
                            <a href="{{ route('encargos.edit', $encargo) }}" class="btn btn-ghost btn-icon" title="Registrar pago" aria-label="Registrar pago"><x-icon name="coins" class="size-4" /></a>
                        @endif
                        <form method="POST" action="{{ route('encargos.destroy', $encargo) }}" onsubmit="return confirm('¿Eliminar el encargo de {{ $encargo->cliente?->nombre }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Eliminar" aria-label="Eliminar"><x-icon name="trash" class="size-4" /></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="inbox" class="size-5" /></span>
                    <div>
                        <p class="font-medium">Todavía no hay encargos</p>
                        <p class="text-sm text-ink-muted">Registre un producto a conseguir para un cliente.</p>
                    </div>
                    <a href="{{ route('encargos.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nuevo encargo</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
