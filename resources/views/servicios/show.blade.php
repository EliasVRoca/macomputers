@extends('layouts.app')

@php
    use App\Enums\EstadoServicio;
    use App\Models\Venta;
    $cerrado = $servicio->cerrado();
    $esGerente = auth()->user()?->esGerente() ?? false;
@endphp

@section('titulo', $servicio->numero)
@section('encabezado', $servicio->numero)
@section('subtitulo', ($servicio->cliente?->nombre ?? 'Sin cliente').' · '.$servicio->equipo.' · '.$servicio->sucursal?->nombre)
@section('acciones')
    <a href="{{ route('servicios.imprimir', $servicio) }}" target="_blank" class="btn btn-secondary"><x-icon name="clipboard-list" class="size-4" /> Imprimir orden</a>
    @if ($esGerente || ! $cerrado)
        <a href="{{ route('servicios.edit', $servicio) }}" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Editar datos</a>
    @endif
    <a href="{{ route('servicios.index') }}" class="btn btn-ghost"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        {{-- Estado y transiciones --}}
        <section class="card card-pad">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="badge {{ $servicio->estadoEnum()->colorBadge() }} px-3 py-1.5 text-sm"><span class="dot"></span>{{ $servicio->estadoEnum()->etiqueta() }}</span>
                    @if ($servicio->prioridad !== 'normal')
                        <span class="badge {{ $servicio->prioridad === 'urgente' ? 'badge-danger' : 'badge-warn' }}">Prioridad {{ \App\Models\Servicio::PRIORIDADES[$servicio->prioridad] }}</span>
                    @endif
                    @if ($servicio->fecha_prometida)
                        @php($vencida = $servicio->fecha_prometida->lt(now()->startOfDay()) && ! $cerrado)
                        <span class="badge {{ $vencida ? 'badge-danger' : 'badge-neutral' }}">
                            @if ($vencida) <x-icon name="alert-triangle" class="size-3.5" /> @endif
                            Prometida: {{ $servicio->fecha_prometida->format('d/m/Y') }}
                        </span>
                    @endif
                </div>
            </div>

            @if ($cerrado)
                <p class="mt-4 rounded-lg bg-surface-2 px-4 py-3 text-sm text-ink-muted">
                    La orden está en estado final ({{ $servicio->estadoEnum()->etiqueta() }}) y ya no admite cambios de estado ni de repuestos.
                    @unless ($esGerente)
                        Solo un gerente puede editar o eliminar esta orden.
                    @endunless
                </p>
            @elseif ($siguientes === [])
                <p class="mt-4 text-sm text-ink-muted">No hay transiciones disponibles.</p>
            @else
                <div class="mt-5 space-y-3">
                    <p class="text-sm font-medium text-ink-muted">Avanzar la orden a:</p>
                    @foreach ($siguientes as $estado)
                        <form method="POST" action="{{ route('servicios.estado', $servicio) }}" class="flex flex-wrap items-center gap-2 rounded-lg border border-hairline p-3">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="estado" value="{{ $estado->value }}">
                            <input name="comentario" class="input flex-1 min-w-[12rem]" maxlength="500" placeholder="Comentario (opcional)">
                            @if ($estado === EstadoServicio::Entregado)
                                <div class="min-w-[11rem]">
                                    <label for="medio_pago" class="label">Medio de pago</label>
                                    <select id="medio_pago" name="medio_pago" class="input" required>
                                        <option value="">Seleccione…</option>
                                        @foreach ($medios as $medio)
                                            <option value="{{ $medio }}" @selected(old('medio_pago') === $medio)>{{ $medio }}</option>
                                        @endforeach
                                    </select>
                                    @error('medio_pago') <p class="error-texto">{{ $message }}</p> @enderror
                                </div>
                            @endif
                            <button type="submit" class="btn {{ $estado === EstadoServicio::Cancelado ? 'btn-danger' : 'btn-primary' }} btn-sm">
                                <x-icon name="{{ $estado === EstadoServicio::Cancelado ? 'x' : 'check' }}" class="size-4" />
                                Marcar como {{ $estado->etiqueta() }}
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Repuestos --}}
        <section class="card card-pad">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-sm font-medium text-ink-muted">Repuestos utilizados</h2>
                <span class="text-sm text-ink-muted">Subtotal: <span class="font-medium tabular-nums text-ink">Bs {{ number_format($servicio->repuestosTotal(), 2, ',', '.') }}</span></span>
            </div>

            <div class="overflow-x-auto">
                <table class="tabla">
                    <thead>
                        <tr><th>Repuesto</th><th class="text-right">Cant.</th><th class="text-right">P. unit.</th><th class="text-right">Subtotal</th><th></th></tr>
                    </thead>
                    <tbody>
                    @forelse ($servicio->repuestos as $repuesto)
                        <tr>
                            <td>{{ $repuesto->producto?->nombre }}</td>
                            <td class="text-right tabular-nums">{{ $repuesto->cantidad }}</td>
                            <td class="text-right tabular-nums">Bs {{ number_format($repuesto->precio_unitario, 2, ',', '.') }}</td>
                            <td class="text-right tabular-nums">Bs {{ number_format($repuesto->subtotal, 2, ',', '.') }}</td>
                            <td class="text-right">
                                @unless ($cerrado)
                                    <form method="POST" action="{{ route('servicios.repuestos.destroy', [$servicio, $repuesto]) }}" onsubmit="return confirm('¿Quitar este repuesto y devolverlo al inventario?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-icon text-danger" title="Quitar" aria-label="Quitar repuesto"><x-icon name="trash" class="size-4" /></button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="empty py-8"><p class="text-sm text-ink-muted">Sin repuestos registrados.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @unless ($cerrado)
                <form method="POST" action="{{ route('servicios.repuestos.store', $servicio) }}" class="mt-4 grid gap-3 border-t border-hairline pt-4 sm:grid-cols-[2fr,1fr,auto] sm:items-end">
                    @csrf
                    <div>
                        <label for="producto_id" class="label">Repuesto</label>
                        <select id="producto_id" name="producto_id" class="input" required>
                            <option value="">Seleccione…</option>
                            @foreach ($productos as $producto)
                                <option value="{{ $producto->id }}" @selected(old('producto_id') == $producto->id)>
                                    {{ $producto->nombre }} · disp. {{ $disponibles[$producto->id] ?? 0 }}
                                </option>
                            @endforeach
                        </select>
                        @error('producto_id') <p class="error-texto">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="cantidad" class="label">Cantidad</label>
                        <input id="cantidad" name="cantidad" type="number" min="1" value="{{ old('cantidad', 1) }}" class="input" required>
                        @error('cantidad') <p class="error-texto">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Agregar</button>
                </form>
                @error('repuesto') <p class="error-texto mt-2">{{ $message }}</p> @enderror
                @error('stock') <p class="error-texto mt-2">{{ $message }}</p> @enderror
            @endunless
        </section>

        {{-- Historial --}}
        <section class="card card-pad">
            <h2 class="mb-4 text-sm font-medium text-ink-muted">Historial de la orden</h2>
            <ol class="space-y-4">
                @forelse ($servicio->historial as $evento)
                    <li class="flex gap-3">
                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand"></span>
                        <div class="min-w-0">
                            <p class="text-sm">
                                @if ($evento->estado_anterior)
                                    <span class="text-ink-muted">{{ EstadoServicio::desde($evento->estado_anterior)->etiqueta() }}</span>
                                    <span class="text-ink-tertiary">→</span>
                                @endif
                                <span class="font-medium">{{ EstadoServicio::desde($evento->estado_nuevo)->etiqueta() }}</span>
                            </p>
                            @if ($evento->comentario)
                                <p class="text-sm text-ink-muted">{{ $evento->comentario }}</p>
                            @endif
                            <p class="text-xs text-ink-tertiary">{{ $evento->created_at->format('d/m/Y H:i') }} · {{ $evento->usuario?->nombre() ?? 'Sistema' }}</p>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-ink-muted">Sin eventos registrados.</li>
                @endforelse
            </ol>
        </section>
    </div>

    {{-- Columna lateral --}}
    <div class="space-y-6">
        <section class="card card-pad space-y-4 text-sm">
            <h2 class="text-sm font-medium text-ink-muted">Datos de la orden</h2>
            <dl class="space-y-3">
                <div><dt class="text-ink-muted">Cliente</dt><dd class="font-medium">{{ $servicio->cliente?->nombre ?? '—' }} @if ($servicio->cliente?->telefono) · {{ $servicio->cliente->telefono }} @endif</dd></div>
                <div><dt class="text-ink-muted">Sucursal</dt><dd class="font-medium">{{ $servicio->sucursal?->nombre }}</dd></div>
                <div><dt class="text-ink-muted">Técnico</dt><dd class="font-medium">{{ $servicio->tecnico?->nombre() ?? '—' }}</dd></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><dt class="text-ink-muted">Recepción</dt><dd class="font-medium">{{ $servicio->fecha_recepcion?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div><dt class="text-ink-muted">Entrega</dt><dd class="font-medium">{{ $servicio->fecha_entrega?->format('d/m/Y') ?? '—' }}</dd></div>
                </div>
                <div><dt class="text-ink-muted">Garantía</dt><dd class="font-medium">{{ $servicio->garantia_dias > 0 ? $servicio->garantia_dias.' días' : 'Sin garantía' }}</dd></div>
                <div><dt class="text-ink-muted">Accesorios recibidos</dt><dd>{{ $servicio->accesorios_recibidos ?: '—' }}</dd></div>
                <div><dt class="text-ink-muted">Diagnóstico</dt><dd class="whitespace-pre-line">{{ $servicio->diagnostico ?: 'Pendiente' }}</dd></div>
                @if ($servicio->observaciones)
                    <div><dt class="text-ink-muted">Observaciones</dt><dd class="whitespace-pre-line">{{ $servicio->observaciones }}</dd></div>
                @endif
            </dl>
        </section>

        <section class="card card-pad space-y-3 text-sm">
            <h2 class="text-sm font-medium text-ink-muted">Costos</h2>
            <div class="flex items-center justify-between"><span class="text-ink-muted">Mano de obra</span><span class="tabular-nums">Bs {{ number_format($servicio->mano_de_obra, 2, ',', '.') }}</span></div>
            <div class="flex items-center justify-between"><span class="text-ink-muted">Repuestos</span><span class="tabular-nums">Bs {{ number_format($servicio->repuestosTotal(), 2, ',', '.') }}</span></div>
            @if ((float) $servicio->descuento > 0)
                <div class="flex items-center justify-between"><span class="text-ink-muted">Descuento</span><span class="tabular-nums text-danger">− Bs {{ number_format($servicio->descuento, 2, ',', '.') }}</span></div>
            @endif
            <div class="flex items-center justify-between border-t border-hairline pt-3 text-base font-medium"><span>Total</span><span class="tabular-nums">Bs {{ number_format($servicio->totalCalculado(), 2, ',', '.') }}</span></div>
            <div class="flex items-center justify-between border-t border-hairline pt-3">
                <span class="text-ink-muted">Medio de pago</span>
                <span class="font-medium">{{ $servicio->venta?->medio_pago ?? 'Pendiente de cobro' }}</span>
            </div>
            @if ($servicio->venta?->estado === Venta::ESTADO_ANULADA)
                <p class="text-xs text-danger">Venta de servicio anulada.</p>
            @endif
            <p class="text-xs text-ink-tertiary">Venta de servicio {{ $servicio->venta?->numero() }}</p>
        </section>
    </div>
</div>
@endsection
