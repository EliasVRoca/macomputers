@extends('layouts.app')

@php
    $esAlta = ! $servicio->exists;
@endphp

@section('titulo', $esAlta ? 'Nueva orden de servicio' : 'Editar '.$servicio->numero)
@section('encabezado', $esAlta ? 'Nueva orden de servicio' : 'Editar datos de '.$servicio->numero)
@section('subtitulo', $esAlta
    ? 'Registre la recepción del equipo. La orden nace en estado «Recibido».'
    : 'Corrija los datos de la orden. El estado se cambia desde la ficha de la orden.')
@section('acciones')
    <a href="{{ $esAlta ? route('servicios.index') : route('servicios.show', $servicio) }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<form method="POST" action="{{ $accion }}" class="card card-pad max-w-3xl space-y-8">
    @csrf
    @method($metodo === 'PUT' ? 'PUT' : 'POST')

    @if ($esAlta)
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

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="sucursal_id" class="label">Sucursal</label>
                    <select id="sucursal_id" name="sucursal_id" class="input" {{ $puedeElegirSucursal ? 'required' : 'disabled' }}>
                        @foreach ($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $sucursales->first()?->id) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                        @endforeach
                    </select>
                    @error('sucursal_id') <p class="error-texto">{{ $message }}</p> @enderror
                </div>

                @if ($puedeElegirTecnico && $tecnicos->isNotEmpty())
                    <div>
                        <label for="tecnico_id" class="label">Técnico asignado</label>
                        <select id="tecnico_id" name="tecnico_id" class="input">
                            @foreach ($tecnicos as $tecnico)
                                <option value="{{ $tecnico->id }}" @selected(old('tecnico_id') == $tecnico->id)>{{ $tecnico->nombre() }}</option>
                            @endforeach
                        </select>
                        @error('tecnico_id') <p class="error-texto">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
        </section>
    @else
        <section class="space-y-5">
            <h2 class="text-sm font-medium text-ink-muted">Cliente, sucursal y técnico</h2>
            <p class="rounded-lg bg-surface-2 px-4 py-3 text-sm">
                Estado actual: <span class="font-medium">{{ $servicio->estadoEnum()->etiqueta() }}</span>
            </p>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="cliente_id" class="label">Cliente</label>
                    @if ($puedeElegirCliente ?? false)
                        <select id="cliente_id" name="cliente_id" class="input">
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}" @selected(old('cliente_id', $servicio->cliente_id) == $cliente->id)>{{ $cliente->nombre }}</option>
                            @endforeach
                        </select>
                        @error('cliente_id') <p class="error-texto">{{ $message }}</p> @enderror
                    @else
                        <p class="font-medium">{{ $servicio->cliente?->nombre ?? '—' }}</p>
                    @endif
                </div>
                <div>
                    <span class="label">Sucursal</span>
                    @if ($puedeElegirSucursal ?? false)
                        <select id="sucursal_id" name="sucursal_id" class="input">
                            @foreach ($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $servicio->sucursal_id) == $sucursal->id)>{{ $sucursal->nombre }}</option>
                            @endforeach
                        </select>
                        @error('sucursal_id') <p class="error-texto">{{ $message }}</p> @enderror
                    @else
                        <p class="font-medium">{{ $servicio->sucursal?->nombre }}</p>
                    @endif
                </div>
                <div>
                    <span class="label">Técnico</span>
                    @if (($puedeElegirTecnico ?? false) && $tecnicos->isNotEmpty())
                        <select id="tecnico_id" name="tecnico_id" class="input">
                            @foreach ($tecnicos as $tecnico)
                                <option value="{{ $tecnico->id }}" @selected(old('tecnico_id', $servicio->tecnico_id) == $tecnico->id)>{{ $tecnico->nombre() }}</option>
                            @endforeach
                        </select>
                        @error('tecnico_id') <p class="error-texto">{{ $message }}</p> @enderror
                    @else
                        <p class="font-medium">{{ $servicio->tecnico?->nombre() ?? '—' }}</p>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <section class="space-y-5 border-t border-hairline pt-8">
        <h2 class="text-sm font-medium text-ink-muted">Equipo y trabajo</h2>
        <div>
            <label for="equipo" class="label">Equipo</label>
            <input id="equipo" name="equipo" class="input" value="{{ old('equipo', $servicio->equipo) }}" required maxlength="150" placeholder="Ej. Laptop Lenovo IdeaPad 3">
            @error('equipo') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="diagnostico" class="label">Falla reportada y diagnóstico</label>
            <textarea id="diagnostico" name="diagnostico" rows="3" class="input" maxlength="1000" placeholder="Describa la falla reportada y el trabajo a realizar">{{ old('diagnostico', $servicio->diagnostico) }}</textarea>
            @error('diagnostico') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="accesorios_recibidos" class="label">Accesorios recibidos <span class="font-normal text-ink-subtle">(opcional)</span></label>
            <input id="accesorios_recibidos" name="accesorios_recibidos" class="input" value="{{ old('accesorios_recibidos', $servicio->accesorios_recibidos) }}" maxlength="500" placeholder="Ej. Cargador, mouse, funda">
            @error('accesorios_recibidos') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
    </section>

    <section class="space-y-5 border-t border-hairline pt-8">
        <h2 class="text-sm font-medium text-ink-muted">Programación</h2>
        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <label for="fecha_recepcion" class="label">Recepción</label>
                <input id="fecha_recepcion" name="fecha_recepcion" type="date" class="input" value="{{ old('fecha_recepcion', $servicio->fecha_recepcion?->toDateString() ?? now()->toDateString()) }}" required>
                @error('fecha_recepcion') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="fecha_prometida" class="label">Prometida <span class="font-normal text-ink-subtle">(opcional)</span></label>
                <input id="fecha_prometida" name="fecha_prometida" type="date" class="input" value="{{ old('fecha_prometida', $servicio->fecha_prometida?->toDateString()) }}">
                @error('fecha_prometida') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="prioridad" class="label">Prioridad</label>
                <select id="prioridad" name="prioridad" class="input" required>
                    @foreach (\App\Models\Servicio::PRIORIDADES as $clave => $etiqueta)
                        <option value="{{ $clave }}" @selected(old('prioridad', $servicio->prioridad) === $clave)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                @error('prioridad') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="space-y-5 border-t border-hairline pt-8">
        <h2 class="text-sm font-medium text-ink-muted">Costo y garantía</h2>
        <p class="text-sm text-ink-muted">El total suma la mano de obra y los repuestos que se agreguen desde la ficha de la orden.</p>
        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <label for="mano_de_obra" class="label">Mano de obra (Bs)</label>
                <input id="mano_de_obra" name="mano_de_obra" type="number" step="0.01" min="0" class="input" value="{{ old('mano_de_obra', $servicio->mano_de_obra ?? 0) }}" required>
                @error('mano_de_obra') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
            @unless ($esAlta)
                <div>
                    <label for="descuento" class="label">Descuento (Bs)</label>
                    <input id="descuento" name="descuento" type="number" step="0.01" min="0" class="input" value="{{ old('descuento', $servicio->descuento ?? 0) }}">
                    @error('descuento') <p class="error-texto">{{ $message }}</p> @enderror
                </div>
            @endunless
            <div>
                <label for="garantia_dias" class="label">Garantía (días)</label>
                <input id="garantia_dias" name="garantia_dias" type="number" min="0" max="3650" class="input" value="{{ old('garantia_dias', $servicio->garantia_dias ?? 0) }}">
                @error('garantia_dias') <p class="error-texto">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label for="observaciones" class="label">Observaciones <span class="font-normal text-ink-subtle">(opcional)</span></label>
            <textarea id="observaciones" name="observaciones" rows="2" class="input" maxlength="1000">{{ old('observaciones', $servicio->observaciones) }}</textarea>
            @error('observaciones') <p class="error-texto">{{ $message }}</p> @enderror
        </div>
    </section>

    <div class="flex gap-2 border-t border-hairline pt-6">
        <button type="submit" class="btn btn-primary"><x-icon name="check" class="size-4" /> {{ $esAlta ? 'Registrar orden' : 'Guardar cambios' }}</button>
        <a href="{{ $esAlta ? route('servicios.index') : route('servicios.show', $servicio) }}" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

@if ($esAlta)
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
@endif
@endsection
