@extends('layouts.app')

@section('titulo', 'Servicios técnicos')
@section('encabezado', 'Servicio técnico')
@section('subtitulo', 'Mantenimiento y reparaciones simples, registrados como venta de servicio.')
@section('acciones')
    <a href="{{ route('servicios.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo servicio</a>
@endsection

@section('contenido')
@php($colores = ['recibido' => 'badge-neutral', 'en_proceso' => 'badge-warn', 'listo' => 'badge-brand', 'entregado' => 'badge-ok'])

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Cliente</th><th>Equipo</th><th>Técnico</th><th>Estado</th><th class="text-right">Costo</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $servicio)
            <tr>
                <td class="font-medium">{{ $servicio->cliente?->nombre }}</td>
                <td>{{ $servicio->equipo }}</td>
                <td class="text-ink-muted">{{ $servicio->tecnico?->username }}</td>
                <td><span class="badge {{ $colores[$servicio->estado] ?? 'badge-neutral' }}"><span class="dot"></span>{{ \App\Models\Servicio::ESTADOS[$servicio->estado] ?? $servicio->estado }}</span></td>
                <td class="text-right tabular-nums">Bs {{ number_format($servicio->venta?->total ?? 0, 2, ',', '.') }}</td>
                <td>
                    <div class="flex justify-end gap-1">
                        <a href="{{ route('servicios.edit', $servicio) }}" class="btn btn-ghost btn-icon" title="Actualizar" aria-label="Actualizar"><x-icon name="pencil" class="size-4" /></a>
                        <form method="POST" action="{{ route('servicios.destroy', $servicio) }}" onsubmit="return confirm('¿Eliminar el servicio de {{ $servicio->cliente?->nombre }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Eliminar" aria-label="Eliminar"><x-icon name="trash" class="size-4" /></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="wrench" class="size-5" /></span>
                    <div>
                        <p class="font-medium">No hay servicios registrados</p>
                        <p class="text-sm text-ink-muted">Registre la recepción de un equipo para reparar.</p>
                    </div>
                    <a href="{{ route('servicios.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nuevo servicio</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
