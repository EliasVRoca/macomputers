@extends('layouts.app')

@section('titulo', 'Apartados')
@section('encabezado', 'Apartados')
@section('subtitulo', 'Productos reservados con seña. El saldo se completa al entregar.')
@section('acciones')
    <a href="{{ route('apartados.create') }}" class="btn btn-primary">Nuevo apartado</a>
@endsection

@section('contenido')
@error('apartado') <p class="error-texto mb-4">{{ $message }}</p> @enderror
<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead><tr><th>Cliente</th><th>Producto</th><th>Sucursal</th><th class="text-right">Seña</th><th class="text-right">Saldo</th><th>Estado</th><th class="text-right">Acciones</th></tr></thead>
        <tbody>
        @forelse ($items as $apartado)
            <tr>
                <td class="font-medium">{{ $apartado->cliente?->nombre }}</td>
                <td>{{ $apartado->cantidad }} × {{ $apartado->producto?->nombre }}</td>
                <td>{{ $apartado->sucursal?->nombre }}</td>
                <td class="text-right">Bs {{ number_format($apartado->sena, 2, ',', '.') }}</td>
                <td class="text-right">Bs {{ number_format($apartado->saldo, 2, ',', '.') }}</td>
                <td><span class="badge">{{ $apartado->estado === 'completado' ? 'Completado' : 'Activo' }}</span></td>
                <td class="flex justify-end gap-1">
                    @if ($apartado->estado === 'activo')
                        <a href="{{ route('apartados.edit', $apartado) }}" class="btn btn-ghost !py-1.5 text-sm">Registrar pago</a>
                    @endif
                    <form method="POST" action="{{ route('apartados.destroy', $apartado) }}" onsubmit="return confirm('¿Eliminar el apartado de {{ $apartado->cliente?->nombre }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost !py-1.5 text-sm text-semantic-error">Eliminar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-ink-muted">No hay apartados registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
