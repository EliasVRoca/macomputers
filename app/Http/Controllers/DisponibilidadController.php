<?php

namespace App\Http\Controllers;

use App\Http\Requests\Inventario\FiltrarDisponibilidadRequest;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DisponibilidadController extends Controller
{
    public function index(FiltrarDisponibilidadRequest $request): View
    {
        return view('disponibilidad.index', [
            'productos' => $this->productosConStock($request->validated())
                ->paginate(20)
                ->withQueryString(),
            'sucursales' => $this->sucursales(),
            'filtros' => $request->validated(),
        ]);
    }

    public function exportar(FiltrarDisponibilidadRequest $request): StreamedResponse
    {
        $sucursales = $this->sucursales();

        $filas = $this->productosConStock($request->validated())->lazy()
            ->map(function (Producto $producto) use ($sucursales): array {
                $porSucursal = $producto->inventarios->pluck('cantidad', 'sucursal_id');
                $total = (int) $producto->inventarios->sum('cantidad');

                return array_merge(
                    [$producto->nombre, $producto->codigo, $producto->marca],
                    $sucursales->map(fn (Sucursal $sucursal): int => (int) ($porSucursal[$sucursal->id] ?? 0))->all(),
                    [$total, $total <= (int) $producto->stock_minimo ? 'Sí' : 'No'],
                );
            });

        return Csv::descargar(
            'disponibilidad-'.now()->format('Ymd-His').'.csv',
            array_merge(
                ['Producto', 'Código', 'Marca'],
                $sucursales->pluck('nombre')->all(),
                ['Total', 'Bajo mínimo'],
            ),
            $filas,
        );
    }

    /**
     * Productos activos con su inventario, filtrados por búsqueda y sucursal.
     * El filtro de sucursal deja solo los productos con existencia (> 0) en ella.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function productosConStock(array $filtros): Builder
    {
        $buscar = (string) ($filtros['buscar'] ?? '');
        $sucursalId = $filtros['sucursal_id'] ?? null;

        return Producto::query()
            ->with('inventarios')
            ->where('activo', true)
            ->when($buscar !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('codigo', 'like', "%{$buscar}%")
                ->orWhere('marca', 'like', "%{$buscar}%")))
            ->when($sucursalId, fn (Builder $q) => $q->whereHas('inventarios', fn (Builder $w) => $w
                ->where('sucursal_id', (int) $sucursalId)
                ->where('cantidad', '>', 0)))
            ->orderBy('nombre');
    }

    /**
     * @return Collection<int, Sucursal>
     */
    private function sucursales(): Collection
    {
        return Sucursal::where('activa', true)->orderBy('nombre')->get();
    }
}
