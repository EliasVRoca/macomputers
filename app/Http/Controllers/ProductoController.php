<?php

namespace App\Http\Controllers;

use App\Enums\CondicionProducto;
use App\Http\Requests\Productos\FiltrarProductosRequest;
use App\Http\Requests\Productos\GuardarProductoRequest;
use App\Models\Categoria;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductoController extends Controller
{
    public function index(FiltrarProductosRequest $request): View
    {
        $filtros = $request->validated();

        return view('productos.index', [
            'items' => $this->listado($filtros)->paginate(15)->withQueryString(),
            'categorias' => Categoria::orderBy('nombre')->get(),
            'condiciones' => CondicionProducto::opciones(),
            'filtros' => $filtros,
        ]);
    }

    public function create(): View
    {
        return $this->formulario(new Producto(['activo' => true, 'stock_minimo' => 0, 'condicion' => CondicionProducto::Nuevo]), route('productos.store'), 'POST');
    }

    public function store(GuardarProductoRequest $request): RedirectResponse
    {
        Producto::create($request->validated());

        return redirect()->route('productos.index')->with('status', 'Producto registrado.');
    }

    public function show(Producto $producto): View
    {
        return view('productos.show', [
            'producto' => $producto,
            'existencias' => $producto->inventarios()->with('sucursal')->get(),
            'movimientos' => MovimientoInventario::with(['sucursal', 'usuario'])
                ->where('producto_id', $producto->id)
                ->latest('id')
                ->limit(15)
                ->get(),
        ]);
    }

    public function edit(Producto $producto): View
    {
        return $this->formulario($producto, route('productos.update', $producto), 'PUT');
    }

    public function update(GuardarProductoRequest $request, Producto $producto): RedirectResponse
    {
        $producto->update($request->validated());

        return redirect()->route('productos.index')->with('status', 'Producto actualizado.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        try {
            $producto->delete();
        } catch (QueryException) {
            return back()->withErrors(['eliminar' => 'No se puede eliminar: el producto tiene movimientos o ventas registradas. Desactívelo en su lugar.']);
        }

        return redirect()->route('productos.index')->with('status', 'Producto eliminado.');
    }

    public function export(FiltrarProductosRequest $request): StreamedResponse
    {
        $filtros = $request->validated();

        $filas = $this->listado($filtros)->lazy()->map(fn (Producto $producto): array => [
            $producto->codigo,
            $producto->nombre,
            $producto->marca,
            $producto->modelo,
            $producto->categoria?->nombre,
            $producto->condicion->etiqueta(),
            (float) $producto->precio_compra,
            (float) $producto->precio_venta,
            (int) ($producto->stock_total ?? 0),
            $producto->stock_minimo,
            $producto->activo ? 'Sí' : 'No',
        ]);

        return Csv::descargar(
            'productos-'.now()->format('Ymd-His').'.csv',
            ['Código', 'Nombre', 'Marca', 'Modelo', 'Categoría', 'Condición', 'Precio compra', 'Precio venta', 'Stock total', 'Stock mínimo', 'Activo'],
            $filas,
        );
    }

    private function formulario(Producto $producto, string $accion, string $metodo): View
    {
        return view('productos.form', [
            'producto' => $producto,
            'accion' => $accion,
            'metodo' => $metodo,
            'categorias' => Categoria::orderBy('nombre')->get(),
            'condiciones' => CondicionProducto::opciones(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function listado(array $filtros): Builder
    {
        $buscar = (string) ($filtros['buscar'] ?? '');

        return Producto::query()
            ->with('categoria')
            ->withSum('inventarios as stock_total', 'cantidad')
            ->when($buscar !== '', fn (Builder $query) => $query->where(fn (Builder $where) => $where
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('codigo', 'like', "%{$buscar}%")
                ->orWhere('marca', 'like', "%{$buscar}%")))
            ->when($filtros['categoria_id'] ?? null, fn (Builder $query, $id) => $query->where('categoria_id', $id))
            ->when($filtros['marca'] ?? null, fn (Builder $query, $marca) => $query->where('marca', 'like', "%{$marca}%"))
            ->when($filtros['condicion'] ?? null, fn (Builder $query, $condicion) => $query->where('condicion', $condicion))
            ->when(($filtros['activo'] ?? null) !== null, fn (Builder $query) => $query->where('activo', (bool) $filtros['activo']))
            ->orderBy('nombre');
    }
}
