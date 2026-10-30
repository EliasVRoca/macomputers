@extends('layouts.app')

@section('titulo', 'Reportes')
@section('encabezado', 'Reportes')
@section('subtitulo', 'Indicadores con gráficos según tu rol y la sucursal seleccionada.')

@section('contenido')
<form method="GET" action="{{ route('reportes.index') }}" class="card card-pad mb-6 grid gap-3 sm:grid-cols-3 lg:grid-cols-8">
    <div>
        <label for="f-desde" class="label">Desde</label>
        <input id="f-desde" name="desde" type="date" value="{{ $filtros['desde']->toDateString() }}" class="input">
    </div>
    <div>
        <label for="f-hasta" class="label">Hasta</label>
        <input id="f-hasta" name="hasta" type="date" value="{{ $filtros['hasta']->toDateString() }}" class="input">
    </div>
    <div>
        <label for="f-agrupacion" class="label">Agrupación</label>
        <select id="f-agrupacion" name="agrupacion" class="input">
            <option value="dia" @selected($filtros['agrupacion'] === 'dia')>Por día</option>
            <option value="semana" @selected($filtros['agrupacion'] === 'semana')>Por semana</option>
            <option value="mes" @selected($filtros['agrupacion'] === 'mes')>Por mes</option>
        </select>
    </div>
    @if ($puedeElegirSucursal)
        <div>
            <label for="f-sucursal" class="label">Sucursal</label>
            <select id="f-sucursal" name="sucursal_id" class="input">
                <option value="">Todas</option>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}" @selected($filtros['sucursal_id'] == $sucursal->id)>{{ $sucursal->nombre }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div class="flex items-end gap-2 {{ $puedeElegirSucursal ? 'lg:col-span-4' : 'sm:col-span-3 lg:col-span-5' }}">
        <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Filtrar</button>
        <a href="{{ route('reportes.index') }}" class="btn btn-ghost">Limpiar</a>
    </div>
</form>

@if ($datos === [])
    <div class="card card-pad">
        <div class="empty">
            <span class="kpi-icon"><x-icon name="chart-column" class="size-5" /></span>
            <div>
                <p class="font-medium">Sin reportes para tu rol</p>
                <p class="text-sm text-ink-muted">Los gráficos aparecen según los permisos de tus módulos.</p>
            </div>
        </div>
    </div>
@else
    @isset($datos['resumen'])
        <div class="mb-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="kpi">
                <p class="text-xs text-ink-muted">Ingresos del período</p>
                <p class="mt-1 text-2xl font-medium tabular-nums">Bs {{ number_format($datos['resumen']['ingresos'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-ink-subtle">{{ $datos['resumen']['ventas'] }} {{ $datos['resumen']['ventas'] === 1 ? 'venta' : 'ventas' }} · {{ $datos['resumen']['unidades'] }} {{ $datos['resumen']['unidades'] === 1 ? 'unidad' : 'unidades' }}</p>
            </div>
            <div class="kpi">
                <p class="text-xs text-ink-muted">Utilidad bruta</p>
                <p class="mt-1 text-2xl font-medium tabular-nums {{ $datos['resumen']['utilidad'] < 0 ? 'text-danger' : '' }}">Bs {{ number_format($datos['resumen']['utilidad'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-ink-subtle">Margen {{ $datos['resumen']['margen'] }} %</p>
            </div>
            <div class="kpi">
                <p class="text-xs text-ink-muted">Ticket promedio</p>
                <p class="mt-1 text-2xl font-medium tabular-nums">Bs {{ number_format($datos['resumen']['ticket_promedio'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-ink-subtle">Por venta de productos</p>
            </div>
            <div class="kpi">
                <p class="text-xs text-ink-muted">Período analizado</p>
                <p class="mt-1 text-lg font-medium">{{ $filtros['desde']->translatedFormat('d/m/Y') }} – {{ $filtros['hasta']->translatedFormat('d/m/Y') }}</p>
                <p class="mt-1 text-xs text-ink-subtle">{{ ucfirst(['dia' => 'agrupado por día', 'semana' => 'agrupado por semana', 'mes' => 'agrupado por mes'][$filtros['agrupacion']]) }}</p>
            </div>
        </div>
    @endisset

    @isset($datos['ventas'])
        <section class="mb-10">
            <h2 class="mb-4 flex items-center gap-2 text-lg font-medium"><x-icon name="receipt" class="size-5 text-ink-muted" /> Ventas e ingresos</h2>
            <div class="grid gap-4 lg:grid-cols-2">
                <x-grafico
                    titulo="Ingresos vs costo"
                    subtitulo="La utilidad es la distancia entre ambas líneas."
                    :config="$datos['ventas']['tendencia']"
                    altura="h-72"
                />
                <x-grafico
                    titulo="Cantidad de ventas"
                    subtitulo="Número de notas por período."
                    :config="$datos['ventas']['cantidades']"
                    altura="h-72"
                />
                <x-grafico
                    titulo="Ingresos por categoría"
                    subtitulo="Distribución según las categorías del catálogo."
                    :config="$datos['ventas']['por_categoria']"
                />
                @isset($datos['ventas']['por_sucursal'])
                    <x-grafico
                        titulo="Ingresos por sucursal"
                        :config="$datos['ventas']['por_sucursal']"
                    />
                @endisset
                <x-grafico
                    titulo="Top 10 productos vendidos"
                    subtitulo="Por monto facturado en el período."
                    :config="$datos['ventas']['top_productos']"
                    altura="h-80"
                />
            </div>
        </section>
    @endisset

    @isset($datos['inventario'])
        <section class="mb-10">
            <h2 class="mb-4 flex items-center gap-2 text-lg font-medium"><x-icon name="warehouse" class="size-5 text-ink-muted" /> Inventario</h2>
            <div class="mb-4 grid gap-4 sm:grid-cols-3">
                <div class="kpi">
                    <p class="text-xs text-ink-muted">Valor del stock (al costo)</p>
                    <p class="mt-1 text-2xl font-medium tabular-nums">Bs {{ number_format($datos['inventario']['cifras']['valor_total'], 2, ',', '.') }}</p>
                </div>
                <div class="kpi">
                    <p class="text-xs text-ink-muted">Unidades en stock</p>
                    <p class="mt-1 text-2xl font-medium tabular-nums">{{ number_format($datos['inventario']['cifras']['unidades'], 0, ',', '.') }}</p>
                </div>
                <div class="kpi {{ $datos['inventario']['cifras']['bajo_minimo'] > 0 ? 'border-danger' : '' }}">
                    <p class="text-xs text-ink-muted">Productos bajo mínimo</p>
                    <p class="mt-1 text-2xl font-medium tabular-nums {{ $datos['inventario']['cifras']['bajo_minimo'] > 0 ? 'text-danger' : '' }}">{{ $datos['inventario']['cifras']['bajo_minimo'] }}</p>
                </div>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                <x-grafico
                    titulo="Unidades por categoría"
                    :config="$datos['inventario']['por_categoria_unidades']"
                />
                <x-grafico
                    titulo="Valor al costo por categoría"
                    :config="$datos['inventario']['por_categoria_valor']"
                />
                <x-grafico
                    titulo="Top 10 productos por valor"
                    subtitulo="Stock disponible valorado al precio de compra."
                    :config="$datos['inventario']['top_valor']"
                    altura="h-80"
                />
            </div>
        </section>
    @endisset

    @isset($datos['servicios'])
        <section class="mb-10">
            <h2 class="mb-4 flex items-center gap-2 text-lg font-medium"><x-icon name="wrench" class="size-5 text-ink-muted" /> Servicio técnico</h2>
            <div class="grid gap-4 lg:grid-cols-2">
                <x-grafico
                    titulo="Órdenes por estado"
                    subtitulo="Órdenes recibidas dentro del rango de fechas."
                    :config="$datos['servicios']['por_estado']"
                />
                <x-grafico
                    titulo="Actividad del taller"
                    subtitulo="Órdenes recibidas e ingresos por período."
                    :config="$datos['servicios']['actividad']"
                    altura="h-72"
                />
            </div>
        </section>
    @endisset

    @isset($datos['busquedas'])
        <section class="mb-4">
            <h2 class="mb-4 flex items-center gap-2 text-lg font-medium"><x-icon name="search" class="size-5 text-ink-muted" /> Búsquedas de clientes</h2>
            <div class="grid gap-4 lg:grid-cols-2">
                <x-grafico
                    titulo="Términos más buscados"
                    subtitulo="Lo que la gente pidió, haya o no resultado."
                    :config="$datos['busquedas']['top_terminos']"
                    altura="h-80"
                />
                <x-grafico
                    titulo="Resultado de las búsquedas"
                    subtitulo="Oportunidades de surtido cuando hay búsquedas sin resultado."
                    :config="$datos['busquedas']['por_resultado']"
                />
                <x-grafico
                    titulo="Búsquedas sin resultado por sucursal"
                    :config="$datos['busquedas']['sin_resultado_sucursal']"
                />
            </div>
        </section>
    @endisset
@endif
@endsection
