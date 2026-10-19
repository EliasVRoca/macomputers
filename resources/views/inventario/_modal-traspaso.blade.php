<dialog id="modal-traspaso" class="modal" data-gerente="{{ $puedeElegirSucursal ? '1' : '0' }}">
    <form method="POST" action="{{ route('inventario.traer') }}">
        @csrf
        <div class="modal-cabecera">
            <div>
                <h2 class="text-base font-medium">Traer de la otra sucursal</h2>
                <p class="text-xs text-ink-muted">Se registra un traspaso: sale del origen y entra al destino.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon" data-modal-cerrar aria-label="Cerrar"><x-icon name="x" class="size-5" /></button>
        </div>

        <div class="modal-cuerpo">
            <input type="hidden" name="producto_id" value="">
            <p class="text-sm">Producto: <span class="font-medium" data-traspaso-nombre></span></p>

            <div>
                <label for="trap-origen" class="label">Desde (sucursal que tiene stock)</label>
                <select id="trap-origen" name="sucursal_origen_id" class="input" required></select>
                @error('sucursal_origen_id') <p class="error-texto">{{ $message }}</p> @enderror
            </div>

            @if ($puedeElegirSucursal)
                <div>
                    <label for="trap-destino" class="label">Hacia</label>
                    <select id="trap-destino" name="sucursal_destino_id" class="input" required>
                        @foreach ($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                        @endforeach
                    </select>
                    @error('sucursal_destino_id') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
            @else
                <input type="hidden" name="sucursal_destino_id" value="{{ auth()->user()->sucursal_id }}">
                <p class="text-sm">Hacia: <span class="font-medium">{{ auth()->user()->sucursal?->nombre }}</span></p>
            @endif

            <div>
                <label for="trap-cantidad" class="label">Cantidad</label>
                <input id="trap-cantidad" name="cantidad" type="number" min="1" value="1" class="input" required>
                @error('cantidad') <p class="error-texto">{{ $message }}</p> @enderror
                @error('stock') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="modal-pie">
            <button type="button" class="btn btn-secondary" data-modal-cerrar>Cancelar</button>
            <button type="submit" class="btn btn-primary"><x-icon name="arrow-left-right" class="size-4" /> Traer</button>
        </div>
    </form>
</dialog>
