<?php

namespace App\Http\Controllers;

use App\Models\Cambio;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CambioController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index(Request $request): View
    {
        $usuario = $request->user();

        $items = Cambio::with(['venta', 'producto', 'reemplazo', 'usuario'])
            ->when(! $usuario->esGerente(), fn ($q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->latest('fecha')
            ->latest('id')
            ->paginate(15);

        return view('cambios.index', ['items' => $items]);
    }

    public function create(Request $request): View
    {
        $usuario = $request->user();

        $ventas = Venta::with('items')
            ->where('tipo', Venta::TIPO_VENTA)
            ->where('estado', Venta::ESTADO_ACTIVA)
            ->when(! $usuario->esGerente(), fn ($q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->latest('fecha')
            ->limit(200)
            ->get();

        return view('cambios.create', [
            'ventas' => $ventas,
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'venta_id' => ['required', Rule::exists('ventas', 'id')],
            'producto_id' => ['required', Rule::exists('productos', 'id')],
            'tipo' => ['required', Rule::in([Cambio::TIPO_CAMBIO, Cambio::TIPO_DEVOLUCION])],
            'producto_reemplazo_id' => ['required_if:tipo,'.Cambio::TIPO_CAMBIO, 'nullable', Rule::exists('productos', 'id')],
            'motivo' => ['required', 'string', 'max:500'],
            'fecha' => ['required', 'date'],
        ], [
            'producto_reemplazo_id.required_if' => 'Seleccione el producto de reemplazo para un cambio.',
        ], [
            'venta_id' => 'venta',
            'producto_id' => 'producto',
            'producto_reemplazo_id' => 'producto de reemplazo',
        ]);

        $venta = Venta::findOrFail($datos['venta_id']);

        abort_unless($usuario->esGerente() || $usuario->sucursal_id === $venta->sucursal_id, 403, 'La venta pertenece a otra sucursal.');

        if ($venta->estado !== Venta::ESTADO_ACTIVA || $venta->tipo !== Venta::TIPO_VENTA) {
            return back()->withErrors(['venta_id' => 'Solo se pueden registrar cambios sobre ventas activas de productos.'])->withInput();
        }

        $perteneceAVenta = VentaItem::where('venta_id', $venta->id)->where('producto_id', $datos['producto_id'])->exists();

        if (! $perteneceAVenta) {
            return back()->withErrors(['producto_id' => 'El producto no pertenece a la venta seleccionada.'])->withInput();
        }

        DB::transaction(function () use ($datos, $venta, $usuario) {
            $cambio = Cambio::create([
                'venta_id' => $venta->id,
                'producto_id' => $datos['producto_id'],
                'producto_reemplazo_id' => $datos['tipo'] === Cambio::TIPO_CAMBIO ? $datos['producto_reemplazo_id'] : null,
                'sucursal_id' => $venta->sucursal_id,
                'user_id' => $usuario->id,
                'tipo' => $datos['tipo'],
                'motivo' => $datos['motivo'],
                'fecha' => $datos['fecha'],
            ]);

            $referencia = "Cambio #{$cambio->id}";

            if ($datos['tipo'] === Cambio::TIPO_CAMBIO) {
                // Sale el producto de reemplazo entregado al cliente.
                $this->stock->mover((int) $datos['producto_reemplazo_id'], $venta->sucursal_id, -1, 'cambio', $referencia, $usuario->id);
            } else {
                // Ingresa el producto devuelto al inventario.
                $this->stock->mover((int) $datos['producto_id'], $venta->sucursal_id, 1, 'devolucion', $referencia, $usuario->id);
            }
        });

        return redirect()->route('cambios.index')->with('status', 'Cambio registrado y stock actualizado.');
    }

    public function show(Request $request, Cambio $cambio): View
    {
        $usuario = $request->user();

        abort_unless($usuario->esGerente() || $usuario->sucursal_id === $cambio->sucursal_id, 403, 'El cambio pertenece a otra sucursal.');

        return view('cambios.show', ['cambio' => $cambio->load(['venta', 'producto', 'reemplazo', 'sucursal', 'usuario'])]);
    }
}
