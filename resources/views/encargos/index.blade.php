@extends('layouts.app')

@section('titulo', 'Encargos')
@section('encabezado', 'Encargos')
@section('subtitulo', 'Productos no disponibles en stock, pedidos con anticipo.')
@section('acciones')
    <a href="{{ route('encargos.create') }}" class="btn btn-primary">Nuevo encargo</a>
@endsection

@section('contenido')
@error('encargo') <p class="error-texto mb-4">{{ $message }}</p> @enderror
<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead><tr><th>Cliente</th><th>Descripción</th><th>Sucursal</th><th class="text-right">Anticipo</th><th class="text-right">Saldo</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
        <tbody>
        @forelse ($items as $encargo)
            <tr>
                <td class="font-medium">{{ $encargo->cliente?->nombre }}</td>
                <td>{{ $encargo->descripcion }}</td>
                <td>{{ $encargo->sucursal?->nombre }}</td>
                <td class="text-right">Bs {{ number_format($encargo->anticipo, 2, ',', '.') }}</td>
                <td class="text-right">Bs {{ number_format($encargo->saldo, 2, ',', '.') }}</td>
                <td><span class="badge">{{ $encargo->estado === 'completado' ? 'Completado' : 'Pendiente' }}</span></td>
                <td class="flex justify-end gap-1">
                    @if ($encargo->estado === 'pendiente')
                        <a href="{{ route('encargos.edit', $encargo) }}" class="btn btn-ghost !py-1.5 text-sm">Registrar pago</a>
                    @endif
                    <form method="POST" action="{{ route('encargos.destroy', $encargo) }}" onsubmit="return confirm('¿Eliminar el encargo de {{ $encargo->cliente?->nombre }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost !py-1.5 text-sm text-semantic-error">Eliminar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-ink-muted">No hay encargos registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
