<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cambios\FiltrarCambiosRequest;
use App\Http\Requests\Cambios\GuardarCambioRequest;
use App\Models\Cambio;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\StockService;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CambioController extends Controller
{
    public function __construct(private StockService $stock) {}

    public function index(FiltrarCambiosRequest $request): View
    {
        $usuario = $request->user();
        $filtros = $request->validated();

        return view('cambios.index', [
            'items' => $this->listado($usuario, $filtros)->paginate(15)->withQueryString(),
            'filtros' => $filtros,
        ]);
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

        return view('cambios.form', [
            'ventas' => $ventas,
            'productos' => Producto::where('activo', true)
                ->withSum('inventarios as stock_total', 'cantidad')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(GuardarCambioRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        $datos = $request->validated();

        $venta = Venta::findOrFail($datos['venta_id']);

        abort_unless(
            $usuario->esGerente() || $usuario->sucursal_id === $venta->sucursal_id,
            403,
            'La venta pertenece a otra sucursal.',
        );

        $cantidad = (int) $datos['cantidad'];

        DB::transaction(function () use ($datos, $venta, $usuario, $cantidad) {
            $cambio = Cambio::create([
                'venta_id' => $venta->id,
                'producto_id' => $datos['producto_id'],
                'producto_reemplazo_id' => $datos['tipo'] === Cambio::TIPO_CAMBIO ? $datos['producto_reemplazo_id'] : null,
                'cantidad' => $cantidad,
                'monto_diferencia' => $datos['monto_diferencia'] ?? 0,
                'sucursal_id' => $venta->sucursal_id,
                'user_id' => $usuario->id,
                'tipo' => $datos['tipo'],
                'motivo' => $datos['motivo'],
                'fecha' => $datos['fecha'],
            ]);

            $referencia = "Cambio #{$cambio->id}";

            if ($datos['tipo'] === Cambio::TIPO_CAMBIO) {
                // Sale el producto de reemplazo entregado al cliente.
                $this->stock->mover((int) $datos['producto_reemplazo_id'], $venta->sucursal_id, -$cantidad, 'cambio', $referencia, $usuario->id);
            } else {
                // Ingresa el producto devuelto al inventario.
                $this->stock->mover((int) $datos['producto_id'], $venta->sucursal_id, $cantidad, 'devolucion', $referencia, $usuario->id);
            }
        });

        return redirect()->route('cambios.index')->with('status', 'Cambio registrado y stock actualizado.');
    }

    public function show(Request $request, Cambio $cambio): View
    {
        $this->autorizarSucursal($request, $cambio);

        return view('cambios.show', ['cambio' => $cambio->load(['venta', 'producto', 'reemplazo', 'sucursal', 'usuario'])]);
    }

    public function export(FiltrarCambiosRequest $request): StreamedResponse
    {
        $usuario = $request->user();
        $filtros = $request->validated();

        $filas = $this->listado($usuario, $filtros)->lazy()->map(fn (Cambio $cambio): array => [
            $cambio->id,
            $cambio->fecha?->format('d/m/Y'),
            $cambio->venta?->numero(),
            $cambio->producto?->nombre,
            $cambio->reemplazo?->nombre ?? '—',
            $cambio->cantidad,
            ucfirst($cambio->tipo),
            number_format((float) $cambio->monto_diferencia, 2, '.', ''),
            $cambio->sucursal?->nombre,
            $cambio->usuario?->username ?? '—',
        ]);

        return Csv::descargar(
            'cambios-'.now()->format('Ymd-His').'.csv',
            ['N°', 'Fecha', 'Nota de venta', 'Producto', 'Reemplazo', 'Cantidad', 'Tipo', 'Diferencia', 'Sucursal', 'Usuario'],
            $filas,
        );
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function listado($usuario, array $filtros): Builder
    {
        $buscar = (string) ($filtros['buscar'] ?? '');

        return Cambio::query()
            ->with(['venta', 'producto', 'reemplazo', 'sucursal', 'usuario'])
            ->when(! $usuario->esGerente(), fn (Builder $q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->when($filtros['tipo'] ?? null, fn (Builder $q) => $q->where('tipo', $filtros['tipo']))
            ->when($filtros['desde'] ?? null, fn (Builder $q) => $q->whereDate('fecha', '>=', $filtros['desde']))
            ->when($filtros['hasta'] ?? null, fn (Builder $q) => $q->whereDate('fecha', '<=', $filtros['hasta']))
            ->when($buscar !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('motivo', 'like', "%{$buscar}%")
                ->orWhereHas('producto', fn (Builder $p) => $p->where('nombre', 'like', "%{$buscar}%"))))
            ->latest('fecha')
            ->latest('id');
    }

    private function autorizarSucursal(Request $request, Cambio $cambio): void
    {
        $usuario = $request->user();

        abort_unless($usuario->esGerente() || $usuario->sucursal_id === $cambio->sucursal_id, 403, 'El cambio pertenece a otra sucursal.');
    }
}
