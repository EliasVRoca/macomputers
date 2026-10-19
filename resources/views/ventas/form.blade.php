@extends('layouts.app')

@section('titulo', 'Nueva venta')
@section('encabezado', 'Nueva venta')
@section('subtitulo', 'El precio se toma del catálogo y se muestra el stock de la sucursal. El stock se descuenta al guardar.')
@section('acciones')
    <a href="{{ route('ventas.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<form method="POST" action="{{ route('ventas.store') }}" class="card card-pad max-w-4xl space-y-8">
    @csrf

    <section class="space-y-5">
        <h2 class="text-sm font-medium text-ink-muted">Datos de la venta</h2>
        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <label for="sucursal_id" class="label">Sucursal</label>
                <select id="sucursal_id" name="sucursal_id" class="input" {{ $puedeElegirSucursal ? 'required' : 'disabled' }}>
                    @foreach ($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $sucursales->first()->id) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                    @endforeach
                </select>
                @error('sucursal_id') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="medio_pago" class="label">Medio de pago</label>
                <select id="medio_pago" name="medio_pago" class="input" required>
                    @foreach ($medios as $medio)
                        <option value="{{ $medio }}" @selected(old('medio_pago') === $medio)>{{ $medio }}</option>
                    @endforeach
                </select>
                @error('medio_pago') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="space-y-4 rounded-lg border border-hairline-soft p-4">
            <input type="hidden" name="modo_cliente" id="modo_cliente" value="{{ old('modo_cliente', 'existente') }}">
            <div class="flex items-center gap-2">
                <input type="checkbox" id="cliente-nuevo" class="h-4 w-4 accent-brand" @checked(old('modo_cliente') === 'nuevo')>
                <label for="cliente-nuevo" class="text-sm text-ink-muted">Registrar un cliente nuevo</label>
            </div>

            <div id="bloque-cliente-existente">
                <label for="cliente_id" class="label">Cliente <span class="font-normal text-ink-subtle">(opcional)</span></label>
                <select id="cliente_id" name="cliente_id" class="input">
                    <option value="">Sin cliente registrado</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->nombre }}</option>
                    @endforeach
                </select>
                @error('cliente_id') <p class="error-texto">{{ $message }}</p> @enderror
            </div>

            <div id="bloque-cliente-nuevo" class="hidden grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-3">
                    <label for="cliente_nombre" class="label">Nombre completo</label>
                    <input id="cliente_nombre" name="cliente_nombre" class="input" value="{{ old('cliente_nombre') }}" maxlength="150" placeholder="Ej. Juan Pérez">
                    @error('cliente_nombre') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="cliente_telefono" class="label">Teléfono</label>
                    <input id="cliente_telefono" name="cliente_telefono" class="input" value="{{ old('cliente_telefono') }}" maxlength="30">
                    @error('cliente_telefono') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="cliente_carnet" class="label">Carnet</label>
                    <input id="cliente_carnet" name="cliente_carnet" class="input" value="{{ old('cliente_carnet') }}" maxlength="30">
                    @error('cliente_carnet') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </section>

    <section class="space-y-4 border-t border-hairline pt-8">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-medium text-ink-muted">Productos</h2>
            <button type="button" id="agregar-fila" class="btn btn-secondary btn-sm"><x-icon name="plus" class="size-4" /> Agregar producto</button>
        </div>
        <div id="filas" class="space-y-3"></div>
        @error('items') <p class="error-texto">{{ $message }}</p> @enderror
        @error('stock') <p class="error-texto">{{ $message }}</p> @enderror

        <div class="flex items-center justify-end gap-3 border-t border-hairline-soft pt-4 text-sm">
            <span class="text-ink-muted">Total</span>
            <span id="total" class="text-lg font-semibold tabular-nums">Bs 0,00</span>
        </div>
    </section>

    <template id="fila-plantilla">
        <div class="fila grid items-end gap-3 rounded-lg border border-hairline-soft p-3 sm:grid-cols-12">
            <div class="sm:col-span-7">
                <label class="label">Producto</label>
                <select name="items[__IDX__][producto_id]" class="input" data-producto required>
                    <option value="">Seleccione…</option>
                    @foreach ($productos as $producto)
                        <option value="{{ $producto->id }}" data-precio="{{ $producto->precio_venta }}">{{ $producto->nombre }} · Bs {{ number_format($producto->precio_venta, 2, ',', '.') }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="label">Cantidad</label>
                <input name="items[__IDX__][cantidad]" type="number" min="1" value="1" class="input" data-cantidad required>
            </div>
            <div class="sm:col-span-2 text-right">
                <span class="label">Subtotal</span>
                <span class="block py-2 text-sm font-medium tabular-nums" data-subtotal>Bs 0,00</span>
            </div>
            <div class="sm:col-span-1">
                <button type="button" data-quitar class="btn btn-ghost btn-icon w-full text-danger" aria-label="Quitar producto"><x-icon name="trash" class="size-4" /></button>
            </div>
            <div class="sm:col-span-12" data-aviso></div>
        </div>
    </template>

    <div class="flex gap-2 border-t border-hairline pt-6">
        <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar y emitir nota</button>
        <a href="{{ route('ventas.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<script>
    (function () {
        var stockMapa = @json($stockMapa);
        var puedeTraspasar = @json($puedeTraspasar);
        var traspasoUrl = @json(route('ventas.traer'));
        var csrf = @json(csrf_token());
        var sucursal = document.getElementById('sucursal_id');
        var contenedor = document.getElementById('filas');
        var plantilla = document.getElementById('fila-plantilla');
        var totalEl = document.getElementById('total');
        var indice = 0;

        function money(n) { return 'Bs ' + n.toFixed(2).replace('.', ','); }
        function stockDe(pid, sid) { return pid && stockMapa[pid] && stockMapa[pid][sid] ? parseInt(stockMapa[pid][sid], 10) : 0; }
        function primeraOtraSucursal(pid, sid) {
            var s = stockMapa[pid] || {};
            for (var k in s) { if (String(k) !== String(sid) && parseInt(s[k], 10) > 0) return k; }
            return null;
        }

        function recalcular() {
            var total = 0;
            contenedor.querySelectorAll('.fila').forEach(function (fila) {
                var sel = fila.querySelector('[data-producto]');
                var cant = fila.querySelector('[data-cantidad]');
                var opcion = sel.options[sel.selectedIndex];
                var precio = opcion && opcion.dataset.precio ? parseFloat(opcion.dataset.precio) : 0;
                var cantidad = parseInt(cant.value, 10) || 0;
                var subtotal = precio * cantidad;
                total += subtotal;
                fila.querySelector('[data-subtotal]').textContent = money(subtotal);

                var aviso = fila.querySelector('[data-aviso]');
                aviso.innerHTML = '';
                if (!sel.value) return;

                var sid = sucursal ? sucursal.value : null;
                var disponible = stockDe(sel.value, sid);
                if (disponible < cantidad) {
                    var p = document.createElement('p');
                    p.className = 'error-texto';
                    p.textContent = 'Stock insuficiente en la sucursal (disponible: ' + disponible + ').';
                    aviso.appendChild(p);
                    if (puedeTraspasar) {
                        var origen = primeraOtraSucursal(sel.value, sid);
                        if (origen) {
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            btn.className = 'btn btn-secondary btn-sm mt-1';
                            btn.textContent = 'Traer de la otra sucursal';
                            btn.addEventListener('click', function () { traer(fila, sel.value, cantidad || 1, origen, sid, btn); });
                            aviso.appendChild(btn);
                        }
                    }
                }
            });
            totalEl.textContent = money(total);
        }

        function traer(fila, pid, cantidad, origen, destino, btn) {
            btn.disabled = true;
            fetch(traspasoUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ producto_id: pid, cantidad: cantidad, sucursal_origen_id: origen, sucursal_destino_id: destino }),
            }).then(function (r) {
                return r.json().then(function (data) { return { ok: r.ok, data: data }; });
            }).then(function (res) {
                if (!res.ok) {
                    var msg = res.data && res.data.message ? res.data.message : 'No se pudo traer el producto.';
                    alert(msg);
                    btn.disabled = false;
                    return;
                }
                if (!stockMapa[pid]) stockMapa[pid] = {};
                stockMapa[pid][destino] = res.data.stock;
                if (stockMapa[pid][origen] !== undefined) stockMapa[pid][origen] = Math.max(0, stockMapa[pid][origen] - cantidad);
                recalcular();
            }).catch(function () { alert('No se pudo conectar con el servidor.'); btn.disabled = false; });
        }

        function agregar() {
            contenedor.insertAdjacentHTML('beforeend', plantilla.innerHTML.replaceAll('__IDX__', indice++));
            recalcular();
        }

        document.getElementById('agregar-fila').addEventListener('click', agregar);
        contenedor.addEventListener('input', recalcular);
        contenedor.addEventListener('change', recalcular);
        contenedor.addEventListener('click', function (e) {
            if (e.target.closest('[data-quitar]')) {
                e.target.closest('.fila').remove();
                recalcular();
            }
        });
        if (sucursal) sucursal.addEventListener('change', recalcular);

        var nuevo = document.getElementById('cliente-nuevo');
        var modo = document.getElementById('modo_cliente');
        function alternarCliente() {
            var esNuevo = nuevo.checked;
            modo.value = esNuevo ? 'nuevo' : 'existente';
            document.getElementById('bloque-cliente-existente').classList.toggle('hidden', esNuevo);
            document.getElementById('bloque-cliente-nuevo').classList.toggle('hidden', !esNuevo);
            document.getElementById('bloque-cliente-nuevo').classList.toggle('grid', esNuevo);
        }
        nuevo.addEventListener('change', alternarCliente);
        alternarCliente();
        agregar();
    })();
</script>
@endsection
