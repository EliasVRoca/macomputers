@extends('layouts.app')

@section('titulo', 'Inicio')
@section('encabezado', 'Inicio')
@section('subtitulo')
    Resumen del día · {{ $sucursalActual?->nombre ?? ($puedeElegirSucursal ? 'Todas las sucursales' : 'Sin sucursal') }} · {{ $hoy->translatedFormat('l j \d\e F \d\e Y') }}
@endsection

@section('acciones')
    @if ($puedeElegirSucursal)
        <form method="GET" action="{{ route('inicio') }}" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="sucursal_id" class="sr-only">Sucursal</label>
                <select id="sucursal_id" name="sucursal_id" class="input w-56">
                    <option value="">Todas las sucursales</option>
                    @foreach ($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" @selected((string) old('sucursal_id', $sucursalActual?->id) === (string) $sucursal->id)>{{ $sucursal->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Ver</button>
        </form>
    @endif
@endsection

@section('contenido')
@if ($metricas === [])
    <div class="card card-pad">
        <div class="empty">
            <span class="kpi-icon"><x-icon name="panel-left" class="size-5" /></span>
            <div>
                <p class="font-medium">Sin métricas para tu rol</p>
                <p class="text-sm text-ink-muted">Usá el menú para acceder a los módulos disponibles.</p>
            </div>
        </div>
    </div>
@else
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @isset($metricas['ventas'])
            <a href="{{ route('ventas.index', array_filter(['desde' => $hoy->toDateString(), 'hasta' => $hoy->toDateString(), 'sucursal_id' => $sucursalActual?->id])) }}"
               data-kpi="ventas"
               class="kpi group flex flex-col transition-colors hover:border-ink-tertiary">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="kpi-icon"><x-icon name="receipt" class="size-5" /></span>
                        <p class="text-xs font-medium text-ink-muted">Ventas del día</p>
                    </div>
                    <x-icon name="chevron-right" class="size-4 text-ink-tertiary opacity-0 transition-opacity group-hover:opacity-100" />
                </div>
                <p class="mt-4 text-2xl font-semibold tabular-nums">Bs {{ number_format((float) $metricas['ventas']['total'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-ink-subtle">{{ $metricas['ventas']['cantidad'] }} {{ $metricas['ventas']['cantidad'] === 1 ? 'venta' : 'ventas' }} · productos</p>
            </a>
        @endisset

        @isset($metricas['bajo_minimo'])
            <a href="{{ route('inventario.index', ['bajo_minimo' => 1]) }}"
               data-kpi="bajo-minimo"
               class="kpi group flex flex-col transition-colors hover:border-ink-tertiary {{ $metricas['bajo_minimo'] > 0 ? 'border-danger' : '' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="kpi-icon {{ $metricas['bajo_minimo'] > 0 ? 'text-danger' : '' }}"><x-icon name="alert-triangle" class="size-5" /></span>
                        <p class="text-xs font-medium text-ink-muted">Bajo mínimo</p>
                    </div>
                    <x-icon name="chevron-right" class="size-4 text-ink-tertiary opacity-0 transition-opacity group-hover:opacity-100" />
                </div>
                <p class="mt-4 text-2xl font-semibold tabular-nums {{ $metricas['bajo_minimo'] > 0 ? 'text-danger' : '' }}">{{ $metricas['bajo_minimo'] }}</p>
                <p class="mt-1 text-xs text-ink-subtle">Stock total de la empresa ≤ mínimo</p>
            </a>
        @endisset

        @isset($metricas['ot_pendientes'])
            <a href="{{ route('servicios.index', array_filter(['sucursal_id' => $sucursalActual?->id])) }}"
               data-kpi="servicios"
               class="kpi group flex flex-col transition-colors hover:border-ink-tertiary">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="kpi-icon"><x-icon name="wrench" class="size-5" /></span>
                        <p class="text-xs font-medium text-ink-muted">Servicio técnico</p>
                    </div>
                    <x-icon name="chevron-right" class="size-4 text-ink-tertiary opacity-0 transition-opacity group-hover:opacity-100" />
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <div class="rounded-lg bg-surface-2 px-3 py-2">
                        <p class="text-[11px] text-ink-muted">Pendientes</p>
                        <p class="text-xl font-semibold tabular-nums">{{ $metricas['ot_pendientes'] }}</p>
                    </div>
                    <div class="rounded-lg bg-surface-2 px-3 py-2">
                        <p class="text-[11px] text-ink-muted">Listas</p>
                        <p class="text-xl font-semibold tabular-nums">{{ $metricas['ot_listas'] }}</p>
                    </div>
                </div>
            </a>
        @endisset

        @isset($metricas['traspasos'])
            <a href="{{ route('traspasos.index', ['desde' => $hoy->toDateString(), 'hasta' => $hoy->toDateString()]) }}"
               data-kpi="traspasos"
               class="kpi group flex flex-col transition-colors hover:border-ink-tertiary">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="kpi-icon"><x-icon name="arrow-left-right" class="size-5" /></span>
                        <p class="text-xs font-medium text-ink-muted">Traspasos de hoy</p>
                    </div>
                    <x-icon name="chevron-right" class="size-4 text-ink-tertiary opacity-0 transition-opacity group-hover:opacity-100" />
                </div>
                <p class="mt-4 text-2xl font-semibold tabular-nums">{{ $metricas['traspasos'] }}</p>
                <p class="mt-1 text-xs text-ink-subtle">Movimientos entre sucursales</p>
            </a>
        @endisset

        @isset($metricas['busquedas_sin_resultado'])
            @php
                $urlBusquedas = $puedeElegirSucursal
                    ? route('buscador.reporte', array_filter(['sucursal_id' => $sucursalActual?->id]))
                    : route('buscador.index');
            @endphp
            <a href="{{ $urlBusquedas }}"
               data-kpi="busquedas"
               class="kpi group flex flex-col transition-colors hover:border-ink-tertiary">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="kpi-icon"><x-icon name="search" class="size-5" /></span>
                        <p class="text-xs font-medium text-ink-muted">Búsquedas sin resultado</p>
                    </div>
                    <x-icon name="chevron-right" class="size-4 text-ink-tertiary opacity-0 transition-opacity group-hover:opacity-100" />
                </div>
                <p class="mt-4 text-2xl font-semibold tabular-nums">{{ $metricas['busquedas_sin_resultado'] }}</p>
                <p class="mt-1 text-xs text-ink-subtle">{{ $puedeElegirSucursal ? 'Ver el reporte de búsquedas' : 'Abrir el buscador' }}</p>
            </a>
        @endisset
    </div>
@endif
@endsection
