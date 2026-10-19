<?php

namespace App\Http\Controllers;

use App\Http\Requests\Encargos\FiltrarEncargosRequest;
use App\Http\Requests\Encargos\GuardarEncargoRequest;
use App\Http\Requests\Encargos\RegistrarAbonoRequest;
use App\Models\Cliente;
use App\Models\Encargo;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Services\ClienteService;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EncargoController extends Controller
{
    public function __construct(private ClienteService $clientes) {}

    public function index(FiltrarEncargosRequest $request): View
    {
        $usuario = $request->user();
        $filtros = $request->validated();

        return view('encargos.index', [
            'items' => $this->listado($usuario, $filtros)->paginate(15)->withQueryString(),
            'filtros' => $filtros,
        ]);
    }

    public function create(Request $request): View
    {
        $descripcion = mb_substr(trim((string) $request->query('descripcion', '')), 0, 255);

        return view('encargos.form', [
            'encargo' => new Encargo([
                'descripcion' => $descripcion !== '' ? $descripcion : null,
                'total' => 0,
                'anticipo' => 0,
                'saldo' => 0,
                'fecha' => now()->toDateString(),
            ]),
            'accion' => route('encargos.store'),
            'metodo' => 'POST',
            'clientes' => Cliente::where('activo', true)->orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'sucursales' => $this->sucursales($request),
            'puedeElegirSucursal' => $request->user()->esGerente(),
        ]);
    }

    public function store(GuardarEncargoRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        $datos = $request->validated();

        $clienteId = $this->clientes->resolver($datos);
        $producto = ($datos['producto_id'] ?? null) ? Producto::find($datos['producto_id']) : null;
        $total = round((float) $datos['total'], 2);
        $saldo = round($total - (float) $datos['anticipo'], 2);

        Encargo::create([
            'cliente_id' => $clienteId,
            'sucursal_id' => $usuario->esGerente() ? (int) $datos['sucursal_id'] : (int) $usuario->sucursal_id,
            'user_id' => $usuario->id,
            'producto_id' => $producto?->id,
            'descripcion' => $datos['descripcion'] ?? $producto?->nombre,
            'total' => $total,
            'anticipo' => $datos['anticipo'],
            'saldo' => $saldo,
            'estado' => $saldo <= 0 ? Encargo::ESTADO_COMPLETADO : Encargo::ESTADO_PENDIENTE,
            'fecha' => $datos['fecha'],
        ]);

        return redirect()->route('encargos.index')->with('status', 'Encargo registrado.');
    }

    public function show(Request $request, Encargo $encargo): View
    {
        $this->autorizarSucursal($request, $encargo);

        return view('encargos.show', ['encargo' => $encargo->load(['cliente', 'producto', 'sucursal', 'usuario'])]);
    }

    public function edit(Request $request, Encargo $encargo): View|RedirectResponse
    {
        $this->autorizarSucursal($request, $encargo);

        if ($encargo->estado === Encargo::ESTADO_COMPLETADO) {
            return redirect()->route('encargos.index')->withErrors(['encargo' => 'El encargo ya está completado.']);
        }

        return view('encargos.form', [
            'encargo' => $encargo->load(['cliente', 'producto']),
            'accion' => route('encargos.update', $encargo),
            'metodo' => 'PUT',
        ]);
    }

    /**
     * Registra el pago del saldo pendiente al entregar el producto (CU12).
     */
    public function update(RegistrarAbonoRequest $request, Encargo $encargo): RedirectResponse
    {
        $this->autorizarSucursal($request, $encargo);

        if ($encargo->estado === Encargo::ESTADO_COMPLETADO) {
            return redirect()->route('encargos.index')->withErrors(['encargo' => 'El encargo ya está completado.']);
        }

        $datos = $request->validated();
        $saldo = round((float) $encargo->saldo - (float) $datos['abono'], 2);

        $encargo->update([
            'saldo' => $saldo,
            'estado' => $saldo <= 0 ? Encargo::ESTADO_COMPLETADO : Encargo::ESTADO_PENDIENTE,
        ]);

        return redirect()->route('encargos.index')->with('status', $saldo <= 0
            ? 'Saldo completado. El encargo queda finalizado.'
            : 'Pago registrado. Saldo pendiente actualizado.');
    }

    public function destroy(Request $request, Encargo $encargo): RedirectResponse
    {
        $this->autorizarSucursal($request, $encargo);

        $encargo->delete();

        return redirect()->route('encargos.index')->with('status', 'Encargo eliminado.');
    }

    public function export(FiltrarEncargosRequest $request): StreamedResponse
    {
        $usuario = $request->user();
        $filtros = $request->validated();

        $filas = $this->listado($usuario, $filtros)->lazy()->map(fn (Encargo $encargo): array => [
            $encargo->id,
            $encargo->fecha?->format('d/m/Y'),
            $encargo->cliente?->nombre,
            $encargo->producto?->nombre ?? $encargo->descripcion,
            number_format((float) $encargo->total, 2, '.', ''),
            number_format((float) $encargo->anticipo, 2, '.', ''),
            number_format((float) $encargo->saldo, 2, '.', ''),
            ucfirst($encargo->estado),
            $encargo->sucursal?->nombre,
            $encargo->usuario?->username ?? '—',
        ]);

        return Csv::descargar(
            'encargos-'.now()->format('Ymd-His').'.csv',
            ['N°', 'Fecha', 'Cliente', 'Producto', 'Total', 'Anticipo', 'Saldo', 'Estado', 'Sucursal', 'Usuario'],
            $filas,
        );
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function listado($usuario, array $filtros): Builder
    {
        $buscar = (string) ($filtros['buscar'] ?? '');

        return Encargo::query()
            ->with(['cliente', 'producto', 'sucursal', 'usuario'])
            ->when(! $usuario->esGerente(), fn (Builder $q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->when($filtros['estado'] ?? null, fn (Builder $q) => $q->where('estado', $filtros['estado']))
            ->when($filtros['desde'] ?? null, fn (Builder $q) => $q->whereDate('fecha', '>=', $filtros['desde']))
            ->when($filtros['hasta'] ?? null, fn (Builder $q) => $q->whereDate('fecha', '<=', $filtros['hasta']))
            ->when($buscar !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('descripcion', 'like', "%{$buscar}%")
                ->orWhereHas('cliente', fn (Builder $c) => $c->where('nombre', 'like', "%{$buscar}%"))
                ->orWhereHas('producto', fn (Builder $p) => $p->where('nombre', 'like', "%{$buscar}%"))))
            ->latest('id');
    }

    private function sucursales(Request $request)
    {
        $usuario = $request->user();

        return $usuario->esGerente()
            ? Sucursal::where('activa', true)->orderBy('nombre')->get()
            : Sucursal::whereKey($usuario->sucursal_id)->get();
    }

    private function autorizarSucursal(Request $request, Encargo $encargo): void
    {
        $usuario = $request->user();

        abort_unless($usuario->esGerente() || $usuario->sucursal_id === $encargo->sucursal_id, 403, 'El encargo pertenece a otra sucursal.');
    }
}
