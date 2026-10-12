<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar', ''));

        $items = Producto::query()
            ->with('categoria')
            ->withSum('inventarios as stock_total', 'cantidad')
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('codigo', 'like', "%{$buscar}%")
                ->orWhere('marca', 'like', "%{$buscar}%")))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('productos.index', ['items' => $items, 'buscar' => $buscar]);
    }

    public function create(): View
    {
        return $this->formulario(new Producto(['activo' => true, 'stock_minimo' => 0]), route('productos.store'), 'POST');
    }

    public function store(Request $request): RedirectResponse
    {
        Producto::create($this->validar($request));

        return redirect()->route('productos.index')->with('status', 'Producto registrado.');
    }

    public function edit(Producto $producto): View
    {
        return $this->formulario($producto, route('productos.update', $producto), 'PUT');
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $producto->update($this->validar($request, $producto));

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

    private function formulario(Producto $producto, string $accion, string $metodo): View
    {
        return view('productos.form', [
            'producto' => $producto,
            'accion' => $accion,
            'metodo' => $metodo,
            'categorias' => Categoria::orderBy('nombre')->get(),
        ]);
    }

    private function validar(Request $request, ?Producto $producto = null): array
    {
        $datos = $request->validate([
            'categoria_id' => ['required', Rule::exists('categorias', 'id')],
            'nombre' => ['required', 'string', 'max:150'],
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'codigo' => ['required', 'string', 'max:50', Rule::unique('productos', 'codigo')->ignore($producto?->id)],
            'precio_compra' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'precio_venta' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
        ]);

        $datos['activo'] = $request->boolean('activo');

        return $datos;
    }
}
