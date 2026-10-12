@extends('layouts.app')

@section('titulo', $servicio->exists ? 'Actualizar servicio' : 'Nuevo servicio')
@section('encabezado', $servicio->exists ? 'Actualizar servicio' : 'Nuevo servicio técnico')
@section('subtitulo', 'Mantenimiento y reparaciones simples, registrados como venta de servicio.')
@section('acciones')
    <a href="{{ route('servicios.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<form method="POST" action="{{ $accion }}" class="card card-pad max-w-2xl space-y-8">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    @if (! $servicio->exists)
        <section class="space-y-5">
            <h2 class="text-sm font-medium text-ink-muted">Cliente y sucursal</h2>

            <div>
                <span class="label">Cliente</span>
                <div class="mb-3 flex flex-wrap gap-2">
                    <label class="cursor-pointer rounded-md border border-hairline px-3 py-2 text-sm transition-colors hover:bg-surface-2 has-[:checked]:border-brand has-[:checked]:bg-brand-soft has-[:checked]:font-medium has-[:checked]:text-brand">
                        <input type="radio" name="modo_cliente" value="existente" class="sr-only" @checked(old('modo_cliente', 'existente') === 'existente')>
                        Cliente registrado
                    </label>
                    <label class="cursor-pointer rounded-md border border-hairline px-3 py-2 text-sm transition-colors hover:bg-surface-2 has-[:checked]:border-brand has-[:checked]:bg-brand-soft has-[:checked]:font-medium has-[:checked]:text-brand">
                        <input type="radio" name="modo_cliente" value="nuevo" class="sr-only" @checked(old('modo_cliente') === 'nuevo')>
                        Cliente nuevo
                    </label>
                </div>

                <div data-cliente="existente">
                    <select id="cliente_id" name="cliente_id" class="input" required>
                        <option value="">Seleccione…</option>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}" @selected(old('cliente_id') == $cliente->id)>{{ $cliente->nombre }}</option>
                        @endforeach
                    </select>
                    @error('cliente_id') <p class="error-texto">{{ $message }}</p> @enderror
                </div>

                <div data-cliente="nuevo" hidden class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="cliente_nombre" class="label">Nombre completo</label>
                        <input id="cliente_nombre" name="cliente_nombre" class="input" value="{{ old('cliente_nombre') }}" maxlength="150" placeholder="Ej. Juan Pérez">
                        @error('cliente_nombre') <p class="error-texto">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="cliente_telefono" class="label">Teléfono</label>
                        <input id="cliente_telefono" name="cliente_telefono" class="input" value="{{ old('cliente_telefono') }}" maxlength="30" placeholder="Ej. 70011223">
                        @error('cliente_telefono') <p class="error-texto">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="cliente_carnet" class="label">Carnet <span class="font-normal text-ink-subtle">(opcional)</span></label>
                        <input id="cliente_carnet" name="cliente_carnet" class="input" value="{{ old('cliente_carnet') }}" maxlength="30">
                        @error('cliente_carnet') <p class="error-texto">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div>
                <label for="sucursal_id" class="label">Sucursal</label>
                <select id="sucursal_id" name="sucursal_id" class="input" {{ $puedeElegirSucursal ? 'required' : 'disabled' }}>
                    @foreach ($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $sucursales->first()->id) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </section>
    @else
        <p class="rounded-lg bg-surface-2 px-4 py-3 text-sm">
            Cliente: <span class="font-medium">{{ $servicio->cliente?->nombre }}</span> ·
            Sucursal: <span class="font-medium">{{ $servicio->sucursal?->nombre }}</span>
        </p>
    @endif

    <section class="space-y-5">
        <h2 class="text-sm font-medium text-ink-muted">Equipo y diagnóstico</h2>
        <div class="space-y-5">
            <div>
                <label for="equipo" class="label">Equipo</label>
                <input id="equipo" name="equipo" class="input" value="{{ old('equipo', $servicio->equipo) }}" required maxlength="150" placeholder="Ej. Laptop Lenovo IdeaPad 3">
                @error('equipo') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="diagnostico" class="label">Diagnóstico</label>
                <textarea id="diagnostico" name="diagnostico" rows="3" class="input" maxlength="1000" placeholder="Describa la falla o el trabajo a realizar">{{ old('diagnostico', $servicio->diagnostico) }}</textarea>
                @error('diagnostico') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="space-y-5 border-t border-hairline pt-8">
        <h2 class="text-sm font-medium text-ink-muted">Costo y estado</h2>
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="costo" class="label">Costo (Bs)</label>
                <input id="costo" name="costo" type="number" step="0.01" min="0" class="input" value="{{ old('costo', $costo) }}" required>
                @error('costo') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="estado" class="label">Estado</label>
                <select id="estado" name="estado" class="input" required>
                    @foreach (\App\Models\Servicio::ESTADOS as $clave => $texto)
                        <option value="{{ $clave }}" @selected(old('estado', $servicio->estado) === $clave)>{{ $texto }}</option>
                    @endforeach
                </select>
                @error('estado') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <div class="flex gap-2 border-t border-hairline pt-6">
        <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> Guardar</button>
        <a href="{{ route('servicios.index') }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<script>
    (function () {
        var radios = document.querySelectorAll('input[name="modo_cliente"]');

        if (radios.length === 0) {
            return;
        }

        function actualizar() {
            var marcado = document.querySelector('input[name="modo_cliente"]:checked') || radios[0];
            var modo = marcado.value;

            document.querySelectorAll('[data-cliente]').forEach(function (grupo) {
                var visible = grupo.dataset.cliente === modo;
                grupo.hidden = !visible;

                // El grupo oculto se desactiva: no se envía ni bloquea la validación del navegador.
                grupo.querySelectorAll('input, select').forEach(function (campo) {
                    campo.disabled = !visible;
                });
            });
        }

        radios.forEach(function (radio) {
            radio.addEventListener('change', actualizar);
        });

        actualizar();
    })();
</script>
@endsection
