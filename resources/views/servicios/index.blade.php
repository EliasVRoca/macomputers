@extends('layouts.app')

@section('titulo', 'Servicios técnicos')
@section('encabezado', 'Servicio técnico')
@section('subtitulo', 'Mantenimiento y reparaciones simples, registrados como venta de servicio.')
@section('acciones')
    <a href="{{ route('servicios.create') }}" class="btn btn-primary">Nuevo servicio</a>
@endsection

@section('contenido')
<div class="card overflow-x-auto p-0">
    <table class="tabla w-full">
        <thead><tr><th>Cliente</th><th>Equipo</th><th>Técnico</th><th>Estado</th><th class="text-right">Costo</th><th class="text-right">Acciones</th></tr></thead>
        <tbody>
        @forelse ($items as $servicio)
            <tr>
                <td class="font-medium">{{ $servicio->cliente?->nombre }}</td>
                <td>{{ $servicio->equipo }}</td>
                <td>{{ $servicio->tecnico?->username }}</td>
                <td><span class="badge">{{ \App\Models\Servicio::ESTADOS[$servicio->estado] ?? $servicio->estado }}</span></td>
                <td class="text-right">Bs {{ number_format($servicio->venta?->total ?? 0, 2, ',', '.') }}</td>
                <td class="flex justify-end gap-1">
                    <a href="{{ route('servicios.edit', $servicio) }}" class="btn btn-ghost !py-1.5 text-sm">Actualizar</a>
                    <form method="POST" action="{{ route('servicios.destroy', $servicio) }}" onsubmit="return confirm('¿Eliminar el servicio de {{ $servicio->cliente?->nombre }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost !py-1.5 text-sm text-semantic-error">Eliminar</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-ink-muted">No hay servicios registrados.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
