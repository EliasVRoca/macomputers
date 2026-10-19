@extends('layouts.app')

@php
    use App\Enums\EstadoServicio;
    $esGerente = auth()->user()?->esGerente() ?? false;
@endphp

@section('titulo', 'Servicios técnicos')
@section('encabezado', 'Servicio técnico')
@section('subtitulo', 'Órdenes de trabajo: recepción, diagnóstico, repuestos, entrega y garantía.')
@section('acciones')
    <a href="{{ route('servicios.export', request()->query()) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Exportar CSV</a>
    <a href="{{ route('servicios.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo servicio</a>
@endsection

@section('contenido')
@php($hoy = now()->startOfDay())

{{-- Conteo por estado --}}
<div class="mb-6 flex flex-wrap gap-2">
    <a href="{{ route('servicios.index', array_filter([...$filtros, 'estado' => null])) }}"
       class="badge {{ empty($filtros['estado']) ? 'badge-brand' : 'badge-neutral' }}">
        Todas · {{ $conteos->sum() }}
    </a>
    @foreach ($estados as $clave => $etiqueta)
        <a href="{{ route('servicios.index', array_filter([...$filtros, 'estado' => $clave])) }}"
           class="badge {{ ($filtros['estado'] ?? null) === $clave ? 'badge-brand' : EstadoServicio::from($clave)->colorBadge() }}">
            <span class="dot"></span>{{ $etiqueta }} · {{ $conteos[$clave] ?? 0 }}
        </a>
    @endforeach
</div>

{{-- Filtros --}}
<form method="GET" action="{{ route('servicios.index') }}" class="card card-pad mb-6">
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="lg:col-span-2">
            <label for="buscar" class="label">Buscar</label>
            <input id="buscar" name="buscar" class="input" value="{{ $filtros['buscar'] ?? '' }}" placeholder="N.° de orden, equipo o cliente">
        </div>
        <div>
            <label for="prioridad" class="label">Prioridad</label>
            <select id="prioridad" name="prioridad" class="input">
                <option value="">Todas</option>
                @foreach ($prioridades as $clave => $etiqueta)
                    <option value="{{ $clave }}" @selected(($filtros['prioridad'] ?? null) === $clave)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
        @if ($puedeElegirSucursal)
            <div>
                <label for="sucursal_id" class="label">Sucursal</label>
                <select id="sucursal_id" name="sucursal_id" class="input">
                    <option value="">Todas</option>
                    @foreach ($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" @selected(($filtros['sucursal_id'] ?? null) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div>
            <label for="desde" class="label">Recepción desde</label>
            <input id="desde" name="desde" type="date" class="input" value="{{ $filtros['desde'] ?? '' }}">
        </div>
        <div>
            <label for="hasta" class="label">Recepción hasta</label>
            <input id="hasta" name="hasta" type="date" class="input" value="{{ $filtros['hasta'] ?? '' }}">
        </div>
        <label class="flex items-center gap-2 self-end pb-2.5 text-sm">
            <input type="checkbox" name="mias" value="1" @checked(! empty($filtros['mias'])) class="rounded border-hairline">
            Solo mis órdenes
        </label>
    </div>
    <input type="hidden" name="estado" value="{{ $filtros['estado'] ?? '' }}">
    <div class="mt-4 flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm"><x-icon name="search" class="size-4" /> Filtrar</button>
        <a href="{{ route('servicios.index') }}" class="btn btn-secondary btn-sm">Limpiar</a>
    </div>
</form>

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr>
                <th>Orden</th><th>Cliente</th><th>Equipo</th>
                @if ($puedeElegirSucursal) <th>Sucursal</th> @endif
                <th>Técnico</th><th>Prioridad</th><th>Estado</th><th>Prometida</th><th class="text-right">Total</th><th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($items as $servicio)
            <tr>
                <td class="whitespace-nowrap font-medium">{{ $servicio->numero }}</td>
                <td>{{ $servicio->cliente?->nombre }}</td>
                <td class="max-w-[16rem] truncate">{{ $servicio->equipo }}</td>
                @if ($puedeElegirSucursal) <td class="text-ink-muted">{{ $servicio->sucursal?->nombre }}</td> @endif
                <td class="text-ink-muted">{{ $servicio->tecnico?->nombre() ?? '—' }}</td>
                <td>
                    @if ($servicio->prioridad !== 'normal')
                        <span class="badge {{ $servicio->prioridad === 'urgente' ? 'badge-danger' : 'badge-warn' }}">{{ \App\Models\Servicio::PRIORIDADES[$servicio->prioridad] ?? $servicio->prioridad }}</span>
                    @else
                        <span class="text-ink-muted">{{ \App\Models\Servicio::PRIORIDADES[$servicio->prioridad] ?? '—' }}</span>
                    @endif
                </td>
                <td>
                    <span class="badge {{ $servicio->estadoEnum()->colorBadge() }}"><span class="dot"></span>{{ $servicio->estadoEnum()->etiqueta() }}</span>
                </td>
                <td class="whitespace-nowrap text-sm">
                    @if ($servicio->fecha_prometida)
                        @if ($servicio->fecha_prometida->lt($hoy) && ! $servicio->cerrado())
                            <span class="inline-flex items-center gap-1 text-danger"><x-icon name="alert-triangle" class="size-4" /> {{ $servicio->fecha_prometida->format('d/m/Y') }}</span>
                        @else
                            {{ $servicio->fecha_prometida->format('d/m/Y') }}
                        @endif
                    @else
                        <span class="text-ink-tertiary">—</span>
                    @endif
                </td>
                <td class="text-right tabular-nums">Bs {{ number_format($servicio->venta?->total ?? 0, 2, ',', '.') }}</td>
                <td>
                    <div class="flex justify-end gap-1">
                        <a href="{{ route('servicios.show', $servicio) }}" class="btn btn-ghost btn-icon" title="Ver orden" aria-label="Ver orden"><x-icon name="eye" class="size-4" /></a>
                        @if ($esGerente || ! $servicio->cerrado())
                            <a href="{{ route('servicios.edit', $servicio) }}" class="btn btn-ghost btn-icon" title="Editar datos" aria-label="Editar datos"><x-icon name="pencil" class="size-4" /></a>
                            <form method="POST" action="{{ route('servicios.destroy', $servicio) }}" onsubmit="return confirm('¿Dar de baja la orden {{ $servicio->numero }}? Se conservará el historial.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Dar de baja" aria-label="Dar de baja"><x-icon name="trash" class="size-4" /></button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ $puedeElegirSucursal ? 10 : 9 }}">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="wrench" class="size-5" /></span>
                    <div>
                        <p class="font-medium">No hay órdenes registradas</p>
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
