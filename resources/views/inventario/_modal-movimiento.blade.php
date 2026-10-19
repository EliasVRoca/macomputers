<dialog id="modal-movimiento" class="modal">
    <form method="POST" action="{{ route('inventario.movimientos.store') }}" data-preview-movimiento>
        @csrf
        <div class="modal-cabecera">
            <div>
                <h2 class="text-base font-medium">Registrar movimiento</h2>
                <p class="text-xs text-ink-muted">Actualiza el stock de una sucursal.</p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon" data-modal-cerrar aria-label="Cerrar"><x-icon name="x" class="size-5" /></button>
        </div>

        <div class="modal-cuerpo">
            <div>
                <label for="mov-producto" class="label">Producto</label>
                <select id="mov-producto" name="producto_id" class="input" required data-preview-producto>
                    <option value="">Seleccione…</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}" @selected(old('producto_id') == $producto->id)>{{ $producto->nombre }} ({{ $producto->codigo }})</option>
                    @endforeach
                </select>
                @error('producto_id') <p class="error-texto">{{ $message }}</p> @enderror
            </div>

            @if ($puedeElegirSucursal)
                <div>
                    <label for="mov-sucursal" class="label">Sucursal</label>
                    <select id="mov-sucursal" name="sucursal_id" class="input" required data-preview-sucursal>
                        @foreach ($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}" @selected(old('sucursal_id') == $sucursal->id)>{{ $sucursal->nombre }}</option>
                        @endforeach
                    </select>
                    @error('sucursal_id') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
            @else
                <input type="hidden" name="sucursal_id" value="{{ auth()->user()->sucursal_id }}" data-preview-sucursal>
                <p class="text-sm text-ink-muted">Sucursal: <span class="font-medium text-ink">{{ auth()->user()->sucursal?->nombre }}</span></p>
            @endif

            <div>
                <label for="mov-tipo" class="label">Tipo de movimiento</label>
                <select id="mov-tipo" name="tipo" class="input" required data-preview-tipo>
                    <option value="entrada" @selected(old('tipo') === 'entrada')>Entrada — suma unidades (reposición)</option>
                    <option value="salida" @selected(old('tipo') === 'salida')>Salida — resta unidades (merma, uso interno)</option>
                    <option value="ajuste" @selected(old('tipo') === 'ajuste')>Ajuste / conteo — deja la cantidad exacta</option>
                </select>
                @error('tipo') <p class="error-texto">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="mov-cantidad" class="label">Cantidad</label>
                <input id="mov-cantidad" name="cantidad" type="number" min="0" class="input" value="{{ old('cantidad') }}" required data-preview-cantidad>
                @error('cantidad') <p class="error-texto">{{ $message }}</p> @enderror
                @error('stock') <p class="error-texto">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-lg bg-surface-2 px-3 py-2 text-sm text-ink-muted" data-preview-salida aria-live="polite">
                Elige producto, sucursal y cantidad para ver el saldo resultante.
            </div>

            <div>
                <label for="mov-referencia" class="label">Motivo o referencia <span class="font-normal text-ink-subtle">(opcional)</span></label>
                <input id="mov-referencia" name="referencia" class="input" value="{{ old('referencia') }}" maxlength="150">
                @error('referencia') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="modal-pie">
            <button type="button" class="btn btn-secondary" data-modal-cerrar>Cancelar</button>
            <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar movimiento</button>
        </div>
    </form>
</dialog>
