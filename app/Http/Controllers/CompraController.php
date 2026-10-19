<?php

namespace App\Http\Controllers;

use App\Http\Requests\Compras\AnularCompraRequest;
use App\Http\Requests\Compras\FiltrarComprasRequest;
use App\Http\Requests\Compras\GuardarCompraRequest;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\CompraService;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompraController extends Controller
{
    public function __construct(private CompraService $compras) {}

    public function index(FiltrarComprasRequest $request): View
    {
        $usuario = $request->user();

        return view('compras.index', [
            'items' => $this->consulta($usuario, $request->validated())->paginate(15)->withQueryString(),
            'proveedores' => Proveedor::orderBy('nombre')->get(),
            'sucursales' => $this->sucursalesVisibles($usuario),
            'filtros' => $request->validated(),
            'puedeElegirSucursal' => $usuario->esGerente(),
        ]);
    }

    public function create(Request $request): View
    {
        $usuario = $request->user();

        return view('compras.form', [
            'proveedores' => Proveedor::orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'sucursales' => $this->sucursalesVisibles($usuario),
            'puedeElegirSucursal' => $usuario->esGerente(),
        ]);
    }

    public function store(GuardarCompraRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        $datos = $request->validated();

        $sucursalId = $usuario->esGerente() ? (int) $datos['sucursal_id'] : (int) $usuario->sucursal_id;

        $this->compras->registrar(
            (int) $datos['proveedor_id'],
            $sucursalId,
            $datos['items'],
            $usuario,
            $datos['fecha'],
        );

        return redirect()->route('compras.index')->with('status', 'Compra registrada y stock sumado a la sucursal.');
    }

    public function show(Request $request, Compra $compra): View
    {
        $usuario = $request->user();

        abort_unless($usuario->esGerente() || $compra->sucursal_id === $usuario->sucursal_id, 403, 'No puede consultar otra sucursal.');

        return view('compras.show', [
            'compra' => $compra->load(['proveedor', 'sucursal', 'usuario', 'anuladaPor', 'items.producto']),
        ]);
    }

    public function anular(AnularCompraRequest $request, Compra $compra): RedirectResponse
    {
        $usuario = $request->user();

        abort_unless($usuario->esGerente() || $compra->sucursal_id === $usuario->sucursal_id, 403, 'No puede anular una compra de otra sucursal.');

        $this->compras->anular($compra, $usuario, $request->validated('motivo'));

        return redirect()->route('compras.show', $compra)->with('status', 'Compra anulada y stock revertido.');
    }

    public function exportar(FiltrarComprasRequest $request): StreamedResponse
    {
        $filas = $this->consulta($request->user(), $request->validated())
            ->withCount('items')
            ->lazy()
            ->map(fn (Compra $compra): array => [
                $compra->fecha?->format('d/m/Y'),
                $compra->proveedor?->nombre,
                $compra->sucursal?->nombre,
                $compra->items_count,
                $compra->total,
                $compra->usuario?->username ?? '—',
                $compra->estaAnulada() ? 'Anulada' : 'Activa',
            ]);

        return Csv::descargar(
            'compras-'.now()->format('Ymd-His').'.csv',
            ['Fecha', 'Proveedor', 'Sucursal', 'Ítems', 'Total', 'Registró', 'Estado'],
            $filas,
        );
    }

    /**
     * Consulta base del listado y la exportación, con filtros y aislamiento por
     * sucursal: el gerente puede filtrar; el resto queda en la suya.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(User $usuario, array $filtros): Builder
    {
        $sucursalId = $usuario->esGerente() ? ($filtros['sucursal_id'] ?? null) : $usuario->sucursal_id;

        return Compra::query()
            ->with(['proveedor', 'sucursal', 'usuario'])
            ->when($sucursalId, fn (Builder $q) => $q->where('sucursal_id', (int) $sucursalId))
            ->when($filtros['proveedor_id'] ?? null, fn (Builder $q) => $q->where('proveedor_id', (int) $filtros['proveedor_id']))
            ->when($filtros['desde'] ?? null, fn (Builder $q) => $q->whereDate('fecha', '>=', $filtros['desde']))
            ->when($filtros['hasta'] ?? null, fn (Builder $q) => $q->whereDate('fecha', '<=', $filtros['hasta']))
            ->when(! ($filtros['incluir_anuladas'] ?? false), fn (Builder $q) => $q->whereNull('anulada_at'))
            ->latest('fecha')
            ->latest('id');
    }

    /**
     * @return Collection<int, Sucursal>
     */
    private function sucursalesVisibles(User $usuario): Collection
    {
        return $usuario->esGerente()
            ? Sucursal::where('activa', true)->orderBy('nombre')->get()
            : Sucursal::whereKey($usuario->sucursal_id)->get();
    }
}
