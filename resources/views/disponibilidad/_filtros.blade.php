<form method="GET" action="{{ route('disponibilidad.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
    <div class="min-w-56 flex-1">
        <label for="buscar" class="label">Buscar producto</label>
        <input id="buscar" name="buscar" type="search" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Nombre, código o marca" class="input">
    </div>
    <div class="min-w-48">
        <label for="sucursal_id" class="label">Sucursal</label>
        <select id="sucursal_id" name="sucursal_id" class="input">
            <option value="">Todas las sucursales</option>
            @foreach ($sucursales as $sucursal)
                <option value="{{ $sucursal->id }}" @selected(($filtros['sucursal_id'] ?? null) == $sucursal->id)>{{ $sucursal->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-0.5 flex gap-2">
        <a href="{{ route('disponibilidad.index') }}" class="btn btn-ghost">Limpiar</a>
        <button type="submit" class="btn btn-secondary"><x-icon name="search" class="size-4" /> Buscar</button>
    </div>
</form>
