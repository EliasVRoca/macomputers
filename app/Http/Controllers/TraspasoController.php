<?php

namespace App\Http\Controllers;

use App\Http\Requests\Traspasos\FiltrarTraspasosRequest;
use App\Http\Requests\Traspasos\GuardarTraspasoRequest;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Traspaso;
use App\Models\User;
use App\Services\TraspasoService;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TraspasoController extends Controller
{
    public function __construct(private TraspasoService $traspasos) {}

    public function index(FiltrarTraspasosRequest $request): View
    {
        $usuario = $request->user();
        $filtros = $request->validated();

        $items = $this->consulta($usuario, $filtros)
            ->with(['origen', 'destino', 'usuario'])
            ->withCount('items')
            ->withSum('items as unidades', 'cantidad')
            ->paginate(15)
            ->withQueryString();

        return view('traspasos.index', [
            'items' => $items,
            'filtros' => $filtros,
            'sucursales' => Sucursal::where('activa', true)->orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'puedeElegirSucursal' => $usuario->esGerente(),
        ]);
    }

    public function create(Request $request): View
    {
        $usuario = $request->user();

        return view('traspasos.form', [
            'sucursalesOrigen' => $usuario->esGerente()
                ? Sucursal::where('activa', true)->orderBy('nombre')->get()
                : Sucursal::whereKey($usuario->sucursal_id)->get(),
            'sucursalesDestino' => Sucursal::where('activa', true)->orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'puedeElegirOrigen' => $usuario->esGerente(),
        ]);
    }

    public function store(GuardarTraspasoRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        $datos = $request->validated();

        $origenId = $usuario->esGerente() ? (int) $datos['sucursal_origen_id'] : (int) $usuario->sucursal_id;

        $this->traspasos->crear($origenId, (int) $datos['sucursal_destino_id'], $datos['items'], $usuario, $datos['fecha']);

        return redirect()->route('traspasos.index')
            ->with('status', 'Traspaso registrado y stock actualizado en ambas sucursales.');
    }

    public function show(Request $request, Traspaso $traspaso): View
    {
        $this->autorizarSucursal($request->user(), $traspaso);

        return view('traspasos.show', [
            'traspaso' => $traspaso->load(['origen', 'destino', 'usuario', 'items.producto']),
        ]);
    }

    public function export(FiltrarTraspasosRequest $request): StreamedResponse
    {
        $usuario = $request->user();
        $filtros = $request->validated();

        $filas = $this->consulta($usuario, $filtros)
            ->with(['origen', 'destino', 'usuario', 'items.producto'])
            ->lazy()
            ->flatMap(fn (Traspaso $traspaso): array => $traspaso->items->map(fn ($item): array => [
                $traspaso->fecha?->format('d/m/Y'),
                $traspaso->numero(),
                $traspaso->origen?->nombre,
                $traspaso->destino?->nombre,
                $item->producto?->nombre,
                $item->producto?->codigo,
                $item->cantidad,
                $traspaso->usuario?->username ?? '—',
            ])->all());

        return Csv::descargar(
            'traspasos-'.now()->format('Ymd-His').'.csv',
            ['Fecha', 'Traspaso', 'Origen', 'Destino', 'Producto', 'Código', 'Cantidad', 'Registró'],
            $filas,
        );
    }

    /**
     * Consulta base: scoping por sucursal (no gerente) y filtros.
     *
     * @param  array<string, mixed>  $filtros
     */
    private function consulta(User $usuario, array $filtros): Builder
    {
        return Traspaso::query()
            ->when(! $usuario->esGerente(), fn (Builder $query) => $query->where(fn (Builder $w) => $w
                ->where('sucursal_origen_id', $usuario->sucursal_id)
                ->orWhere('sucursal_destino_id', $usuario->sucursal_id)))
            ->when($filtros['desde'] ?? null, fn (Builder $query, $desde) => $query->whereDate('fecha', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn (Builder $query, $hasta) => $query->whereDate('fecha', '<=', $hasta))
            ->when($filtros['sucursal_origen_id'] ?? null, fn (Builder $query, $sid) => $query->where('sucursal_origen_id', $sid))
            ->when($filtros['sucursal_destino_id'] ?? null, fn (Builder $query, $sid) => $query->where('sucursal_destino_id', $sid))
            ->when($filtros['producto_id'] ?? null, fn (Builder $query, $pid) => $query->whereHas('items', fn (Builder $i) => $i->where('producto_id', $pid)))
            ->latest('fecha')
            ->latest('id');
    }

    private function autorizarSucursal(User $usuario, Traspaso $traspaso): void
    {
        abort_unless(
            $usuario->esGerente()
                || $traspaso->sucursal_origen_id === $usuario->sucursal_id
                || $traspaso->sucursal_destino_id === $usuario->sucursal_id,
            403,
            'No puede consultar este traspaso.',
        );
    }
}
