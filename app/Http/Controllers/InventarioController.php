<?php

namespace App\Http\Controllers;

use App\Enums\TipoMovimiento;
use App\Http\Requests\Inventario\FiltrarInventarioRequest;
use App\Http\Requests\Inventario\RegistrarMovimientoRequest;
use App\Http\Requests\Inventario\RegistrarTraspasoRapidoRequest;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\InventarioResumenService;
use App\Services\StockService;
use App\Services\TraspasoService;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventarioController extends Controller
{
    /** Un producto está bajo mínimo cuando su total (todas las sucursales) ≤ stock mínimo. */
    private const BAJO_MINIMO = '(select coalesce(sum(i.cantidad), 0) from inventarios i where i.producto_id = productos.id) <= productos.stock_minimo';

    public function __construct(private StockService $stock) {}

    public function index(FiltrarInventarioRequest $request, InventarioResumenService $resumen): View
    {
        $usuario = $request->user();
        $filtros = $request->validated();
        $tab = $request->query('tab') === 'historial' ? 'historial' : 'existencias';
        $buscar = (string) ($filtros['buscar'] ?? '');
        $bajoMinimo = (bool) ($filtros['bajo_minimo'] ?? false);

        $existencias = $this->productosConStock($buscar, $bajoMinimo)
            ->paginate(20, ['*'], 'existencias')
            ->withQueryString();

        $sucursalKardex = $this->sucursalKardex($usuario, $filtros['sucursal_id'] ?? null);

        $kardex = $this->kardexQuery(
            $sucursalKardex,
            $filtros['producto_id'] ?? null,
            $filtros['tipo'] ?? null,
            $filtros['desde'] ?? null,
            $filtros['hasta'] ?? null,
        )->paginate(15, ['*'], 'kardex')->withQueryString();

        return view('inventario.index', [
            'tab' => $tab,
            'metricas' => $resumen->metricas(),
            'existencias' => $existencias,
            'kardex' => $kardex,
            'sucursales' => Sucursal::where('activa', true)->orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'tipos' => TipoMovimiento::opciones(),
            'filtros' => $filtros,
            'sucursalFiltro' => $sucursalKardex,
            'puedeElegirSucursal' => $usuario->esGerente(),
            'puedeTraspasar' => $usuario->tienePermiso('traspasos'),
            'stockMapa' => $this->stockMapa($usuario),
        ]);
    }

    public function sucursal(Request $request, Sucursal $sucursal): View
    {
        $usuario = $request->user();

        abort_unless($usuario->esGerente() || $usuario->sucursal_id === $sucursal->id, 403, 'No puede consultar otra sucursal.');

        $existencias = Inventario::query()
            ->with('producto')
            ->where('sucursal_id', $sucursal->id)
            ->join('productos', 'productos.id', '=', 'inventarios.producto_id')
            ->select('inventarios.*')
            ->orderBy('productos.nombre')
            ->paginate(20);

        return view('inventario.sucursal', [
            'sucursal' => $sucursal,
            'existencias' => $existencias,
            'kardex' => MovimientoInventario::with(['producto', 'usuario'])
                ->where('sucursal_id', $sucursal->id)
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function movimiento(RegistrarMovimientoRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        $datos = $request->validated();

        $sucursalId = $usuario->esGerente() ? (int) $datos['sucursal_id'] : (int) $usuario->sucursal_id;
        $productoId = (int) $datos['producto_id'];
        $cantidad = (int) $datos['cantidad'];
        $referencia = $datos['referencia'] ?? null;

        if ($datos['tipo'] === TipoMovimiento::Ajuste->value) {
            $actual = $this->stock->disponible($productoId, $sucursalId);
            $delta = $cantidad - $actual;

            if ($delta === 0) {
                return back()->withErrors(['cantidad' => 'La cantidad indicada ya coincide con el stock actual.'])->withInput();
            }

            $this->stock->mover($productoId, $sucursalId, $delta, TipoMovimiento::Ajuste->value, $referencia ?? "Ajuste a {$cantidad} unidades", $usuario->id);
        } else {
            $delta = $datos['tipo'] === TipoMovimiento::Entrada->value ? $cantidad : -$cantidad;
            $this->stock->mover($productoId, $sucursalId, $delta, $datos['tipo'], $referencia, $usuario->id);
        }

        return redirect()->route('inventario.index', ['tab' => 'historial'])
            ->with('status', 'Movimiento de inventario registrado.');
    }

    public function traer(RegistrarTraspasoRapidoRequest $request, TraspasoService $traspasos): RedirectResponse
    {
        $usuario = $request->user();
        $datos = $request->validated();

        $origenId = (int) $datos['sucursal_origen_id'];
        $destinoId = $usuario->esGerente() ? (int) $datos['sucursal_destino_id'] : (int) $usuario->sucursal_id;

        $traspasos->crear($origenId, $destinoId, [[
            'producto_id' => (int) $datos['producto_id'],
            'cantidad' => (int) $datos['cantidad'],
        ]], $usuario, now()->toDateString());

        return redirect()->route('inventario.index', ['tab' => 'existencias'])
            ->with('status', 'Producto traído de la otra sucursal.');
    }

    public function exportKardex(FiltrarInventarioRequest $request): StreamedResponse
    {
        $usuario = $request->user();
        $filtros = $request->validated();
        $sucursalId = $this->sucursalKardex($usuario, $filtros['sucursal_id'] ?? null);

        $filas = $this->kardexQuery(
            $sucursalId,
            $filtros['producto_id'] ?? null,
            $filtros['tipo'] ?? null,
            $filtros['desde'] ?? null,
            $filtros['hasta'] ?? null,
        )->lazy()->map(fn (MovimientoInventario $mov): array => [
            $mov->created_at?->format('d/m/Y H:i'),
            $mov->producto?->nombre,
            $mov->producto?->codigo,
            $mov->sucursal?->nombre,
            TipoMovimiento::desde($mov->tipo)->etiqueta(),
            $mov->cantidad,
            $mov->saldo,
            $mov->usuario?->username ?? '—',
            $mov->referencia,
        ]);

        return Csv::descargar(
            'kardex-'.now()->format('Ymd-His').'.csv',
            ['Fecha', 'Producto', 'Código', 'Sucursal', 'Tipo', 'Cantidad', 'Saldo', 'Usuario', 'Referencia'],
            $filas,
        );
    }

    public function exportExistencias(FiltrarInventarioRequest $request): StreamedResponse
    {
        $filtros = $request->validated();

        $filas = $this->existenciasDetalle(
            (string) ($filtros['buscar'] ?? ''),
            (bool) ($filtros['bajo_minimo'] ?? false),
        )->lazy()->map(fn (Inventario $inventario): array => [
            $inventario->producto?->nombre,
            $inventario->producto?->codigo,
            $inventario->sucursal?->nombre,
            $inventario->cantidad,
            $inventario->producto?->stock_minimo,
            $inventario->cantidad <= (int) ($inventario->producto?->stock_minimo ?? 0) ? 'Sí' : 'No',
        ]);

        return Csv::descargar(
            'existencias-'.now()->format('Ymd-His').'.csv',
            ['Producto', 'Código', 'Sucursal', 'Cantidad', 'Stock mínimo', 'Bajo mínimo'],
            $filas,
        );
    }

    /** Sucursal del kardex: el gerente puede filtrar; el resto queda en la suya. */
    private function sucursalKardex(User $usuario, ?int $solicitada): ?int
    {
        return $usuario->esGerente() ? $solicitada : $usuario->sucursal_id;
    }

    /** Productos activos con su stock por sucursal (vista de existencias). */
    private function productosConStock(string $buscar, bool $bajoMinimo): Builder
    {
        return Producto::query()
            ->with('inventarios')
            ->where('activo', true)
            ->when($buscar !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('codigo', 'like', "%{$buscar}%")
                ->orWhere('marca', 'like', "%{$buscar}%")))
            ->when($bajoMinimo, fn (Builder $q) => $q->whereRaw(self::BAJO_MINIMO))
            ->orderBy('nombre');
    }

    /** Existencias detalladas (una fila por producto y sucursal), para exportar. */
    private function existenciasDetalle(string $buscar, bool $bajoMinimo): Builder
    {
        return Inventario::query()
            ->with(['producto', 'sucursal'])
            ->join('productos', 'productos.id', '=', 'inventarios.producto_id')
            ->select('inventarios.*')
            ->when($buscar !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('productos.nombre', 'like', "%{$buscar}%")
                ->orWhere('productos.codigo', 'like', "%{$buscar}%")
                ->orWhere('productos.marca', 'like', "%{$buscar}%")))
            ->when($bajoMinimo, fn (Builder $q) => $q->whereRaw(self::BAJO_MINIMO))
            ->orderBy('productos.nombre');
    }

    private function kardexQuery(?int $sucursalId, ?int $productoId, ?string $tipo, ?string $desde, ?string $hasta): Builder
    {
        return MovimientoInventario::query()
            ->with(['producto', 'sucursal', 'usuario'])
            ->when($sucursalId, fn (Builder $query) => $query->where('sucursal_id', $sucursalId))
            ->when($productoId, fn (Builder $query) => $query->where('producto_id', $productoId))
            ->when($tipo, fn (Builder $query) => $query->where('tipo', $tipo))
            ->when($desde, fn (Builder $query) => $query->whereDate('created_at', '>=', $desde))
            ->when($hasta, fn (Builder $query) => $query->whereDate('created_at', '<=', $hasta))
            ->latest('id');
    }

    /**
     * Mapa producto → sucursal → cantidad, para la previsualización del saldo.
     *
     * @return array<int|string, array<string, int>>
     */
    private function stockMapa(User $usuario): array
    {
        $sucursalIds = $this->sucursalesVisibles($usuario)->pluck('id');

        return Inventario::query()
            ->whereIn('sucursal_id', $sucursalIds)
            ->get(['producto_id', 'sucursal_id', 'cantidad'])
            ->groupBy('producto_id')
            ->map(fn ($filas): array => $filas
                ->mapWithKeys(fn (Inventario $fila): array => [(string) $fila->sucursal_id => (int) $fila->cantidad])
                ->all())
            ->all();
    }

    private function sucursalesVisibles(User $usuario)
    {
        return $usuario->esGerente()
            ? Sucursal::orderBy('nombre')->get()
            : Sucursal::whereKey($usuario->sucursal_id)->get();
    }
}
