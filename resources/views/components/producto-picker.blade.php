@props([
    'name' => 'producto_id',
    'id' => null,
    'label' => 'Producto',
    'value' => null,
    'producto' => null,
    'stock' => null,
    'url',
    'sucursalId' => null,
    'sucursalSelector' => null,
    'limpiarAlCambiarSucursal' => true,
    'required' => false,
    'optional' => false,
    'ayuda' => true,
    'placeholder' => 'Buscar por nombre, código o marca…',
])

@php
    $seleccionadoId = $value ?? $producto?->id;
    $pickerId = $id ?? $name;

    $detalle = [];

    if ($producto) {
        if ($producto->codigo) {
            $detalle[] = $producto->codigo;
        }

        if ($producto->marca) {
            $detalle[] = $producto->marca;
        }

        $detalle[] = 'Bs '.number_format((float) $producto->precio_venta, 2, ',', '.');

        if ($stock !== null) {
            $detalle[] = (int) $stock > 0 ? 'Stock: '.(int) $stock : 'Sin stock';
        }
    }

    $detalleInicial = implode(' · ', $detalle);
    $sinStock = $stock !== null && (int) $stock <= 0;
@endphp

<div
    class="producto-picker"
    data-producto-picker
    data-url="{{ $url }}"
    data-sucursal="{{ $sucursalId }}"
    data-picker-requerido="{{ $required ? 'true' : 'false' }}"
    data-picker-limpiar-sucursal="{{ $limpiarAlCambiarSucursal ? '1' : '0' }}"
    @if ($sucursalSelector) data-sucursal-selector="{{ $sucursalSelector }}" @endif
>
    <label for="{{ $pickerId }}-buscar" class="label">
        {{ $label }}
        @if ($optional)
            <span class="font-normal text-ink-subtle">(opcional)</span>
        @endif
    </label>

    <div class="relative">
        <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-muted" />

        <input
            id="{{ $pickerId }}-buscar"
            type="text"
            class="input pr-10 pl-9"
            value="{{ $producto?->nombre }}"
            placeholder="{{ $placeholder }}"
            role="combobox"
            aria-autocomplete="list"
            aria-expanded="false"
            aria-haspopup="listbox"
            aria-controls="{{ $pickerId }}-lista"
            @if ($ayuda) aria-describedby="{{ $pickerId }}-ayuda" @endif
            aria-required="{{ $required ? 'true' : 'false' }}"
            autocomplete="off"
            spellcheck="false"
            data-picker-input
        >

        <button
            type="button"
            class="absolute top-1/2 right-1.5 flex size-7 -translate-y-1/2 items-center justify-center rounded-md text-ink-muted transition-colors hover:bg-surface-2 hover:text-ink {{ $seleccionadoId ? '' : 'hidden' }}"
            aria-label="Limpiar producto"
            data-picker-limpiar
        >
            <x-icon name="x" class="size-4" />
        </button>

        <div
            id="{{ $pickerId }}-lista"
            role="listbox"
            class="absolute top-full right-0 left-0 z-30 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border border-hairline bg-surface-1 p-1 shadow-xl"
            data-picker-lista
        ></div>
    </div>

    <input type="hidden" name="{{ $name }}" id="{{ $id ? $pickerId.'-valor' : $name }}" value="{{ $seleccionadoId }}" data-picker-value>

    <p
        class="mt-1.5 text-xs {{ $detalleInicial === '' ? 'hidden' : '' }} {{ $sinStock ? 'text-danger' : 'text-ink-subtle' }}"
        data-picker-resumen
    >{{ $detalleInicial }}</p>

    @if ($ayuda)
        <p id="{{ $pickerId }}-ayuda" class="mt-1 text-xs text-ink-subtle">
            Escribí al menos 2 letras. Usá ↑/↓ para navegar y Enter para elegir.
        </p>
    @endif

    <p class="mt-1 hidden text-xs text-danger" data-picker-error>Seleccioná un producto de la lista.</p>

    <p class="sr-only" aria-live="polite" data-picker-estado></p>

    @error($name)
        <p class="error-texto">{{ $message }}</p>
    @enderror
</div>
