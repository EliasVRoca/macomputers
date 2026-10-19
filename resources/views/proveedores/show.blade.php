@extends('layouts.app')

@section('titulo', $proveedor->nombre)
@section('encabezado', $proveedor->nombre)
@section('subtitulo', 'Ficha del proveedor y su historial de compras.')
@section('acciones')
    <a href="{{ route('proveedores.edit', $proveedor) }}" class="btn btn-secondary"><x-icon name="pencil" class="size-4" /> Editar</a>
    <a href="{{ route('proveedores.index') }}" class="btn btn-secondary"><x-icon name="chevron-left" class="size-4" /> Volver</a>
@endsection

@section('contenido')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <section>
            <h2 class="mb-3 text-sm font-medium text-ink">Historial de compras</h2>
            <div class="card card-flush overflow-x-auto">
                <table class="tabla">
                    <thead><tr><th>Fecha</th><th>Compra</th><th>Sucursal</th><th class="text-right">Ítems</th><th class="text-right">Total</th><th>Estado</th></tr></thead>
                    <tbody>
                    @forelse ($compras as $compra)
                        <tr class="{{ $compra->estaAnulada() ? 'opacity-60' : '' }}">
                            <td>{{ $compra->fecha->format('d/m/Y') }}</td>
                            <td><a href="{{ route('compras.show', $compra) }}" class="font-medium hover:underline">#{{ $compra->id }}</a></td>
                            <td class="text-ink-muted">{{ $compra->sucursal?->nombre ?? '—' }}</td>
                            <td class="text-right tabular-nums">{{ $compra->items_count }}</td>
                            <td class="text-right tabular-nums">Bs {{ number_format($compra->total, 2, ',', '.') }}</td>
                            <td>
                                @if ($compra->estaAnulada())
                                    <span class="badge badge-danger">Anulada</span>
                                @else
                                    <span class="badge badge-ok">Activa</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">
                            <div class="empty">
                                <span class="kpi-icon"><x-icon name="clipboard-list" class="size-5" /></span>
                                <div>
                                    <p class="font-medium">Sin compras registradas</p>
                                    <p class="text-sm text-ink-muted">Aquí verás las compras hechas a este proveedor.</p>
                                </div>
                            </div>
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <aside class="space-y-6">
        <div class="card card-pad">
            <h2 class="mb-4 text-sm font-medium text-ink-muted">Datos de contacto</h2>
            <dl class="space-y-3 text-sm">
                <div><dt class="text-xs text-ink-muted">Contacto</dt><dd>{{ $proveedor->contacto ?? '—' }}</dd></div>
                <div><dt class="text-xs text-ink-muted">País de origen</dt><dd>{{ $proveedor->pais_origen ?? '—' }}</dd></div>
                <div><dt class="text-xs text-ink-muted">Dirección</dt><dd>{{ $proveedor->direccion ?? '—' }}</dd></div>
            </dl>
        </div>
    </aside>
</div>
@endsection
