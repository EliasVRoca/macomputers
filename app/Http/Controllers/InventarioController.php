<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventarioController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index(Request $request): View
    {
        $usuario = $request->user();
        $sucursalId = $usuario->esGerente() ? $request->integer('sucursal_id') ?: null : $usuario->sucursal_id;

        $existencias = Inventario::query()
            ->with(['producto', 'sucursal'])
            ->join('productos', 'productos.id', '=', 'inventarios.producto_id')
            ->select('inventarios.*')
            ->when($sucursalId, fn ($q) => $q->where('inventarios.sucursal_id', $sucursalId))
            ->orderBy('productos.nombre')
            ->paginate(15, ['*'], 'existencias')
            ->withQueryString();

        $kardex = MovimientoInventario::query()
            ->with(['producto', 'sucursal', 'usuario'])
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->latest('id')
            ->paginate(15, ['*'], 'kardex')
            ->withQueryString();

        return view('inventario.index', [
            'existencias' => $existencias,
            'kardex' => $kardex,
            'sucursales' => $this->sucursalesVisibles($usuario),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'sucursalFiltro' => $sucursalId,
            'puedeElegirSucursal' => $usuario->esGerente(),
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

    public function movimiento(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'producto_id' => ['required', Rule::exists('productos', 'id')],
            'sucursal_id' => [$usuario->esGerente() ? 'required' : 'nullable', Rule::exists('sucursales', 'id')],
            'tipo' => ['required', Rule::in(['entrada', 'salida', 'ajuste'])],
            'cantidad' => ['required', 'integer', 'min:0'],
            'referencia' => ['nullable', 'string', 'max:150'],
        ], [], [
            'producto_id' => 'producto',
            'sucursal_id' => 'sucursal',
            'cantidad' => 'cantidad',
        ]);

        $sucursalId = $usuario->esGerente() ? (int) $datos['sucursal_id'] : (int) $usuario->sucursal_id;
        $productoId = (int) $datos['producto_id'];
        $cantidad = (int) $datos['cantidad'];
        $referencia = $datos['referencia'] ?? null;

        if ($datos['tipo'] === 'ajuste') {
            $actual = $this->stock->disponible($productoId, $sucursalId);
            $delta = $cantidad - $actual;

            if ($delta === 0) {
                return back()->withErrors(['cantidad' => 'La cantidad indicada ya coincide con el stock actual.'])->withInput();
            }

            $this->stock->mover($productoId, $sucursalId, $delta, 'ajuste', $referencia ?? "Ajuste a {$cantidad} unidades", $usuario->id);
        } else {
            if ($cantidad < 1) {
                return back()->withErrors(['cantidad' => 'La cantidad debe ser mayor a cero.'])->withInput();
            }

            $delta = $datos['tipo'] === 'entrada' ? $cantidad : -$cantidad;
            $this->stock->mover($productoId, $sucursalId, $delta, $datos['tipo'], $referencia, $usuario->id);
        }

        return redirect()->route('inventario.index', $usuario->esGerente() ? ['sucursal_id' => $sucursalId] : [])
            ->with('status', 'Movimiento de inventario registrado.');
    }

    private function sucursalesVisibles($usuario)
    {
        return $usuario->esGerente()
            ? Sucursal::orderBy('nombre')->get()
            : Sucursal::whereKey($usuario->sucursal_id)->get();
    }
}
