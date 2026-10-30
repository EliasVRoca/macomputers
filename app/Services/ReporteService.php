<?php

namespace App\Services;

use App\Enums\EstadoServicio;
use App\Enums\ResultadoBusqueda;
use App\Models\BusquedaProducto;
use App\Models\Inventario;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Agregaciones para la sección de Reportes y los mini gráficos del inicio.
 *
 * Cada área se incluye únicamente si el usuario tiene el permiso de su módulo,
 * y los datos se limitan a la sucursal del usuario cuando no es gerente.
 */
class ReporteService
{
    private const MAX_PERIODOS = 750;

    public function __construct(private readonly InventarioResumenService $inventario) {}

    /**
     * @return array<string, mixed>
     */
    public function generar(
        User $usuario,
        CarbonImmutable $desde,
        CarbonImmutable $hasta,
        ?int $sucursalId = null,
        string $agrupacion = 'dia',
    ): array {
        $datos = [];

        if ($usuario->tienePermiso('ventas')) {
            $ventas = $this->ventas($usuario, $sucursalId, $desde, $hasta)->get();
            $items = $this->items($usuario, $sucursalId, $desde, $hasta)
                ->with('producto:id,categoria_id,precio_compra,nombre', 'producto.categoria:id,nombre')
                ->get();

            $periodos = $this->periodos($desde, $hasta, $agrupacion);
            $tendencia = $this->tendencia($ventas, $periodos, $agrupacion);
            $utilidad = $this->utilidad($items, $periodos, $agrupacion);

            $ingresos = (float) $ventas->sum('total');
            $costo = $utilidad['costo_total'];
            $ganancia = round($ingresos - $costo, 2);

            $datos['resumen'] = [
                'ingresos' => $ingresos,
                'ventas' => $ventas->count(),
                'unidades' => (int) $items->sum('cantidad'),
                'ticket_promedio' => $ventas->isEmpty()
                    ? 0.0
                    : round($ingresos / $ventas->count(), 2),
                'utilidad' => $ganancia,
                'margen' => $ingresos > 0 ? round($ganancia / $ingresos * 100, 1) : 0.0,
            ];

            $datos['ventas'] = [
                'tendencia' => [
                    'tipo' => 'linea',
                    'etiquetas' => $tendencia['etiquetas'],
                    'series' => [
                        ['nombre' => 'Ingresos', 'datos' => $tendencia['montos']],
                        ['nombre' => 'Costo', 'datos' => $utilidad['costos']],
                    ],
                    'formato' => 'monto',
                ],
                'cantidades' => [
                    'tipo' => 'barras',
                    'etiquetas' => $tendencia['etiquetas'],
                    'series' => [
                        ['nombre' => 'Ventas', 'datos' => $tendencia['cantidades']],
                    ],
                    'formato' => 'entero',
                ],
                'por_categoria' => $this->porCategoria($items),
                'top_productos' => $this->topProductos($items),
            ];

            if ($usuario->esGerente()) {
                $datos['ventas']['por_sucursal'] = $this->porSucursal($ventas);
            }
        }

        if ($usuario->tienePermiso('inventario')) {
            $stock = Inventario::query()
                ->with('producto:id,categoria_id,precio_compra,nombre', 'producto.categoria:id,nombre')
                ->when($sucursalId !== null, fn (Builder $q) => $q->where('sucursal_id', $sucursalId))
                ->get(['producto_id', 'sucursal_id', 'cantidad']);

            $porCategoria = $this->stockPorCategoria($stock);

            $datos['inventario'] = [
                'cifras' => [
                    'valor_total' => (float) $stock->sum(fn (Inventario $fila) => (float) $fila->producto?->precio_compra * (int) $fila->cantidad),
                    'unidades' => (int) $stock->sum('cantidad'),
                    'bajo_minimo' => $this->inventario->bajoMinimo(),
                ],
                'por_categoria_unidades' => $porCategoria['unidades'],
                'por_categoria_valor' => $porCategoria['valor'],
                'top_valor' => $this->topStockValor($stock),
            ];
        }

        if ($usuario->tienePermiso('servicios')) {
            $servicios = Servicio::query()
                ->when(! $usuario->esGerente(), fn (Builder $q) => $q->where('sucursal_id', $usuario->sucursal_id))
                ->when($usuario->esGerente() && $sucursalId !== null, fn (Builder $q) => $q->where('sucursal_id', $sucursalId))
                ->whereBetween('fecha_recepcion', [$desde->toDateString(), $hasta->toDateString()])
                ->get(['id', 'estado', 'fecha_recepcion']);

            $ingresosServicio = Venta::query()
                ->where('tipo', Venta::TIPO_SERVICIO)
                ->where('estado', Venta::ESTADO_ACTIVA)
                ->when(! $usuario->esGerente(), fn (Builder $q) => $q->where('sucursal_id', $usuario->sucursal_id))
                ->when($usuario->esGerente() && $sucursalId !== null, fn (Builder $q) => $q->where('sucursal_id', $sucursalId))
                ->whereBetween('fecha', [$desde, $hasta])
                ->get(['id', 'fecha', 'total']);

            $datos['servicios'] = [
                'por_estado' => $this->serviciosPorEstado($servicios),
                'actividad' => $this->actividadServicios($servicios, $ingresosServicio, $this->periodos($desde, $hasta, $agrupacion), $agrupacion),
            ];
        }

        if ($usuario->tienePermiso('buscador')) {
            $busquedas = BusquedaProducto::query()
                ->with('sucursal:id,nombre')
                ->when(! $usuario->esGerente(), fn (Builder $q) => $q->where('sucursal_id', $usuario->sucursal_id))
                ->when($usuario->esGerente() && $sucursalId !== null, fn (Builder $q) => $q->where('sucursal_id', $sucursalId))
                ->whereBetween('created_at', [$desde, $hasta])
                ->get(['id', 'termino', 'sucursal_id', 'resultado']);

            $datos['busquedas'] = [
                'top_terminos' => $this->topTerminos($busquedas),
                'por_resultado' => $this->busquedasPorResultado($busquedas),
                'sin_resultado_sucursal' => $this->sinResultadoPorSucursal($busquedas),
            ];
        }

        return $datos;
    }

    /**
     * Mini gráficos del dashboard (últimos 14 días).
     *
     * @return array<string, array<string, mixed>>
     */
    public function mini(User $usuario, ?int $sucursalId = null): array
    {
        $desde = today()->subDays(13)->startOfDay()->toImmutable();
        $hasta = today()->endOfDay()->toImmutable();
        $mini = [];

        if ($usuario->tienePermiso('ventas')) {
            $ventas = $this->ventas($usuario, $sucursalId, $desde, $hasta)->get();
            $tendencia = $this->tendencia($ventas, $this->periodos($desde, $hasta, 'dia'), 'dia');

            $mini['ventas'] = [
                'titulo' => 'Ventas · últimos 14 días',
                'tipo' => 'linea',
                'etiquetas' => $tendencia['etiquetas'],
                'series' => [
                    ['nombre' => 'Ingresos', 'datos' => $tendencia['montos']],
                ],
                'formato' => 'monto',
            ];
        }

        if ($usuario->tienePermiso('servicios')) {
            $servicios = Servicio::query()
                ->when(! $usuario->esGerente(), fn (Builder $q) => $q->where('sucursal_id', $usuario->sucursal_id))
                ->when($usuario->esGerente() && $sucursalId !== null, fn (Builder $q) => $q->where('sucursal_id', $sucursalId))
                ->get(['id', 'estado']);

            $mini['servicios'] = $this->serviciosPorEstado($servicios);
            $mini['servicios']['titulo'] = 'Servicio técnico · por estado';
        }

        if ($usuario->tienePermiso('buscador')) {
            $busquedas = BusquedaProducto::query()
                ->when(! $usuario->esGerente(), fn (Builder $q) => $q->where('sucursal_id', $usuario->sucursal_id))
                ->when($usuario->esGerente() && $sucursalId !== null, fn (Builder $q) => $q->where('sucursal_id', $sucursalId))
                ->whereBetween('created_at', [$desde, $hasta])
                ->get(['id', 'termino', 'created_at', 'resultado']);

            $periodos = $this->periodos($desde, $hasta, 'dia');
            $indice = array_flip(array_keys($periodos));
            $sinStock = array_fill(0, count($periodos), 0);
            $noExiste = array_fill(0, count($periodos), 0);

            foreach ($busquedas as $busqueda) {
                $clave = $this->clave(CarbonImmutable::parse($busqueda->created_at), 'dia');

                if (! isset($indice[$clave])) {
                    continue;
                }

                if ($busqueda->resultado === ResultadoBusqueda::SinStock) {
                    $sinStock[$indice[$clave]]++;
                } elseif ($busqueda->resultado === ResultadoBusqueda::NoExiste) {
                    $noExiste[$indice[$clave]]++;
                }
            }

            $mini['busquedas'] = [
                'titulo' => 'Búsquedas sin resultado · 14 días',
                'tipo' => 'barras',
                'etiquetas' => array_values($periodos),
                'series' => [
                    ['nombre' => 'Sin stock', 'datos' => $sinStock],
                    ['nombre' => 'No existe', 'datos' => $noExiste],
                ],
                'formato' => 'entero',
            ];
        }

        return $mini;
    }

    private function ventas(User $usuario, ?int $sucursalId, CarbonImmutable $desde, CarbonImmutable $hasta): Builder
    {
        return Venta::query()
            ->with('sucursal:id,nombre')
            ->where('tipo', Venta::TIPO_VENTA)
            ->where('estado', Venta::ESTADO_ACTIVA)
            ->when(! $usuario->esGerente(), fn (Builder $q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->when($usuario->esGerente() && $sucursalId !== null, fn (Builder $q) => $q->where('sucursal_id', $sucursalId))
            ->whereBetween('fecha', [$desde, $hasta])
            ->select(['id', 'fecha', 'total', 'sucursal_id']);
    }

    private function items(User $usuario, ?int $sucursalId, CarbonImmutable $desde, CarbonImmutable $hasta): Builder
    {
        return VentaItem::query()
            ->join('ventas', 'ventas.id', '=', 'venta_items.venta_id')
            ->where('ventas.tipo', Venta::TIPO_VENTA)
            ->where('ventas.estado', Venta::ESTADO_ACTIVA)
            ->when(! $usuario->esGerente(), fn (Builder $q) => $q->where('ventas.sucursal_id', $usuario->sucursal_id))
            ->when($usuario->esGerente() && $sucursalId !== null, fn (Builder $q) => $q->where('ventas.sucursal_id', $sucursalId))
            ->whereBetween('ventas.fecha', [$desde, $hasta])
            ->select([
                'venta_items.id', 'venta_items.producto_id', 'venta_items.descripcion',
                'venta_items.cantidad', 'venta_items.subtotal', 'venta_items.costo_unitario',
                'ventas.fecha',
            ]);
    }

    /**
     * Etiquetas ordenadas (clave => etiqueta) del rango según la agrupación.
     *
     * @return array<string, string>
     */
    private function periodos(CarbonImmutable $desde, CarbonImmutable $hasta, string $agrupacion): array
    {
        $periodos = [];
        $cursor = $desde;

        while ($cursor->lte($hasta) && count($periodos) < self::MAX_PERIODOS) {
            $periodos[$this->clave($cursor, $agrupacion)] = $this->etiqueta($cursor, $agrupacion);
            $cursor = $cursor->add(1, match ($agrupacion) {
                'mes' => 'month',
                'semana' => 'week',
                default => 'day',
            });
        }

        return $periodos;
    }

    private function clave(CarbonImmutable $fecha, string $agrupacion): string
    {
        return match ($agrupacion) {
            'mes' => $fecha->format('Y-m'),
            'semana' => $fecha->startOfWeek()->toDateString(),
            default => $fecha->toDateString(),
        };
    }

    private function etiqueta(CarbonImmutable $fecha, string $agrupacion): string
    {
        return match ($agrupacion) {
            'mes' => $fecha->translatedFormat('m/Y'),
            'semana' => 'Sem. '.$fecha->startOfWeek()->translatedFormat('d/m'),
            default => $fecha->format('d/m'),
        };
    }

    /**
     * @param  Collection<int, Venta>  $ventas
     * @param  array<string, string>  $periodos
     * @return array{etiquetas: array<int, string>, montos: array<int, float>, cantidades: array<int, int>}
     */
    private function tendencia(Collection $ventas, array $periodos, string $agrupacion): array
    {
        $montos = array_fill(0, count($periodos), 0.0);
        $cantidades = array_fill(0, count($periodos), 0);
        $indice = array_flip(array_keys($periodos));

        foreach ($ventas as $venta) {
            $clave = $this->clave(CarbonImmutable::parse($venta->fecha), $agrupacion);

            if (! isset($indice[$clave])) {
                continue;
            }

            $montos[$indice[$clave]] += (float) $venta->total;
            $cantidades[$indice[$clave]]++;
        }

        return [
            'etiquetas' => array_values($periodos),
            'montos' => array_map(fn ($m) => round($m, 2), $montos),
            'cantidades' => $cantidades,
        ];
    }

    /**
     * Costo e ingresos por período. Los ítems antiguos sin costo guardado usan
     * el precio de compra actual del producto como aproximación.
     *
     * @param  Collection<int, VentaItem>  $items
     * @param  array<string, string>  $periodos
     * @return array{costos: array<int, float>, costo_total: float}
     */
    private function utilidad(Collection $items, array $periodos, string $agrupacion): array
    {
        $costos = array_fill(0, count($periodos), 0.0);
        $indice = array_flip(array_keys($periodos));
        $costoTotal = 0.0;

        foreach ($items as $item) {
            $costoUnitario = $item->costo_unitario !== null
                ? (float) $item->costo_unitario
                : (float) ($item->producto?->precio_compra ?? 0);

            $costo = $costoUnitario * (int) $item->cantidad;
            $costoTotal += $costo;

            $clave = $this->clave(CarbonImmutable::parse($item->fecha), $agrupacion);

            if (isset($indice[$clave])) {
                $costos[$indice[$clave]] += $costo;
            }
        }

        return [
            'costos' => array_map(fn ($c) => round($c, 2), $costos),
            'costo_total' => round($costoTotal, 2),
        ];
    }

    /**
     * @param  Collection<int, VentaItem>  $items
     * @return array<string, mixed>
     */
    private function porCategoria(Collection $items): array
    {
        $porCategoria = [];

        foreach ($items as $item) {
            $nombre = $item->producto?->categoria?->nombre ?? 'Sin categoría';
            $porCategoria[$nombre]['monto'] = ($porCategoria[$nombre]['monto'] ?? 0.0) + (float) $item->subtotal;
            $porCategoria[$nombre]['unidades'] = ($porCategoria[$nombre]['unidades'] ?? 0) + (int) $item->cantidad;
        }

        ksort($porCategoria);

        return [
            'tipo' => 'dona',
            'etiquetas' => array_keys($porCategoria),
            'series' => [
                ['nombre' => 'Ingresos', 'datos' => array_map(fn ($c) => round($c['monto'], 2), array_values($porCategoria))],
            ],
            'formato' => 'monto',
        ];
    }

    /**
     * @param  Collection<int, VentaItem>  $items
     * @return array<string, mixed>
     */
    private function topProductos(Collection $items): array
    {
        $top = [];

        foreach ($items as $item) {
            $nombre = $item->producto?->nombre ?? $item->descripcion ?? 'Producto';
            $top[$nombre]['monto'] = ($top[$nombre]['monto'] ?? 0.0) + (float) $item->subtotal;
            $top[$nombre]['unidades'] = ($top[$nombre]['unidades'] ?? 0) + (int) $item->cantidad;
        }

        uasort($top, fn ($a, $b) => $b['monto'] <=> $a['monto']);
        $top = array_slice($top, 0, 10, true);

        return [
            'tipo' => 'barrasH',
            'etiquetas' => array_keys($top),
            'series' => [
                ['nombre' => 'Monto', 'datos' => array_map(fn ($p) => round($p['monto'], 2), array_values($top))],
            ],
            'formato' => 'monto',
        ];
    }

    /**
     * @param  Collection<int, Venta>  $ventas
     * @return array<string, mixed>
     */
    private function porSucursal(Collection $ventas): array
    {
        $porSucursal = [];

        foreach ($ventas as $venta) {
            $nombre = $venta->sucursal?->nombre ?? 'Sucursal #'.$venta->sucursal_id;
            $porSucursal[$nombre]['monto'] = ($porSucursal[$nombre]['monto'] ?? 0.0) + (float) $venta->total;
            $porSucursal[$nombre]['cantidad'] = ($porSucursal[$nombre]['cantidad'] ?? 0) + 1;
        }

        ksort($porSucursal);

        return [
            'tipo' => 'barras',
            'etiquetas' => array_keys($porSucursal),
            'series' => [
                ['nombre' => 'Ingresos', 'datos' => array_map(fn ($s) => round($s['monto'], 2), array_values($porSucursal))],
            ],
            'formato' => 'monto',
        ];
    }

    /**
     * @param  Collection<int, Inventario>  $stock
     * @return array{unidades: array<string, mixed>, valor: array<string, mixed>}
     */
    private function stockPorCategoria(Collection $stock): array
    {
        $unidades = [];
        $valor = [];

        foreach ($stock as $fila) {
            $nombre = $fila->producto?->categoria?->nombre ?? 'Sin categoría';
            $unidades[$nombre] = ($unidades[$nombre] ?? 0) + (int) $fila->cantidad;
            $valor[$nombre] = ($valor[$nombre] ?? 0.0) + (float) ($fila->producto?->precio_compra ?? 0) * (int) $fila->cantidad;
        }

        ksort($unidades);
        ksort($valor);

        return [
            'unidades' => [
                'tipo' => 'dona',
                'etiquetas' => array_keys($unidades),
                'series' => [
                    ['nombre' => 'Unidades', 'datos' => array_map(fn ($u) => max(0, (int) $u), array_values($unidades))],
                ],
                'formato' => 'entero',
            ],
            'valor' => [
                'tipo' => 'dona',
                'etiquetas' => array_keys($valor),
                'series' => [
                    ['nombre' => 'Valor al costo', 'datos' => array_map(fn ($v) => round(max(0, $v), 2), array_values($valor))],
                ],
                'formato' => 'monto',
            ],
        ];
    }

    /**
     * @param  Collection<int, Inventario>  $stock
     * @return array<string, mixed>
     */
    private function topStockValor(Collection $stock): array
    {
        $top = [];

        foreach ($stock as $fila) {
            $producto = $fila->producto;

            if ($producto === null) {
                continue;
            }

            $nombre = $producto->nombre;
            $top[$nombre]['valor'] = ($top[$nombre]['valor'] ?? 0.0) + (float) $producto->precio_compra * (int) $fila->cantidad;
            $top[$nombre]['unidades'] = ($top[$nombre]['unidades'] ?? 0) + (int) $fila->cantidad;
        }

        uasort($top, fn ($a, $b) => $b['valor'] <=> $a['valor']);
        $top = array_slice($top, 0, 10, true);

        return [
            'tipo' => 'barrasH',
            'etiquetas' => array_keys($top),
            'series' => [
                ['nombre' => 'Valor al costo', 'datos' => array_map(fn ($p) => round($p['valor'], 2), array_values($top))],
            ],
            'formato' => 'monto',
        ];
    }

    /**
     * @param  Collection<int, Servicio>  $servicios
     * @return array<string, mixed>
     */
    private function serviciosPorEstado(Collection $servicios): array
    {
        $conteos = $servicios->countBy(fn (Servicio $servicio) => $servicio->estado);

        return [
            'tipo' => 'dona',
            'etiquetas' => collect(EstadoServicio::cases())->map->etiqueta()->all(),
            'series' => [
                ['nombre' => 'Órdenes', 'datos' => collect(EstadoServicio::cases())
                    ->map(fn (EstadoServicio $estado) => (int) ($conteos[$estado->value] ?? 0))
                    ->all()],
            ],
            'formato' => 'entero',
        ];
    }

    /**
     * @param  Collection<int, Servicio>  $servicios
     * @param  Collection<int, Venta>  $ingresosServicio
     * @param  array<string, string>  $periodos
     * @return array<string, mixed>
     */
    private function actividadServicios(Collection $servicios, Collection $ingresosServicio, array $periodos, string $agrupacion): array
    {
        $ot = array_fill(0, count($periodos), 0);
        $montos = array_fill(0, count($periodos), 0.0);
        $indice = array_flip(array_keys($periodos));

        foreach ($servicios as $servicio) {
            $clave = $this->clave(CarbonImmutable::parse($servicio->fecha_recepcion), $agrupacion);

            if (isset($indice[$clave])) {
                $ot[$indice[$clave]]++;
            }
        }

        foreach ($ingresosServicio as $venta) {
            $clave = $this->clave(CarbonImmutable::parse($venta->fecha), $agrupacion);

            if (isset($indice[$clave])) {
                $montos[$indice[$clave]] += (float) $venta->total;
            }
        }

        return [
            'tipo' => 'linea',
            'etiquetas' => array_values($periodos),
            'series' => [
                ['nombre' => 'OT recibidas', 'datos' => $ot],
                ['nombre' => 'Ingresos', 'datos' => array_map(fn ($m) => round($m, 2), $montos), 'eje' => 'derecho', 'formato' => 'monto'],
            ],
            'formato' => null,
        ];
    }

    /**
     * @param  Collection<int, BusquedaProducto>  $busquedas
     * @return array<string, mixed>
     */
    private function topTerminos(Collection $busquedas): array
    {
        $top = $busquedas->countBy(fn (BusquedaProducto $busqueda) => mb_strtolower(trim($busqueda->termino)));
        $top = $top->sortDesc()->take(10);

        return [
            'tipo' => 'barrasH',
            'etiquetas' => $top->keys()->all(),
            'series' => [
                ['nombre' => 'Búsquedas', 'datos' => $top->values()->all()],
            ],
            'formato' => 'entero',
        ];
    }

    /**
     * @param  Collection<int, BusquedaProducto>  $busquedas
     * @return array<string, mixed>
     */
    private function busquedasPorResultado(Collection $busquedas): array
    {
        $conteos = $busquedas->countBy(fn (BusquedaProducto $busqueda) => $busqueda->resultado->value);

        return [
            'tipo' => 'dona',
            'etiquetas' => collect(ResultadoBusqueda::cases())->map->etiqueta()->all(),
            'series' => [
                ['nombre' => 'Búsquedas', 'datos' => collect(ResultadoBusqueda::cases())
                    ->map(fn (ResultadoBusqueda $resultado) => (int) ($conteos[$resultado->value] ?? 0))
                    ->all()],
            ],
            'formato' => 'entero',
        ];
    }

    /**
     * @param  Collection<int, BusquedaProducto>  $busquedas
     * @return array<string, mixed>
     */
    private function sinResultadoPorSucursal(Collection $busquedas): array
    {
        $sinResultado = $busquedas
            ->filter(fn (BusquedaProducto $busqueda) => in_array($busqueda->resultado, [ResultadoBusqueda::SinStock, ResultadoBusqueda::NoExiste], true))
            ->groupBy(fn (BusquedaProducto $busqueda) => $busqueda->sucursal?->nombre ?? 'Sucursal #'.$busqueda->sucursal_id);

        return [
            'tipo' => 'barras',
            'etiquetas' => $sinResultado->keys()->all(),
            'series' => [
                ['nombre' => 'Sin resultado', 'datos' => $sinResultado->map->count()->values()->all()],
            ],
            'formato' => 'entero',
        ];
    }
}
