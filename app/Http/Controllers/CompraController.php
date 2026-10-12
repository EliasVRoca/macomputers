<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraItem;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CompraController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index(): View
    {
        $items = Compra::with(['proveedor', 'sucursal', 'usuario'])
            ->latest('fecha')
            ->latest('id')
            ->paginate(15);

        return view('compras.index', ['items' => $items]);
    }

    public function create(Request $request): View
    {
        $usuario = $request->user();

        return view('compras.create', [
            'proveedores' => Proveedor::orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'sucursales' => $usuario->esGerente()
                ? Sucursal::where('activa', true)->orderBy('nombre')->get()
                : Sucursal::whereKey($usuario->sucursal_id)->get(),
            'puedeElegirSucursal' => $usuario->esGerente(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'proveedor_id' => ['required', Rule::exists('proveedores', 'id')],
            'sucursal_id' => [$usuario->esGerente() ? 'required' : 'nullable', Rule::exists('sucursales', 'id')],
            'fecha' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', Rule::exists('productos', 'id')],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_compra' => ['required', 'numeric', 'min:0'],
        ], [
            'items.required' => 'Agregue al menos un producto a la compra.',
            'items.min' => 'Agregue al menos un producto a la compra.',
        ], [
            'proveedor_id' => 'proveedor',
            'sucursal_id' => 'sucursal',
        ]);

        $sucursalId = $usuario->esGerente() ? (int) $datos['sucursal_id'] : (int) $usuario->sucursal_id;

        DB::transaction(function () use ($datos, $sucursalId, $usuario) {
            $compra = Compra::create([
                'proveedor_id' => $datos['proveedor_id'],
                'sucursal_id' => $sucursalId,
                'user_id' => $usuario->id,
                'fecha' => $datos['fecha'],
                'total' => 0,
            ]);

            $total = 0;

            foreach ($datos['items'] as $item) {
                $cantidad = (int) $item['cantidad'];
                $precio = round((float) $item['precio_compra'], 2);
                $subtotal = round($cantidad * $precio, 2);
                $total += $subtotal;

                CompraItem::create([
                    'compra_id' => $compra->id,
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $cantidad,
                    'precio_compra' => $precio,
                    'subtotal' => $subtotal,
                ]);

                $this->stock->mover((int) $item['producto_id'], $sucursalId, $cantidad, 'compra', "Compra #{$compra->id}", $usuario->id);
            }

            $compra->update(['total' => $total]);
        });

        return redirect()->route('compras.index')->with('status', 'Compra registrada y stock sumado a la sucursal.');
    }

    public function show(Compra $compra): View
    {
        return view('compras.show', ['compra' => $compra->load(['proveedor', 'sucursal', 'usuario', 'items.producto'])]);
    }
}
