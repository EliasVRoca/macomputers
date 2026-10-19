<?php

namespace App\Services;

use App\Enums\EstadoServicio;
use App\Enums\ResultadoBusqueda;
use App\Models\BusquedaProducto;
use App\Models\Servicio;
use App\Models\Traspaso;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    public function __construct(private readonly InventarioResumenService $inventario) {}

    /**
     * @return array<string, mixed>
     */
    public function metricas(User $usuario, ?int $sucursalId = null): array
    {
        $inicio = today()->startOfDay();
        $fin = today()->endOfDay();
        $metricas = [];

        if ($usuario->tienePermiso('ventas')) {
            $ventas = Venta::query()
                ->where('tipo', Venta::TIPO_VENTA)
                ->where('estado', Venta::ESTADO_ACTIVA)
                ->whereBetween('fecha', [$inicio, $fin])
                ->when($sucursalId !== null, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
                ->selectRaw('COALESCE(SUM(total), 0) as total, COUNT(*) as cantidad')
                ->first();

            $metricas['ventas'] = [
                'total' => (float) ($ventas?->total ?? 0),
                'cantidad' => (int) ($ventas?->cantidad ?? 0),
            ];
        }

        if ($usuario->tienePermiso('inventario')) {
            $metricas['bajo_minimo'] = $this->inventario->bajoMinimo();
        }

        if ($usuario->tienePermiso('servicios')) {
            $conteos = Servicio::query()
                ->when($sucursalId !== null, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
                ->selectRaw('estado, COUNT(*) as total')
                ->groupBy('estado')
                ->pluck('total', 'estado');

            $metricas['ot_pendientes'] = (int) ($conteos[EstadoServicio::Recibido->value] ?? 0)
                + (int) ($conteos[EstadoServicio::EnProceso->value] ?? 0);
            $metricas['ot_listas'] = (int) ($conteos[EstadoServicio::Listo->value] ?? 0);
        }

        if ($usuario->tienePermiso('traspasos')) {
            $metricas['traspasos'] = Traspaso::query()
                ->whereDate('fecha', today())
                ->when($sucursalId !== null, fn (Builder $query) => $query->where(fn (Builder $where) => $where
                    ->where('sucursal_origen_id', $sucursalId)
                    ->orWhere('sucursal_destino_id', $sucursalId)))
                ->count();
        }

        if ($usuario->tienePermiso('buscador')) {
            $metricas['busquedas_sin_resultado'] = BusquedaProducto::query()
                ->whereBetween('created_at', [$inicio, $fin])
                ->whereIn('resultado', [
                    ResultadoBusqueda::SinStock->value,
                    ResultadoBusqueda::NoExiste->value,
                ])
                ->when($sucursalId !== null, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
                ->count();
        }

        return $metricas;
    }
}
