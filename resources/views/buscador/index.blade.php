@extends('layouts.app')

@section('titulo', 'Buscador')
@section('encabezado', 'Buscador de productos')
@section('subtitulo', 'Encontrá cualquier producto por nombre, código, marca o atributo, con su stock por sucursal.')
@section('acciones')
    <a href="{{ route('disponibilidad.index') }}" class="btn btn-secondary"><x-icon name="map-pin" class="size-4" /> Disponibilidad</a>
    @if (auth()->user()->esGerente())
        <a href="{{ route('buscador.reporte') }}" class="btn btn-secondary"><x-icon name="trending-up" class="size-4" /> Lo más buscado</a>
    @endif
@endsection

@section('contenido')
@php
    $termino = $filtros['termino'] ?? '';
    $sucursalUsuario = auth()->user()->sucursal?->nombre;
@endphp

<div
    data-buscador
    data-url="{{ route('buscador.json') }}"
    data-termino="{{ $termino }}"
    data-sucursal="{{ $sucursalUsuarioId }}"
    data-puede-traspasar="{{ $puedeTraspasar ? '1' : '0' }}"
    data-puede-ver-ficha="{{ $puedeVerFicha ? '1' : '0' }}"
    data-puede-encargar="{{ $puedeEncargar ? '1' : '0' }}"
    data-puede-comprar="{{ $puedeComprar ? '1' : '0' }}"
>
    {{-- Hero de búsqueda --}}
    <div class="buscador-hero" data-buscador-hero>
        <div class="mb-5 inline-flex items-center gap-2 rounded-full border border-hairline bg-surface-1 px-3 py-1.5 text-xs font-medium text-ink-muted">
            <x-icon name="sparkles" class="size-3.5 text-brand" />
            Buscador inteligente
            @if ($sucursalUsuario)
                <span class="text-ink-tertiary">·</span>
                <span>Mi sucursal: {{ $sucursalUsuario }}</span>
            @endif
        </div>

        <div class="buscador-input">
            <x-icon name="search" class="size-5 shrink-0 text-ink-muted" />
            <input
                id="buscador-termino"
                type="search"
                name="termino"
                value="{{ $termino }}"
                placeholder="Ej.: teclado 60%, laptop lenovo, RAM 8GB…"
                autocomplete="off"
                spellcheck="false"
                aria-label="Buscar producto"
                aria-controls="buscador-resultados"
            >
            <button type="button" data-buscador-limpiar class="btn btn-ghost btn-icon {{ $termino === '' ? 'invisible' : '' }}" aria-label="Limpiar búsqueda">
                <x-icon name="x" class="size-4" />
            </button>
        </div>

        <p class="mt-3 text-xs text-ink-subtle">
            Escribí y los resultados aparecen al instante. <span class="kbd">/</span> enfoca la búsqueda · <span class="kbd">Esc</span> limpia.
        </p>

        <div class="mt-6 flex flex-wrap justify-center gap-2" data-buscador-sugerencias>
            @foreach (['teclado 60%', 'laptop lenovo', 'RAM 8GB', 'impresora', 'monitor'] as $sugerencia)
                <button type="button" class="buscador-sugerencia" data-sugerencia="{{ $sugerencia }}">{{ $sugerencia }}</button>
            @endforeach
        </div>

        <div class="mt-5 items-center justify-center gap-2 text-xs text-ink-subtle" data-buscador-recientes hidden>
            <span>Recientes:</span>
            <div class="flex flex-wrap justify-center gap-1.5" data-buscador-recientes-lista></div>
        </div>
    </div>

    {{-- Filtros --}}
    <details class="mx-auto mt-6 max-w-3xl" data-buscador-filtros>
        <summary class="mx-auto flex w-fit cursor-pointer list-none items-center gap-1.5 rounded-full border border-hairline bg-surface-1 px-3 py-1.5 text-xs font-medium text-ink-muted transition-colors hover:border-ink-tertiary hover:text-ink">
            <x-icon name="sliders" class="size-3.5" />
            Filtros
            <x-icon name="chevron-down" class="size-3.5" />
        </summary>
        <div class="mt-3 grid gap-3 rounded-xl border border-hairline bg-surface-1 p-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="f-categoria" class="label">Categoría</label>
                <select id="f-categoria" name="categoria_id" class="input" data-filtro="categoria_id">
                    <option value="">Todas</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected((string) ($filtros['categoria_id'] ?? '') === (string) $categoria->id)>{{ $categoria->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="f-marca" class="label">Marca</label>
                <select id="f-marca" name="marca" class="input" data-filtro="marca">
                    <option value="">Todas</option>
                    @foreach ($marcas as $marca)
                        <option value="{{ $marca }}" @selected(($filtros['marca'] ?? '') === $marca)>{{ $marca }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="f-condicion" class="label">Condición</label>
                <select id="f-condicion" name="condicion" class="input" data-filtro="condicion">
                    <option value="">Todas</option>
                    @foreach ($condiciones as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected(($filtros['condicion'] ?? '') === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
            </div>
            @if ($puedeElegirSucursal)
                <div>
                    <label for="f-sucursal" class="label">Sucursal de referencia</label>
                    <select id="f-sucursal" name="sucursal_id" class="input" data-filtro="sucursal_id">
                        @foreach ($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}" @selected((string) ($filtros['sucursal_id'] ?? $sucursalUsuarioId) === (string) $sucursal->id)>{{ $sucursal->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <label class="flex items-center gap-2 self-end pb-2.5 text-sm sm:col-span-2">
                <input type="checkbox" name="solo_stock" value="1" data-filtro="solo_stock" @checked($filtros['solo_stock'] ?? false) class="h-4 w-4 rounded border-hairline accent-[var(--color-brand)]">
                Mostrar solo productos con stock en mi sucursal
            </label>
        </div>
    </details>

    {{-- Resultados --}}
    <div class="mt-8">
        <div class="mb-4 flex items-end justify-between gap-3" data-buscador-resumen hidden>
            <div>
                <p class="text-sm font-medium" data-buscador-conteo></p>
                <p class="mt-0.5 text-xs text-ink-subtle" data-buscador-resultado></p>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" data-buscador-limpiar>Limpiar búsqueda</button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" data-buscador-resultados id="buscador-resultados"></div>

        <div data-buscador-cargando aria-live="polite" hidden>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @for ($i = 0; $i < 6; $i++)
                    <div class="overflow-hidden rounded-xl border border-hairline bg-surface-1">
                        <div class="skeleton aspect-[4/3] rounded-none"></div>
                        <div class="space-y-3 p-4">
                            <div class="skeleton h-4 w-4/5"></div>
                            <div class="skeleton h-3 w-1/2"></div>
                            <div class="skeleton h-3 w-2/3"></div>
                            <div class="skeleton h-9 w-full"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        <div data-buscador-vacio hidden>
            <div class="empty">
                <span class="kpi-icon"><x-icon name="search" class="size-5" /></span>
                <div>
                    <p class="text-base font-medium">No encontramos «<span data-vacio-termino></span>»</p>
                    <p class="mt-1 text-sm text-ink-muted">Probá con otra palabra, revisá los similares o dejanos el pedido.</p>
                </div>
                <div class="mt-2 flex flex-wrap justify-center gap-2">
                    @if ($puedeEncargar)
                        <a href="{{ route('encargos.create') }}" class="btn btn-primary btn-sm" data-cta-encargo>
                            <x-icon name="inbox" class="size-4" /> Crear encargo
                        </a>
                    @endif
                    @if ($puedeComprar)
                        <a href="{{ route('compras.create') }}" class="btn btn-secondary btn-sm" data-cta-compra>
                            <x-icon name="clipboard-list" class="size-4" /> Registrar compra
                        </a>
                    @endif
                    <a href="{{ route('disponibilidad.index') }}" class="btn btn-secondary btn-sm" data-cta-disponibilidad>
                        <x-icon name="map-pin" class="size-4" /> Ver disponibilidad
                    </a>
                </div>
            </div>

            <div class="mt-6" data-buscador-similares hidden>
                <div class="mb-3 flex items-end justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-medium">Quizá te sirva</h2>
                        <p class="text-xs text-ink-subtle">Productos parecidos por categoría y atributos.</p>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" data-similares></div>
            </div>
        </div>

        <div class="rounded-xl border border-danger px-4 py-3 text-sm text-danger" data-buscador-error role="alert" hidden></div>
    </div>

    <p class="sr-only" data-buscador-estado aria-live="polite"></p>
</div>

@include('inventario._modal-traspaso')
<script id="sucursales-datos" type="application/json">{!! json_encode($sucursales->pluck('nombre', 'id')->all(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!}</script>

@vite('resources/js/buscador.js')
@endsection
