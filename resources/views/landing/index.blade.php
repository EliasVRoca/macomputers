@extends('layouts.landing')

@section('titulo', 'Sistema de inventario multi-sucursal')

@section('contenido')
    {{-- Hero --}}
    <section class="landing-section pt-10 sm:pt-14 lg:pt-20">
        <div class="landing-container grid items-center gap-14 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16">
            <div>
                <p class="landing-eyebrow">
                    <span class="h-1.5 w-1.5 rounded-full bg-brand"></span>
                    Sistema de gestión · M&amp;A Computers
                </p>
                <h1 class="landing-title mt-5">
                    Todo el stock de las dos sucursales, <span class="text-brand">en una sola pantalla.</span>
                </h1>
                <p class="landing-lead mt-6 max-w-xl">
                    Inventario, ventas, traspasos y servicio técnico centralizados. La disponibilidad deja de depender de una llamada: está en el sistema, al instante.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        Ingresar al sistema
                        <x-icon name="chevron-right" class="size-4" />
                    </a>
                    <a href="#modulos" class="btn btn-secondary">
                        Ver los módulos
                        <x-icon name="chevron-down" class="size-4" />
                    </a>
                </div>

                <dl class="mt-10 grid max-w-lg grid-cols-3 gap-4 border-t border-hairline pt-6">
                    <div>
                        <dt class="text-xs text-ink-muted">Sucursales</dt>
                        <dd class="mt-1 text-2xl font-semibold tracking-tight">2</dd>
                    </div>
                    <div class="border-l border-hairline pl-4">
                        <dt class="text-xs text-ink-muted">Módulos</dt>
                        <dd class="mt-1 text-2xl font-semibold tracking-tight">17</dd>
                    </div>
                    <div class="border-l border-hairline pl-4">
                        <dt class="text-xs text-ink-muted">Roles</dt>
                        <dd class="mt-1 text-2xl font-semibold tracking-tight">3</dd>
                    </div>
                </dl>
            </div>

            <div class="relative">
                @include('landing._panel-mockup')

                <div class="landing-float-card hidden lg:flex" aria-hidden="true">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-ok-soft text-ok">
                        <x-icon name="check" class="size-4" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium">Traspaso registrado</p>
                        <p class="truncate text-[11px] text-ink-muted">Segunda sucursal → Principal · 2 unidades</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Problema y solución --}}
    <section id="sistema" class="landing-section">
        <div class="landing-container">
            <div class="max-w-2xl">
                <p class="landing-eyebrow">El problema que resuelve</p>
                <h2 class="landing-h2 mt-4">Dos tiendas. Un solo inventario.</h2>
                <p class="landing-lead mt-4">
                    Cuando cada sucursal lleva su propio registro, la respuesta al cliente depende de una llamada. El sistema elimina ese paso y deja todo el historial en un solo lugar.
                </p>
            </div>

            <div class="mt-10 grid gap-4 lg:grid-cols-2">
                <article class="card bg-surface-2">
                    <div class="flex items-center gap-3">
                        <span class="landing-icon-tile bg-surface-1">
                            <x-icon name="x" class="size-4" />
                        </span>
                        <div>
                            <p class="text-xs font-medium text-ink-muted">Antes</p>
                            <h3 class="text-lg font-medium tracking-tight">Cada sucursal por su lado</h3>
                        </div>
                    </div>
                    <ul class="mt-5 space-y-3">
                        <li class="landing-compare-item">
                            <x-icon name="x" class="mt-0.5 size-4 shrink-0 text-ink-tertiary" />
                            <span>Stock anotado por separado, sin una fuente única de verdad.</span>
                        </li>
                        <li class="landing-compare-item">
                            <x-icon name="x" class="mt-0.5 size-4 shrink-0 text-ink-tertiary" />
                            <span>Llamadas o traslados físicos para confirmar disponibilidad.</span>
                        </li>
                        <li class="landing-compare-item">
                            <x-icon name="x" class="mt-0.5 size-4 shrink-0 text-ink-tertiary" />
                            <span>Traslados entre tiendas anotados a mano o sin registro.</span>
                        </li>
                        <li class="landing-compare-item">
                            <x-icon name="x" class="mt-0.5 size-4 shrink-0 text-ink-tertiary" />
                            <span>Ventas, apartados y servicio técnico dispersos.</span>
                        </li>
                    </ul>
                </article>

                <article class="card">
                    <div class="flex items-center gap-3">
                        <span class="landing-icon-tile bg-brand-soft text-brand">
                            <x-icon name="check" class="size-4" />
                        </span>
                        <div>
                            <p class="text-xs font-medium text-brand">Con M&amp;A</p>
                            <h3 class="text-lg font-medium tracking-tight">Una operación conectada</h3>
                        </div>
                    </div>
                    <ul class="mt-5 space-y-3">
                        <li class="landing-compare-item">
                            <x-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand" />
                            <span>Disponibilidad por sucursal al instante, sin llamar a la otra tienda.</span>
                        </li>
                        <li class="landing-compare-item">
                            <x-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand" />
                            <span>Traspaso que descuenta del origen y suma al destino en un paso.</span>
                        </li>
                        <li class="landing-compare-item">
                            <x-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand" />
                            <span>Kardex completo: cada entrada, salida, ajuste y venta queda registrada.</span>
                        </li>
                        <li class="landing-compare-item">
                            <x-icon name="check" class="mt-0.5 size-4 shrink-0 text-brand" />
                            <span>Ventas, apartados, encargos y órdenes de servicio en el mismo sistema.</span>
                        </li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    {{-- Módulo estrella --}}
    <section id="buscador" class="landing-section">
        <div class="landing-container">
            <div class="overflow-hidden rounded-2xl border border-hairline bg-surface-1">
                <div class="grid lg:grid-cols-[0.95fr_1.05fr]">
                    <div class="min-w-0 p-6 sm:p-10 lg:p-12">
                        <p class="landing-eyebrow border-0 bg-transparent px-0">
                            <x-icon name="sparkles" class="size-3.5 text-brand" />
                            Módulo estrella
                        </p>
                        <h2 class="landing-h2 mt-4">Una búsqueda. Stock, imagen y alternativas.</h2>
                        <p class="landing-lead mt-4">
                            Escribe el nombre, código, marca, modelo o atributo y obtén la ficha con la existencia de cada sucursal. Si no hay coincidencia exacta, el sistema propone alternativas.
                        </p>

                        <ul class="mt-8 grid gap-5 sm:grid-cols-2">
                            <li class="flex gap-3">
                                <span class="landing-icon-tile shrink-0">
                                    <x-icon name="search" class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">Búsqueda instantánea</p>
                                    <p class="mt-1 text-sm leading-relaxed text-ink-muted">Por nombre, código, marca, modelo y atributos.</p>
                                </div>
                            </li>
                            <li class="flex gap-3">
                                <span class="landing-icon-tile shrink-0">
                                    <x-icon name="map-pin" class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">Stock por sucursal</p>
                                    <p class="mt-1 text-sm leading-relaxed text-ink-muted">Total y unidades en cada tienda, con alerta de bajo mínimo.</p>
                                </div>
                            </li>
                            <li class="flex gap-3">
                                <span class="landing-icon-tile shrink-0">
                                    <x-icon name="sparkles" class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">Productos similares</p>
                                    <p class="mt-1 text-sm leading-relaxed text-ink-muted">Alternativas equivalentes cuando no hay coincidencia exacta.</p>
                                </div>
                            </li>
                            <li class="flex gap-3">
                                <span class="landing-icon-tile shrink-0">
                                    <x-icon name="trending-up" class="size-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium">Demanda que no se pierde</p>
                                    <p class="mt-1 text-sm leading-relaxed text-ink-muted">Las búsquedas sin resultado quedan registradas para decidir qué comprar.</p>
                                </div>
                            </li>
                        </ul>

                        <a href="{{ route('login') }}" class="btn btn-primary mt-8">
                            Probar el buscador
                            <x-icon name="chevron-right" class="size-4" />
                        </a>
                    </div>

                    <div class="min-w-0 border-t border-hairline bg-canvas/60 p-4 sm:p-8 lg:border-t-0 lg:border-l">
                        @include('landing._buscador-mockup')
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Módulos --}}
    <section id="modulos" class="landing-section">
        <div class="landing-container">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="max-w-2xl">
                    <p class="landing-eyebrow">17 módulos · 5 áreas</p>
                    <h2 class="landing-h2 mt-4">Todo el negocio, conectado.</h2>
                    <p class="landing-lead mt-4">
                        Cada área tiene su lugar y cada movimiento deja rastro. Los permisos se ajustan por rol y el alcance por sucursal.
                    </p>
                </div>
                <a href="{{ route('login') }}" class="btn btn-secondary btn-sm shrink-0">
                    Ver el sistema
                    <x-icon name="chevron-right" class="size-4" />
                </a>
            </div>

            @php
                $descripciones = [
                    'Administración' => 'Usuarios, roles y sucursales bajo control.',
                    'Catálogo' => 'Categorías, productos y proveedores con ficha completa.',
                    'Inventario' => 'Disponibilidad, existencias, traspasos y compras.',
                    'Ventas' => 'Ventas, apartados, encargos, cambios y clientes.',
                    'Servicio técnico' => 'Órdenes de trabajo, repuestos y estados.',
                ];
                $anchos = [
                    'Administración' => 'lg:col-span-2',
                    'Catálogo' => 'lg:col-span-2',
                    'Inventario' => 'lg:col-span-2',
                    'Ventas' => 'lg:col-span-3',
                    'Servicio técnico' => 'lg:col-span-3',
                ];
            @endphp

            <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-6">
                @foreach (config('permisos.grupos') as $nombre => $grupo)
                    <article class="card flex flex-col {{ $anchos[$nombre] ?? 'lg:col-span-2' }}">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand">
                                <x-icon :name="$grupo['icon']" class="size-5" />
                            </span>
                            <h3 class="text-lg font-medium tracking-tight">{{ $nombre }}</h3>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-ink-muted">{{ $descripciones[$nombre] ?? '' }}</p>
                        <ul class="mt-5 flex flex-wrap gap-1.5">
                            @foreach ($grupo['modulos'] as $clave)
                                <li class="rounded-full bg-surface-2 px-2.5 py-1 text-xs text-ink-muted">
                                    {{ config("permisos.modulos.{$clave}.label") }}
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>

            <p class="mt-6 text-xs text-ink-muted">
                Los módulos visibles dependen del rol. El gerente administra todo el sistema; el vendedor y el técnico trabajan con su sucursal.
            </p>
        </div>
    </section>

    {{-- Cómo funciona --}}
    <section id="como-funciona" class="landing-section">
        <div class="landing-container">
            <div class="max-w-2xl">
                <p class="landing-eyebrow">Cómo funciona</p>
                <h2 class="landing-h2 mt-4">Del mostrador a la otra sucursal, en un solo paso.</h2>
                <p class="landing-lead mt-4">
                    El flujo que antes dependía de llamadas ahora se resuelve dentro del sistema, con el stock actualizado en cada etapa.
                </p>
            </div>

            <ol class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <li class="card">
                    <div class="flex items-center justify-between gap-3">
                        <span class="landing-step-number">1</span>
                        <x-icon name="search" class="size-5 text-ink-tertiary" />
                    </div>
                    <h3 class="mt-5 text-base font-medium">Busca el producto</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-muted">Por nombre, código, marca, modelo o atributo. Con foto y ficha técnica.</p>
                </li>
                <li class="card">
                    <div class="flex items-center justify-between gap-3">
                        <span class="landing-step-number">2</span>
                        <x-icon name="map-pin" class="size-5 text-ink-tertiary" />
                    </div>
                    <h3 class="mt-5 text-base font-medium">Consulta ambas sucursales</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-muted">Existencia real de cada tienda y alerta cuando el producto está bajo mínimo.</p>
                </li>
                <li class="card">
                    <div class="flex items-center justify-between gap-3">
                        <span class="landing-step-number">3</span>
                        <x-icon name="arrow-left-right" class="size-5 text-ink-tertiary" />
                    </div>
                    <h3 class="mt-5 text-base font-medium">Registra el traspaso</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-muted">El sistema descuenta del origen y suma al destino en una sola operación.</p>
                </li>
                <li class="card">
                    <div class="flex items-center justify-between gap-3">
                        <span class="landing-step-number">4</span>
                        <x-icon name="receipt" class="size-5 text-ink-tertiary" />
                    </div>
                    <h3 class="mt-5 text-base font-medium">Vende o entrega</h3>
                    <p class="mt-2 text-sm leading-relaxed text-ink-muted">Venta, apartado, encargo u orden de servicio, con su registro y comprobante.</p>
                </li>
            </ol>
        </div>
    </section>

    {{-- Roles y seguridad --}}
    <section class="landing-section">
        <div class="landing-container">
            <div class="max-w-2xl">
                <p class="landing-eyebrow">Roles y permisos</p>
                <h2 class="landing-h2 mt-4">Cada rol ve exactamente lo que necesita.</h2>
                <p class="landing-lead mt-4">
                    Permisos configurables y aislamiento por sucursal. Cada persona trabaja con lo suyo, sin ruido y sin accesos de más.
                </p>
            </div>

            <div class="mt-10 grid gap-4 md:grid-cols-3">
                <article class="card">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand">
                            <x-icon name="shield" class="size-5" />
                        </span>
                        <h3 class="text-lg font-medium tracking-tight">Gerente</h3>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed text-ink-muted">Administra ambas sucursales, usuarios, roles y catálogo. Ve todas las métricas del negocio.</p>
                    <ul class="mt-5 flex flex-wrap gap-1.5">
                        <li class="rounded-full bg-surface-2 px-2.5 py-1 text-xs text-ink-muted">Ambas sucursales</li>
                        <li class="rounded-full bg-surface-2 px-2.5 py-1 text-xs text-ink-muted">Usuarios y roles</li>
                        <li class="rounded-full bg-surface-2 px-2.5 py-1 text-xs text-ink-muted">Catálogo</li>
                        <li class="rounded-full bg-surface-2 px-2.5 py-1 text-xs text-ink-muted">Compras</li>
                    </ul>
                </article>

                <article class="card">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand">
                            <x-icon name="shopping-cart" class="size-5" />
                        </span>
                        <h3 class="text-lg font-medium tracking-tight">Vendedor</h3>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed text-ink-muted">Atiende ventas, apartados, encargos, cambios y el inventario de su sucursal.</p>
                    <ul class="mt-5 flex flex-wrap gap-1.5">
                        <li class="rounded-full bg-surface-2 px-2.5 py-1 text-xs text-ink-muted">Ventas</li>
                        <li class="rounded-full bg-surface-2 px-2.5 py-1 text-xs text-ink-muted">Apartados</li>
                        <li class="rounded-full bg-surface-2 px-2.5 py-1 text-xs text-ink-muted">Encargos</li>
                        <li class="rounded-full bg-surface-2 px-2.5 py-1 text-xs text-ink-muted">Cambios</li>
                    </ul>
                </article>

                <article class="card">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-soft text-brand">
                            <x-icon name="wrench" class="size-5" />
                        </span>
                        <h3 class="text-lg font-medium tracking-tight">Técnico</h3>
                    </div>
                    <p class="mt-3 text-sm leading-relaxed text-ink-muted">Gestiona las órdenes de trabajo, los repuestos y los estados del taller.</p>
                    <ul class="mt-5 flex flex-wrap gap-1.5">
                        <li class="rounded-full bg-surface-2 px-2.5 py-1 text-xs text-ink-muted">Servicio técnico</li>
                        <li class="rounded-full bg-surface-2 px-2.5 py-1 text-xs text-ink-muted">Disponibilidad</li>
                        <li class="rounded-full bg-surface-2 px-2.5 py-1 text-xs text-ink-muted">Buscador</li>
                    </ul>
                </article>
            </div>

            <div class="mt-4 grid gap-4 rounded-xl border border-hairline bg-surface-2 p-5 sm:grid-cols-3">
                <div class="flex gap-3">
                    <x-icon name="lock" class="mt-0.5 size-4 shrink-0 text-ink-muted" />
                    <div>
                        <p class="text-sm font-medium">Una sesión activa</p>
                        <p class="mt-1 text-xs leading-relaxed text-ink-muted">Al ingresar en otro equipo se cierra la sesión anterior.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <x-icon name="shield" class="mt-0.5 size-4 shrink-0 text-ink-muted" />
                    <div>
                        <p class="text-sm font-medium">Permisos por rol</p>
                        <p class="mt-1 text-xs leading-relaxed text-ink-muted">Cada persona ve sólo los módulos que le corresponden.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <x-icon name="list" class="mt-0.5 size-4 shrink-0 text-ink-muted" />
                    <div>
                        <p class="text-sm font-medium">Kardex completo</p>
                        <p class="mt-1 text-xs leading-relaxed text-ink-muted">Cada movimiento de stock queda registrado con su referencia.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA final --}}
    <section class="landing-section pt-0">
        <div class="landing-container">
            <div class="relative overflow-hidden rounded-2xl border border-hairline bg-surface-1 p-8 sm:p-12 lg:p-16">
                <div class="max-w-2xl">
                    <p class="landing-eyebrow">Acceso al sistema</p>
                    <h2 class="landing-h2 mt-4">La otra sucursal, a un clic.</h2>
                    <p class="landing-lead mt-4">
                        Ingresa con tu usuario para consultar disponibilidad, registrar movimientos y atender más rápido. El acceso es exclusivo para el personal de M&amp;A Computers.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('login') }}" class="btn btn-primary">
                            Ingresar al sistema
                            <x-icon name="chevron-right" class="size-4" />
                        </a>
                        <a href="#modulos" class="btn btn-secondary">Repasar los módulos</a>
                    </div>
                </div>
                <div class="pointer-events-none absolute -right-10 -bottom-12 hidden lg:block" aria-hidden="true">
                    <x-icon name="warehouse" class="size-64 text-brand-soft" />
                </div>
            </div>
        </div>
    </section>
@endsection
