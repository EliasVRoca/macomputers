<div class="landing-mockup" role="img" aria-label="Vista del panel de inicio del sistema: ventas del día, bajo mínimo, servicio técnico, traspasos y búsquedas sin resultado.">
    <div aria-hidden="true">
        <div class="flex items-center justify-between gap-2 border-b border-hairline px-3 py-2.5 sm:px-4">
            <div class="flex min-w-0 items-center gap-2">
                <span class="flex h-6 w-6 shrink-0 items-center justify-center overflow-hidden rounded-md border border-hairline bg-white">
                    <img src="{{ asset('images/logo-mark.jpg') }}" alt="" class="h-full w-full object-cover">
                </span>
                <span class="truncate text-xs font-medium">Panel de inicio</span>
                <span class="badge badge-brand hidden sm:inline-flex"><span class="dot"></span>Todas las sucursales</span>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <span class="hidden items-center gap-1.5 rounded-md border border-hairline bg-canvas px-2 py-1 text-[10px] text-ink-tertiary sm:flex">
                    <x-icon name="search" class="size-3" /> Buscar
                    <span class="kbd border-0 bg-surface-2 px-1 py-0 text-[9px]">Ctrl K</span>
                </span>
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand text-[10px] font-medium text-on-primary">M</span>
            </div>
        </div>

        <div class="space-y-3 p-3 sm:p-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-sm font-medium tracking-tight">Resumen del día</p>
                    <p class="text-[11px] text-ink-muted">Ventas, stock y servicio técnico en una vista</p>
                </div>
                <span class="badge badge-ok"><span class="dot"></span>Hoy</span>
            </div>

            <div class="grid grid-cols-2 gap-2.5">
                <div class="landing-mockup-kpi">
                    <div class="flex items-center gap-2 text-[11px] text-ink-muted">
                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-surface-1 text-ink-muted">
                            <x-icon name="receipt" class="size-3.5" />
                        </span>
                        Ventas del día
                    </div>
                    <p class="mt-2 text-lg font-semibold tabular-nums">Bs 4.860,00</p>
                    <p class="mt-0.5 text-[10px] text-ink-muted">3 ventas · solo productos</p>
                </div>

                <div class="landing-mockup-kpi border-danger/40">
                    <div class="flex items-center gap-2 text-[11px] text-ink-muted">
                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-danger-soft text-danger">
                            <x-icon name="alert-triangle" class="size-3.5" />
                        </span>
                        Bajo mínimo
                    </div>
                    <p class="mt-2 text-lg font-semibold tabular-nums text-danger">3</p>
                    <p class="mt-0.5 text-[10px] text-ink-muted">Productos por reponer</p>
                </div>

                <div class="landing-mockup-kpi">
                    <div class="flex items-center gap-2 text-[11px] text-ink-muted">
                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-surface-1 text-ink-muted">
                            <x-icon name="wrench" class="size-3.5" />
                        </span>
                        Servicio técnico
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-1.5">
                        <div class="rounded-md bg-surface-1 px-2 py-1.5">
                            <p class="text-[9px] text-ink-muted">Pendientes</p>
                            <p class="text-sm font-semibold tabular-nums">4</p>
                        </div>
                        <div class="rounded-md bg-surface-1 px-2 py-1.5">
                            <p class="text-[9px] text-ink-muted">Listas</p>
                            <p class="text-sm font-semibold tabular-nums">2</p>
                        </div>
                    </div>
                </div>

                <div class="landing-mockup-kpi">
                    <div class="flex items-center gap-2 text-[11px] text-ink-muted">
                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-surface-1 text-ink-muted">
                            <x-icon name="arrow-left-right" class="size-3.5" />
                        </span>
                        Traspasos de hoy
                    </div>
                    <p class="mt-2 text-lg font-semibold tabular-nums">6</p>
                    <p class="mt-0.5 text-[10px] text-ink-muted">Entre sucursales</p>
                </div>
            </div>

            <div class="rounded-lg border border-hairline bg-canvas/70 p-3">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-[11px] font-medium">Disponibilidad</p>
                    <span class="text-[10px] text-ink-muted">Buscador</span>
                </div>
                <div class="mt-2 flex items-center gap-2.5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-hairline bg-surface-1 text-ink-tertiary">
                        <x-icon name="package" class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-medium">Teclado mecánico 60% · KZ-60</p>
                        <div class="stock-chips mt-1.5">
                            <span class="stock-chip local">Principal · 8</span>
                            <span class="stock-chip">Segunda · 3</span>
                        </div>
                    </div>
                    <span class="badge badge-ok hidden shrink-0 sm:inline-flex">En stock</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div class="flex items-center justify-between gap-2 rounded-md bg-surface-2 px-3 py-2">
                    <span class="truncate text-[10px] text-ink-muted">Búsquedas sin resultado</span>
                    <span class="text-sm font-semibold tabular-nums">2</span>
                </div>
                <div class="flex items-center justify-between gap-2 rounded-md bg-surface-2 px-3 py-2">
                    <span class="truncate text-[10px] text-ink-muted">Clientes atendidos</span>
                    <span class="text-sm font-semibold tabular-nums">18</span>
                </div>
            </div>
        </div>
    </div>
</div>
