@extends('layouts.app')

@section('titulo', 'Lo más buscado')
@section('encabezado', 'Lo más buscado que no tenemos')
@section('subtitulo', 'Términos que los clientes pidieron y no se encontraron o no tenían stock.')
@section('acciones')
    <a href="{{ route('buscador.reporte.export', request()->query()) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> Exportar CSV</a>
    <a href="{{ route('buscador.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver al buscador</a>
@endsection

@section('contenido')
@php($items = $datos['items'])
@php($maximo = max(1, (int) $items->max('veces')))

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <div class="kpi">
        <p class="text-xs text-ink-muted">Búsquedas sin resultado</p>
        <p class="mt-1 text-2xl font-medium tabular-nums">{{ $datos['total'] }}</p>
    </div>
    <div class="kpi">
        <p class="text-xs text-ink-muted">Términos distintos</p>
        <p class="mt-1 text-2xl font-medium tabular-nums">{{ $datos['terminos'] }}</p>
    </div>
    <div class="kpi">
        <p class="text-xs text-ink-muted">Término más buscado</p>
        <p class="mt-1 truncate text-lg font-medium">{{ $datos['top'] ?? '—' }}</p>
    </div>
</div>

<form method="GET" action="{{ route('buscador.reporte') }}" class="card card-pad mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
    <div>
        <label for="desde" class="label">Desde</label>
        <input id="desde" name="desde" type="date" value="{{ $filtros['desde'] ?? '' }}" class="input">
    </div>
    <div>
        <label for="hasta" class="label">Hasta</label>
        <input id="hasta" name="hasta" type="date" value="{{ $filtros['hasta'] ?? '' }}" class="input">
    </div>
    <div>
        <label for="sucursal_id" class="label">Sucursal</label>
        <select id="sucursal_id" name="sucursal_id" class="input">
            <option value="">Todas</option>
            @foreach ($sucursales as $sucursal)
                <option value="{{ $sucursal->id }}" @selected((string) ($filtros['sucursal_id'] ?? '') === (string) $sucursal->id)>{{ $sucursal->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="resultado" class="label">Resultado</label>
        <select id="resultado" name="resultado" class="input">
            <option value="">Ambos</option>
            @foreach ($resultados as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(($filtros['resultado'] ?? '') === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-end gap-2">
        <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Filtrar</button>
        <a href="{{ route('buscador.reporte') }}" class="btn btn-ghost">Limpiar</a>
    </div>
</form>

<div class="card card-flush overflow-x-auto">
    <table class="tabla">
        <thead>
            <tr>
                <th>Término</th>
                <th class="w-56">Demanda</th>
                <th class="text-right">No existe</th>
                <th class="text-right">Sin stock</th>
                <th>Última búsqueda</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($items as $item)
            @php($porcentaje = max(4, round(((int) $item->veces / $maximo) * 100)))
            <tr>
                <td>
                    <p class="font-medium">{{ $item->termino }}</p>
                    <p class="text-xs text-ink-subtle">{{ (int) $item->veces }} {{ (int) $item->veces === 1 ? 'búsqueda' : 'búsquedas' }}</p>
                </td>
                <td>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-surface-2">
                        <div class="h-2 rounded-full bg-brand" style="width: {{ $porcentaje }}%"></div>
                    </div>
                </td>
                <td class="text-right tabular-nums">{{ (int) $item->no_existe }}</td>
                <td class="text-right tabular-nums">{{ (int) $item->sin_stock }}</td>
                <td class="whitespace-nowrap text-sm text-ink-muted">
                    {{ $item->ultima ? \Illuminate\Support\Carbon::parse($item->ultima)->format('d/m/Y H:i') : '—' }}
                </td>
                <td>
                    <div class="flex justify-end gap-1">
                        <a href="{{ route('buscador.index', ['termino' => $item->termino]) }}" class="btn btn-ghost btn-icon" title="Buscar de nuevo" aria-label="Buscar de nuevo"><x-icon name="search" class="size-4" /></a>
                        <a href="{{ route('compras.create', ['descripcion' => $item->termino]) }}" class="btn btn-ghost btn-icon" title="Registrar compra" aria-label="Registrar compra"><x-icon name="clipboard-list" class="size-4" /></a>
                        <a href="{{ route('encargos.create', ['descripcion' => $item->termino]) }}" class="btn btn-ghost btn-icon" title="Crear encargo" aria-label="Crear encargo"><x-icon name="inbox" class="size-4" /></a>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">
                <div class="empty">
                    <span class="kpi-icon"><x-icon name="trending-up" class="size-5" /></span>
                    <div>
                        <p class="font-medium">Todavía no hay búsquedas sin resultado</p>
                        <p class="text-sm text-ink-muted">Cuando un cliente pida algo que no está, aparecerá acá.</p>
                    </div>
                </div>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
