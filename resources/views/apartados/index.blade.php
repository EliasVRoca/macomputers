@extends('layouts.app')

@section('titulo', 'Apartados')
@section('encabezado', 'Apartados')
@section('subtitulo', 'Productos reservados con seña. El saldo se completa al entregar.')
@section('acciones')
    <a href="{{ route('apartados.export', request()->query()) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Exportar CSV</a>
    <a href="{{ route('apartados.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo apartado</a>
@endsection

@section('contenido')
@error('apartado') <p class="error-texto mb-4">{{ $message }}</p> @enderror

<form method="GET" action="{{ route('apartados.index') }}" class="card card-pad mb-4 grid gap-3 sm:grid-cols-4">
    <div class="sm:col-span-2">
        <label for="buscar" class="label">Buscar</label>
        <input type="search" id="buscar" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Cliente o producto" class="input">
    </div>
    <div>
        <label for="f-estado" class="label">Estado</label>
        <select id="f-estado" name="estado" class="input">
            <option value="">Todos</option>
            <option value="activo" @selected(($filtros['estado'] ?? null) === 'activo')>Activo</option>
            <option value="completado" @selected(($filtros['estado'] ?? null) === 'completado')>Completado</option>
            <option value="cancelado" @selected(($filtros['estado'] ?? null) === 'cancelado')>Cancelado</option>
        </select>
    </div>
    <div class="flex items-end gap-2">
        <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Filtrar</button>
        <a href="{{ route('apartados.index') }}" class="btn btn-ghost">Limpiar</a>
    </div>
</form>

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr>
                <th>Fecha</th>
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
                <td class="whitespace-nowrap">{{ $apartado->fecha?->format('d/m/Y') }}</td>
                <td class="font-medium">{{ $apartado->cliente?->nombre }}</td>
                <td>
                    {{ $apartado->producto?->nombre }}
                    <span class="block text-xs text-ink-muted">{{ $apartado->cantidad }} unidad{{ $apartado->cantidad === 1 ? '' : 'es' }}</span>
                </td>
                <td class="text-ink-muted">{{ $apartado->sucursal?->nombre }}</td>
                <td class="text-right tabular-nums">Bs {{ number_format($apartado->sena, 2, ',', '.') }}</td>
                <td class="text-right font-medium tabular-nums">Bs {{ number_format($apartado->saldo, 2, ',', '.') }}</td>
                <td>
                    @if ($apartado->estado === \App\Models\Apartado::ESTADO_COMPLETADO)
                        <span class="badge badge-ok"><span class="dot"></span>Completado</span>
                    @elseif ($apartado->estado === \App\Models\Apartado::ESTADO_CANCELADO)
                        <span class="badge badge-neutral"><span class="dot"></span>Cancelado</span>
                    @else
                        <span class="badge badge-warn"><span class="dot"></span>Activo</span>
                    @endif
                </td>
                <td>
                    <div class="flex justify-end gap-1">
                        @if ($apartado->estado === \App\Models\Apartado::ESTADO_ACTIVO)
                            <a href="{{ route('apartados.edit', $apartado) }}" class="btn btn-ghost btn-icon" title="Registrar pago" aria-label="Registrar pago"><x-icon name="coins" class="size-4" /></a>
                        @endif
                        <a href="{{ route('apartados.show', $apartado) }}" class="btn btn-ghost btn-icon" title="Ver detalle" aria-label="Ver detalle"><x-icon name="eye" class="size-4" /></a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8">
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
