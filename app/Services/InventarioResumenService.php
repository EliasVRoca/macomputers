<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;

/**
 * Indicadores (KPIs) del encabezado del módulo de Inventario.
 */
class InventarioResumenService
{
    /** Productos activos cuyo total por sucursal es menor o igual al stock mínimo. */
    private const BAJO_MINIMO = '(select coalesce(sum(i.cantidad), 0) from inventarios i where i.producto_id = productos.id) <= productos.stock_minimo';

    /**
     * @return array{productos: int, unidades: int, bajo_minimo: int, movimientos_hoy: int}
     */
    public function metricas(): array
    {
        return [
            'productos' => Producto::where('activo', true)->count(),
            'unidades' => (int) Inventario::sum('cantidad'),
            'bajo_minimo' => $this->bajoMinimo(),
            'movimientos_hoy' => MovimientoInventario::whereDate('created_at', today())->count(),
        ];
    }

    public function bajoMinimo(): int
    {
        return Producto::where('activo', true)->whereRaw(self::BAJO_MINIMO)->count();
    }
}
