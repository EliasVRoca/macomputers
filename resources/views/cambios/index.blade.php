@extends('layouts.app')

@section('titulo', 'Cambios y devoluciones')
@section('encabezado', 'Cambios y devoluciones')
@section('subtitulo', 'Cambios o devoluciones por falla, a partir de una venta registrada.')
@section('acciones')
    <a href="{{ route('cambios.export', request()->query()) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Exportar CSV</a>
    <a href="{{ route('cambios.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Nuevo cambio</a>
@endsection

@section('contenido')
<form method="GET" action="{{ route('cambios.index') }}" class="card card-pad mb-4 grid gap-3 sm:grid-cols-4">
    <div class="sm:col-span-2">
        <label for="buscar" class="label">Buscar</label>
        <input type="search" id="buscar" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Producto o motivo" class="input">
    </div>
    <div>
        <label for="f-tipo" class="label">Tipo</label>
        <select id="f-tipo" name="tipo" class="input">
            <option value="">Todos</option>
            <option value="cambio" @selected(($filtros['tipo'] ?? null) === 'cambio')>Cambio</option>
            <option value="devolucion" @selected(($filtros['tipo'] ?? null) === 'devolucion')>Devolución</option>
        </select>
    </div>
    <div class="flex items-end gap-2">
        <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Filtrar</button>
        <a href="{{ route('cambios.index') }}" class="btn btn-ghost">Limpiar</a>
    </div>
</form>

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr><th>Fecha</th><th>Venta</th><th>Producto</th><th class="text-right">Cant.</th><th>Tipo</th><th class="text-right">Diferencia</th><th>Motivo</th><th class="text-right">Detalle</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $cambio)
            <tr>
                <td class="whitespace-nowrap">{{ $cambio->fecha->format('d/m/Y') }}</td>
                <td class="font-mono text-xs">{{ $cambio->venta?->numero() }}</td>
                <td>
                    {{ $cambio->producto?->nombre }}
                    @if ($cambio->reemplazo)<span class="block text-xs text-ink-muted">Reemplazo: {{ $cambio->reemplazo->nombre }}</span>@endif
                </td>
                <td class="text-right tabular-nums">{{ $cambio->cantidad }}</td>
                <td>
                    @if ($cambio->tipo === \App\Models\Cambio::TIPO_DEVOLUCION)
                        <span class="badge badge-danger"><span class="dot"></span>Devolución</span>
                    @else
                        <span class="badge badge-brand"><span class="dot"></span>Cambio</span>
                    @endif
                </td>
                <td class="text-right tabular-nums">Bs {{ number_format($cambio->monto_diferencia, 2, ',', '.') }}</td>
                <td class="max-w-xs truncate text-ink-muted">{{ $cambio->motivo }}</td>
                <td>
                    <div class="flex justify-end">
                        <a href="{{ route('cambios.show', $cambio) }}" class="btn btn-ghost btn-icon" title="Ver detalle" aria-label="Ver detalle"><x-icon name="eye" class="size-4" /></a>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="8">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="repeat" class="size-5" /></span>
                    <div>
                        <p class="font-medium">No hay cambios registrados</p>
                        <p class="text-sm text-ink-muted">Registre un cambio o devolución a partir de una venta.</p>
                    </div>
                    <a href="{{ route('cambios.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Nuevo cambio</a>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
