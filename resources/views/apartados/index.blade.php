@extends('layouts.app')

@section('titulo', 'Apartados')
@section('encabezado', 'Apartados')
@section('subtitulo', 'Productos reservados con seña. El saldo se completa al entregar.')
@section('acciones')
    <a href="{{ route('apartados.create') }}" class="btn btn-primary">
        <x-icon name="plus" class="size-4" /> Nuevo apartado
    </a>
@endsection

@section('contenido')
@error('apartado') <p class="error-texto mb-4">{{ $message }}</p> @enderror

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Producto</th>
                <th>Sucursal</th>
                <th class="text-right">Seña</th>
                <th class="text-right">Saldo</th>
                <th>Estado</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($items as $apartado)
            <tr>
                <td class="font-medium">{{ $apartado->cliente?->nombre }}</td>
                <td>
                    {{ $apartado->producto?->nombre }}
                    <span class="block text-xs text-ink-muted">{{ $apartado->cantidad }} unidad{{ $apartado->cantidad === 1 ? '' : 'es' }}</span>
                </td>
                <td class="text-ink-muted">{{ $apartado->sucursal?->nombre }}</td>
                <td class="text-right tabular-nums">Bs {{ number_format($apartado->sena, 2, ',', '.') }}</td>
                <td class="text-right font-medium tabular-nums">Bs {{ number_format($apartado->saldo, 2, ',', '.') }}</td>
                <td>
                    @if ($apartado->estado === 'completado')
                        <span class="badge badge-ok"><span class="dot"></span>Completado</span>
                    @else
                        <span class="badge badge-warn"><span class="dot"></span>Activo</span>
                    @endif
                </td>
                <td>
                    <div class="flex justify-end gap-1">
                        @if ($apartado->estado === 'activo')
                            <a href="{{ route('apartados.edit', $apartado) }}" class="btn btn-ghost btn-sm" title="Registrar pago">
                                <x-icon name="coins" class="size-4" /> <span class="sr-only">Registrar pago</span>
                            </a>
                        @endif
                        <form method="POST" action="{{ route('apartados.destroy', $apartado) }}" onsubmit="return confirm('¿Eliminar el apartado de {{ $apartado->cliente?->nombre }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-sm text-danger" title="Eliminar">
                                <x-icon name="trash" class="size-4" /> <span class="sr-only">Eliminar</span>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7">
                    <div class="empty">
                        <span class="kpi-icon"><x-icon name="bookmark" class="size-5" /></span>
                        <div>
                            <p class="font-medium">Todavía no hay apartados</p>
                            <p class="text-sm text-ink-muted">Registre una reserva con seña para apartar un equipo.</p>
                        </div>
                        <a href="{{ route('apartados.create') }}" class="btn btn-primary btn-sm">
                            <x-icon name="plus" class="size-4" /> Nuevo apartado
                        </a>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
