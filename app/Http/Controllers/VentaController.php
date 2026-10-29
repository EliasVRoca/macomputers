<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ventas\FiltrarVentasRequest;
use App\Http\Requests\Ventas\GuardarVentaRequest;
use App\Http\Requests\Ventas\TraerProductoRequest;
use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Services\ClienteService;
use App\Services\StockService;
use App\Services\TraspasoService;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VentaController extends Controller
{
    public function __construct(
        private StockService $stock,
        private ClienteService $clientes,
    ) {}

    public function index(FiltrarVentasRequest $request): View
    {
        $usuario = $request->user();
        $filtros = $request->validated();

        return view('ventas.index', [
            'items' => $this->listado($usuario, $filtros)->paginate(15)->withQueryString(),
            'filtros' => $filtros,
            'clientes' => Cliente::orderBy('nombre')->get(),
            'sucursales' => $this->sucursales($request),
            'puedeElegirSucursal' => $usuario->esGerente(),
        ]);
    }

    public function create(Request $request): View
    {
        $usuario = $request->user();
        $productoId = $request->integer('producto');
        $productoInicial = $productoId > 0
            ? Producto::query()
                ->select(['id', 'nombre', 'codigo', 'marca', 'precio_venta'])
                ->where('activo', true)
                ->find($productoId)
            : null;

        return view('ventas.form', [
            'sucursales' => $this->sucursales($request),
            'clientes' => Cliente::where('activo', true)->orderBy('nombre')->get(),
            'productoInicial' => $productoInicial,
            'productoSugerido' => $productoInicial?->id,
            'medios' => Venta::MEDIOS_PAGO,
            'puedeElegirSucursal' => $usuario->esGerente(),
            'stockMapa' => $this->stockMapa($usuario),
            'puedeTraspasar' => $usuario->tienePermiso('traspasos'),
        ]);
    }

    public function store(GuardarVentaRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        $datos = $request->validated();

        $sucursalId = $usuario->esGerente() ? (int) $datos['sucursal_id'] : (int) $usuario->sucursal_id;

        $venta = DB::transaction(function () use ($datos, $sucursalId, $usuario) {
            $clienteId = $this->clientes->resolver($datos);

            $venta = Venta::create([
                'sucursal_id' => $sucursalId,
                'cliente_id' => $clienteId,
                'user_id' => $usuario->id,
                'tipo' => Venta::TIPO_VENTA,
                'fecha' => now(),
                'medio_pago' => $datos['medio_pago'],
                'total' => 0,
                'estado' => Venta::ESTADO_ACTIVA,
            ]);

            $total = $this->registrarItems($venta, $datos['items'], $sucursalId, $usuario->id);

            $venta->update(['total' => $total]);

            return $venta;
        });

        return redirect()->route('ventas.show', $venta)->with('status', "Venta registrada. Nota de venta {$venta->numero()}.");
    }

    public function show(Request $request, Venta $venta): View
    {
        $this->autorizarSucursal($request, $venta);

        return view('ventas.show', ['venta' => $venta->load(['cliente', 'sucursal', 'usuario', 'items', 'apartado'])]);
    }

    public function imprimir(Request $request, Venta $venta): View
    {
        $this->autorizarSucursal($request, $venta);

        return view('ventas.imprimir', ['venta' => $venta->load(['cliente', 'sucursal', 'usuario', 'items'])]);
    }

    public function anular(Request $request, Venta $venta): RedirectResponse
    {
        $usuario = $request->user();
        $this->autorizarSucursal($request, $venta);

        DB::transaction(function () use ($venta, $usuario): void {
            $venta = Venta::query()->lockForUpdate()->findOrFail($venta->id);

            if ($venta->tipo !== Venta::TIPO_VENTA) {
                throw ValidationException::withMessages(['anular' => 'Solo se pueden anular ventas de productos.']);
            }

            if ($venta->estado === Venta::ESTADO_ANULADA) {
                throw ValidationException::withMessages(['anular' => 'La venta ya se encuentra anulada.']);
            }

            if ($venta->apartado()->exists()) {
                throw ValidationException::withMessages(['anular' => 'La venta proviene de un apartado y no puede anularse desde aquí.']);
            }

            if ($venta->cambios()->exists()) {
                throw ValidationException::withMessages(['anular' => 'La venta tiene cambios o devoluciones registrados; no puede anularse.']);
            }

            foreach ($venta->items()->whereNotNull('producto_id')->get() as $item) {
                $this->stock->mover($item->producto_id, $venta->sucursal_id, $item->cantidad, 'anulacion_venta', 'Anulación '.$venta->numero(), $usuario->id);
            }

            $venta->update(['estado' => Venta::ESTADO_ANULADA]);
        });

        return redirect()->route('ventas.show', $venta)->with('status', 'Venta anulada y stock devuelto a la sucursal.');
    }

    /**
     * Atajo desde el formulario de venta: trae unidades de otra sucursal cuando
     * el producto no tiene stock en la sucursal de la venta.
     */
    public function traer(TraerProductoRequest $request, TraspasoService $traspasos): JsonResponse
    {
        $usuario = $request->user();
        $datos = $request->validated();

        $destinoId = $usuario->esGerente() ? (int) $datos['sucursal_destino_id'] : (int) $usuario->sucursal_id;

        $traspasos->crear((int) $datos['sucursal_origen_id'], $destinoId, [[
            'producto_id' => (int) $datos['producto_id'],
            'cantidad' => (int) $datos['cantidad'],
        ]], $usuario, now()->toDateString());

        return response()->json([
            'ok' => true,
            'stock' => $this->stock->disponible((int) $datos['producto_id'], $destinoId),
        ]);
    }

    public function export(FiltrarVentasRequest $request): StreamedResponse
    {
        $usuario = $request->user();
        $filtros = $request->validated();

        $filas = $this->listado($usuario, $filtros)->lazy()->map(fn (Venta $venta): array => [
            $venta->numero(),
            $venta->fecha?->format('d/m/Y H:i'),
            $venta->cliente?->nombre ?? 'Sin cliente',
            $venta->sucursal?->nombre,
            $venta->medio_pago,
            number_format((float) $venta->total, 2, '.', ''),
            $venta->estado === Venta::ESTADO_ANULADA ? 'Anulada' : 'Activa',
            $venta->usuario?->username ?? '—',
        ]);

        return Csv::descargar(
            'ventas-'.now()->format('Ymd-His').'.csv',
            ['Nota', 'Fecha', 'Cliente', 'Sucursal', 'Medio de pago', 'Total', 'Estado', 'Usuario'],
            $filas,
        );
    }

    /**
     * @param  array<int, array{producto_id: int|string, cantidad: int|string}>  $items
     */
    private function registrarItems(Venta $venta, array $items, int $sucursalId, int $usuarioId): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $producto = Producto::findOrFail($item['producto_id']);
            $cantidad = (int) $item['cantidad'];
            $subtotal = round($cantidad * (float) $producto->precio_venta, 2);
            $total += $subtotal;

            VentaItem::create([
                'venta_id' => $venta->id,
                'producto_id' => $producto->id,
                'descripcion' => $producto->nombre,
                'cantidad' => $cantidad,
                'precio_unitario' => $producto->precio_venta,
                'subtotal' => $subtotal,
            ]);

            $this->stock->mover($producto->id, $sucursalId, -$cantidad, 'venta', 'Venta '.$venta->numero(), $usuarioId);
        }

        return round($total, 2);
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function listado($usuario, array $filtros): Builder
    {
        $buscar = (string) ($filtros['buscar'] ?? '');
        $notaId = preg_match('/^(?:NV-)?0*(\d+)$/i', $buscar, $coincidencia) === 1
            ? (int) $coincidencia[1]
            : null;

        return Venta::query()
            ->with(['cliente', 'sucursal', 'usuario'])
            ->where('tipo', Venta::TIPO_VENTA)
            ->when(! $usuario->esGerente(), fn (Builder $q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->when($usuario->esGerente() && ($filtros['sucursal_id'] ?? null), fn (Builder $q) => $q->where('sucursal_id', $filtros['sucursal_id']))
            ->when($filtros['cliente_id'] ?? null, fn (Builder $q) => $q->where('cliente_id', $filtros['cliente_id']))
            ->when($filtros['estado'] ?? null, fn (Builder $q) => $q->where('estado', $filtros['estado']))
            ->when($filtros['desde'] ?? null, fn (Builder $q) => $q->whereDate('fecha', '>=', $filtros['desde']))
            ->when($filtros['hasta'] ?? null, fn (Builder $q) => $q->whereDate('fecha', '<=', $filtros['hasta']))
            ->when($buscar !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereHas('cliente', fn (Builder $c) => $c->where('nombre', 'like', "%{$buscar}%"))
                ->orWhereHas('items', fn (Builder $i) => $i->where('descripcion', 'like', "%{$buscar}%"))
                ->orWhere('id', $notaId ?? 0)))
            ->latest('fecha')
            ->latest('id');
    }

    private function sucursales(Request $request)
    {
        $usuario = $request->user();

        return $usuario->esGerente()
            ? Sucursal::where('activa', true)->orderBy('nombre')->get()
            : Sucursal::whereKey($usuario->sucursal_id)->get();
    }

    /**
     * Mapa producto → sucursal → cantidad, para mostrar disponibilidad en el formulario.
     *
     * @return array<int|string, array<string, int>>
     */
    private function stockMapa($usuario): array
    {
        return Inventario::query()
            ->whereIn('sucursal_id', Sucursal::pluck('id'))
            ->get(['producto_id', 'sucursal_id', 'cantidad'])
            ->groupBy('producto_id')
            ->map(fn ($filas): array => $filas
                ->mapWithKeys(fn (Inventario $fila): array => [(string) $fila->sucursal_id => (int) $fila->cantidad])
                ->all())
            ->all();
    }

    private function autorizarSucursal(Request $request, Venta $venta): void
    {
        $usuario = $request->user();

        abort_unless($usuario->esGerente() || $usuario->sucursal_id === $venta->sucursal_id, 403, 'La venta pertenece a otra sucursal.');
    }
}
