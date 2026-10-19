@extends('layouts.app')

@section('titulo', $cliente->nombre)
@section('encabezado', $cliente->nombre)
@section('subtitulo', 'Cliente · '.($cliente->telefono ?? 'sin teléfono'))
@section('acciones')
    <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Editar</a>
    <a href="{{ route('clientes.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="card card-pad lg:col-span-1">
        <h2 class="mb-4 text-sm font-medium text-ink-muted">Datos</h2>
        <dl class="space-y-4 text-sm">
            <div><dt class="text-xs text-ink-muted">Nombre</dt><dd class="font-medium">{{ $cliente->nombre }}</dd></div>
            <div><dt class="text-xs text-ink-muted">Teléfono</dt><dd>{{ $cliente->telefono ?? '—' }}</dd></div>
            <div><dt class="text-xs text-ink-muted">Carnet</dt><dd>{{ $cliente->carnet ?? '—' }}</dd></div>
            <div><dt class="text-xs text-ink-muted">Dirección</dt><dd>{{ $cliente->direccion ?? '—' }}</dd></div>
            <div>
                <dt class="text-xs text-ink-muted">Estado</dt>
                <dd>@if ($cliente->activo)<span class="badge badge-ok"><span class="dot"></span>Activo</span>@else<span class="badge badge-neutral"><span class="dot"></span>Inactivo</span>@endif</dd>
            </div>
        </dl>
    </div>

    <div class="space-y-6 lg:col-span-2">
        <section>
            <h2 class="mb-3 text-sm font-medium text-ink">Últimas ventas</h2>
            <div class="card card-flush overflow-x-auto">
                <table class="tabla">
                    <thead><tr><th>Nota</th><th>Fecha</th><th>Sucursal</th><th class="text-right">Total</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($ventas as $venta)
                        <tr>
                            <td class="font-mono text-xs"><a href="{{ route('ventas.show', $venta) }}" class="text-brand hover:underline">{{ $venta->numero() }}</a></td>
                            <td>{{ $venta->fecha?->format('d/m/Y') }}</td>
                            <td class="text-ink-muted">{{ $venta->sucursal?->nombre }}</td>
                            <td class="text-right tabular-nums">Bs {{ number_format($venta->total, 2, ',', '.') }}</td>
                            <td>{{ ucfirst($venta->estado) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-ink-muted">Sin ventas.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-sm font-medium text-ink">Apartados</h2>
            <div class="card card-flush overflow-x-auto">
                <table class="tabla">
                    <thead><tr><th>Producto</th><th class="text-right">Seña</th><th class="text-right">Saldo</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($apartados as $apartado)
                        <tr>
                            <td>{{ $apartado->producto?->nombre }}</td>
                            <td class="text-right tabular-nums">Bs {{ number_format($apartado->sena, 2, ',', '.') }}</td>
                            <td class="text-right tabular-nums">Bs {{ number_format($apartado->saldo, 2, ',', '.') }}</td>
                            <td>{{ ucfirst($apartado->estado) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-ink-muted">Sin apartados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-sm font-medium text-ink">Encargos</h2>
            <div class="card card-flush overflow-x-auto">
                <table class="tabla">
                    <thead><tr><th>Producto</th><th class="text-right">Total</th><th class="text-right">Saldo</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($encargos as $encargo)
                        <tr>
                            <td>{{ $encargo->producto?->nombre ?? $encargo->descripcion }}</td>
                            <td class="text-right tabular-nums">Bs {{ number_format($encargo->total, 2, ',', '.') }}</td>
                            <td class="text-right tabular-nums">Bs {{ number_format($encargo->saldo, 2, ',', '.') }}</td>
                            <td>{{ ucfirst($encargo->estado) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-ink-muted">Sin encargos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section>
            <h2 class="mb-3 text-sm font-medium text-ink">Servicios técnicos</h2>
            <div class="card card-flush overflow-x-auto">
                <table class="tabla">
                    <thead><tr><th>OT</th><th>Equipo</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($servicios as $servicio)
                        <tr>
                            <td class="font-mono text-xs"><a href="{{ route('servicios.show', $servicio) }}" class="text-brand hover:underline">{{ $servicio->numero }}</a></td>
                            <td>{{ $servicio->equipo }}</td>
                            <td>{{ $servicio->estadoEnum()->etiqueta() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-ink-muted">Sin servicios.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection
