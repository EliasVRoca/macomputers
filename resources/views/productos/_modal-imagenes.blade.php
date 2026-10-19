@php
    $nombreProducto = trim($producto->nombre);
    $marcaProducto = trim($producto->marca);
    $consultaBase = $marcaProducto === '' || \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($nombreProducto), \Illuminate\Support\Str::lower($marcaProducto))
        ? $nombreProducto
        : $marcaProducto.' '.$nombreProducto;
    $consultaImagenes = \Illuminate\Support\Str::limit(
        preg_replace('/\s+/u', ' ', $consultaBase),
        100,
        ''
    );
@endphp

<dialog id="modal-imagenes-internet" class="modal modal-ancho"
        aria-labelledby="imagenes-internet-titulo"
        data-url-buscar="{{ route('productos.imagenes.buscar', $producto) }}"
        data-url-guardar="{{ route('productos.imagenes.internet', $producto) }}">
    <div class="flex max-h-[90vh] flex-col">
        <div class="modal-cabecera">
            <div>
                <h2 id="imagenes-internet-titulo" class="text-base font-medium">Buscar imágenes en internet</h2>
                <p class="text-xs text-ink-muted">Elegí hasta 5; se guarda una copia optimizada con su fuente.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon" data-modal-cerrar aria-label="Cerrar"><x-icon name="x" class="size-5" /></button>
        </div>

        <div class="modal-cuerpo min-h-0 flex-1 overflow-y-auto">
            <div class="flex flex-col gap-2 sm:flex-row">
                <div class="buscador-input flex-1 py-2.5">
                    <x-icon name="search" class="size-4 shrink-0 text-ink-tertiary" />
                    <input type="search" data-imagenes-consulta value="{{ $consultaImagenes }}" placeholder="Ej. Lenovo IdeaPad 3 15IAU7" maxlength="100" aria-label="Buscar imágenes">
                </div>
                <button type="button" class="btn btn-primary" data-imagenes-buscar><x-icon name="search" class="size-4" /> Buscar</button>
            </div>

            <p data-imagenes-estado class="text-xs text-ink-muted" role="status" aria-live="polite"></p>

            <div data-imagenes-resultados class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"></div>

            <p class="text-[11px] leading-relaxed text-ink-subtle">
                Las imágenes son de terceros. Se guarda la fuente y, si está disponible, la licencia. Verificá que tengas derecho a usarlas.
                Resultados de <a data-imagenes-atribucion href="https://commons.wikimedia.org/" target="_blank" rel="noopener noreferrer" class="text-brand hover:underline">Wikimedia Commons</a>.
            </p>
        </div>

        <div class="modal-pie flex-wrap items-center justify-between gap-2">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" data-imagenes-principal class="h-4 w-4 rounded border-hairline accent-brand" @checked($imagenes->isEmpty())>
                Usar la primera como principal
            </label>
            <div class="flex flex-wrap items-center gap-2">
                <span data-imagenes-contador class="text-xs text-ink-muted">0 seleccionadas</span>
                <button type="button" class="btn btn-secondary" data-modal-cerrar>Cancelar</button>
                <button type="button" class="btn btn-primary" data-imagenes-agregar disabled><x-icon name="plus" class="size-4" /> Agregar</button>
            </div>
        </div>
    </div>
</dialog>
