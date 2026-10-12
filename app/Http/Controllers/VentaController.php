<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VentaController extends Controller
{
    public const MEDIOS_PAGO = ['Efectivo', 'Transferencia', 'Tarjeta', 'QR'];

    public function __construct(private StockService $stock)
    {
    }

    public function index(Request $request): View
    {
        $usuario = $request->user();

        $items = Venta::with(['cliente', 'sucursal', 'usuario'])
            ->where('tipo', Venta::TIPO_VENTA)
            ->when(! $usuario->esGerente(), fn ($q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->latest('fecha')
            ->latest('id')
            ->paginate(15);

        return view('ventas.index', ['items' => $items]);
    }

    public function create(Request $request): View
    {
        $usuario = $request->user();

        return view('ventas.create', [
            'sucursales' => $usuario->esGerente()
                ? Sucursal::where('activa', true)->orderBy('nombre')->get()
                : Sucursal::whereKey($usuario->sucursal_id)->get(),
            'clientes' => Cliente::orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'medios' => self::MEDIOS_PAGO,
            'puedeElegirSucursal' => $usuario->esGerente(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'sucursal_id' => [$usuario->esGerente() ? 'required' : 'nullable', Rule::exists('sucursales', 'id')],
            'cliente_id' => ['nullable', Rule::exists('clientes', 'id')],
            'medio_pago' => ['required', Rule::in(self::MEDIOS_PAGO)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', Rule::exists('productos', 'id')],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ], [
            'items.required' => 'Agregue al menos un producto a la venta.',
            'items.min' => 'Agregue al menos un producto a la venta.',
        ], [
            'sucursal_id' => 'sucursal',
            'cliente_id' => 'cliente',
            'medio_pago' => 'medio de pago',
        ]);

        $sucursalId = $usuario->esGerente() ? (int) $datos['sucursal_id'] : (int) $usuario->sucursal_id;

        $venta = DB::transaction(function () use ($datos, $sucursalId, $usuario) {
            $venta = Venta::create([
                'sucursal_id' => $sucursalId,
                'cliente_id' => $datos['cliente_id'] ?? null,
                'user_id' => $usuario->id,
                'tipo' => Venta::TIPO_VENTA,
                'fecha' => now(),
                'medio_pago' => $datos['medio_pago'],
                'total' => 0,
                'estado' => Venta::ESTADO_ACTIVA,
            ]);

            $total = 0;

            foreach ($datos['items'] as $item) {
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

                $this->stock->mover($producto->id, $sucursalId, -$cantidad, 'venta', 'Venta '.$venta->numero(), $usuario->id);
            }

            $venta->update(['total' => $total]);

            return $venta;
        });

        return redirect()->route('ventas.show', $venta)->with('status', "Venta registrada. Nota de venta {$venta->numero()}.");
    }

    public function show(Request $request, Venta $venta): View
    {
        $this->autorizarSucursal($request, $venta);

        return view('ventas.show', ['venta' => $venta->load(['cliente', 'sucursal', 'usuario', 'items'])]);
    }

    public function anular(Request $request, Venta $venta): RedirectResponse
    {
        $usuario = $request->user();
        $this->autorizarSucursal($request, $venta);

        if ($venta->tipo !== Venta::TIPO_VENTA) {
            return back()->withErrors(['anular' => 'Solo se pueden anular ventas de productos.']);
        }

        if ($venta->estado === Venta::ESTADO_ANULADA) {
            return back()->withErrors(['anular' => 'La venta ya se encuentra anulada.']);
        }

        DB::transaction(function () use ($venta, $usuario) {
            foreach ($venta->items()->whereNotNull('producto_id')->get() as $item) {
                $this->stock->mover($item->producto_id, $venta->sucursal_id, $item->cantidad, 'anulacion_venta', 'Anulación '.$venta->numero(), $usuario->id);
            }

            $venta->update(['estado' => Venta::ESTADO_ANULADA]);
        });

        return redirect()->route('ventas.show', $venta)->with('status', 'Venta anulada y stock devuelto a la sucursal.');
    }

    private function autorizarSucursal(Request $request, Venta $venta): void
    {
        $usuario = $request->user();

        abort_unless($usuario->esGerente() || $usuario->sucursal_id === $venta->sucursal_id, 403, 'La venta pertenece a otra sucursal.');
    }
}
