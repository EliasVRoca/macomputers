<?php

namespace App\Http\Controllers;

use App\Enums\TipoMovimiento;
use App\Http\Requests\Apartados\CancelarApartadoRequest;
use App\Http\Requests\Apartados\FiltrarApartadosRequest;
use App\Http\Requests\Apartados\GuardarApartadoRequest;
use App\Http\Requests\Apartados\RegistrarAbonoRequest;
use App\Models\Apartado;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Models\VentaItem;
use App\Services\ClienteService;
use App\Services\StockService;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApartadoController extends Controller
{
    public function __construct(
        private StockService $stock,
        private ClienteService $clientes,
    ) {}

    public function index(FiltrarApartadosRequest $request): View
    {
        $usuario = $request->user();
        $filtros = $request->validated();

        return view('apartados.index', [
            'items' => $this->listado($usuario, $filtros)->paginate(15)->withQueryString(),
            'filtros' => $filtros,
        ]);
    }

    public function create(Request $request): View
    {
        return view('apartados.form', [
            'apartado' => new Apartado(['cantidad' => 1, 'sena' => 0, 'fecha' => now()->toDateString()]),
            'accion' => route('apartados.store'),
            'metodo' => 'POST',
            'clientes' => Cliente::where('activo', true)->orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'sucursales' => $this->sucursales($request),
            'puedeElegirSucursal' => $request->user()->esGerente(),
        ]);
    }

    public function store(GuardarApartadoRequest $request): RedirectResponse
    {
        $usuario = $request->user();
        $datos = $request->validated();

        $sucursalId = $usuario->esGerente() ? (int) $datos['sucursal_id'] : (int) $usuario->sucursal_id;
        $clienteId = $this->clientes->resolver($datos);
        $producto = Producto::findOrFail($datos['producto_id']);
        $total = round((int) $datos['cantidad'] * (float) $producto->precio_venta, 2);
        $saldo = round($total - (float) $datos['sena'], 2);

        DB::transaction(function () use ($datos, $sucursalId, $usuario, $clienteId, $producto, $saldo) {
            $apartado = Apartado::create([
                'cliente_id' => $clienteId,
                'sucursal_id' => $sucursalId,
                'user_id' => $usuario->id,
                'producto_id' => $producto->id,
                'cantidad' => $datos['cantidad'],
                'precio_unitario' => $producto->precio_venta,
                'sena' => $datos['sena'],
                'saldo' => $saldo,
                'estado' => $saldo <= 0 ? Apartado::ESTADO_COMPLETADO : Apartado::ESTADO_ACTIVO,
                'fecha' => $datos['fecha'],
            ]);

            // La reserva descuenta el stock de la sucursal hasta que se complete o se cancele.
            $this->stock->mover($producto->id, $sucursalId, -(int) $datos['cantidad'], 'apartado', "Apartado #{$apartado->id}", $usuario->id);

            if ($saldo <= 0) {
                $this->generarVenta($apartado, null, $usuario->id);
            }
        });

        return redirect()->route('apartados.index')->with('status', 'Apartado registrado y producto reservado.');
    }

    public function show(Request $request, Apartado $apartado): View
    {
        $this->autorizarSucursal($request, $apartado);

        return view('apartados.show', ['apartado' => $apartado->load(['cliente', 'producto', 'sucursal', 'usuario', 'venta'])]);
    }

    public function edit(Request $request, Apartado $apartado): View|RedirectResponse
    {
        $this->autorizarSucursal($request, $apartado);

        if ($apartado->estado !== Apartado::ESTADO_ACTIVO) {
            return redirect()->route('apartados.index')->withErrors(['apartado' => 'Solo los apartados activos admiten más pagos.']);
        }

        return view('apartados.form', [
            'apartado' => $apartado->load(['cliente', 'producto', 'sucursal']),
            'accion' => route('apartados.update', $apartado),
            'metodo' => 'PUT',
            'medios' => Venta::MEDIOS_PAGO,
        ]);
    }

    public function update(RegistrarAbonoRequest $request, Apartado $apartado): RedirectResponse
    {
        $this->autorizarSucursal($request, $apartado);

        if ($apartado->estado !== Apartado::ESTADO_ACTIVO) {
            return redirect()->route('apartados.index')->withErrors(['apartado' => 'Solo los apartados activos admiten más pagos.']);
        }

        $datos = $request->validated();
        $saldo = round((float) $apartado->saldo - (float) $datos['abono'], 2);

        DB::transaction(function () use ($apartado, $datos, $saldo, $request) {
            $apartado->update([
                'saldo' => $saldo,
                'estado' => $saldo <= 0 ? Apartado::ESTADO_COMPLETADO : Apartado::ESTADO_ACTIVO,
            ]);

            if ($saldo <= 0) {
                $this->generarVenta($apartado->fresh(), $datos['medio_pago'] ?? null, $request->user()->id);
            }
        });

        return redirect()->route('apartados.index')->with('status', $saldo <= 0
            ? 'Saldo completado. Se generó la venta del apartado.'
            : 'Pago registrado. Saldo pendiente actualizado.');
    }

    public function cancelar(CancelarApartadoRequest $request, Apartado $apartado): RedirectResponse
    {
        $this->autorizarSucursal($request, $apartado);

        if ($apartado->estado !== Apartado::ESTADO_ACTIVO) {
            return redirect()->route('apartados.index')->withErrors(['apartado' => 'Solo se pueden cancelar apartados activos.']);
        }

        $datos = $request->validated();

        DB::transaction(function () use ($apartado, $datos, $request) {
            $this->stock->mover($apartado->producto_id, $apartado->sucursal_id, (int) $apartado->cantidad, TipoMovimiento::LiberacionApartado->value, "Apartado #{$apartado->id} cancelado", $request->user()->id);

            $apartado->update([
                'estado' => Apartado::ESTADO_CANCELADO,
                'motivo_cancelacion' => $datos['motivo_cancelacion'] ?? null,
                'sena_retenida' => $datos['sena_retenida'] ?? 0,
            ]);
        });

        return redirect()->route('apartados.index')->with('status', 'Apartado cancelado y stock liberado.');
    }

    public function export(FiltrarApartadosRequest $request): StreamedResponse
    {
        $usuario = $request->user();
        $filtros = $request->validated();

        $filas = $this->listado($usuario, $filtros)->lazy()->map(fn (Apartado $apartado): array => [
            $apartado->id,
            $apartado->fecha?->format('d/m/Y'),
            $apartado->cliente?->nombre,
            $apartado->producto?->nombre,
            $apartado->cantidad,
            number_format((float) $apartado->sena, 2, '.', ''),
            number_format((float) $apartado->saldo, 2, '.', ''),
            ucfirst($apartado->estado),
            $apartado->sucursal?->nombre,
            $apartado->usuario?->username ?? '—',
        ]);

        return Csv::descargar(
            'apartados-'.now()->format('Ymd-His').'.csv',
            ['N°', 'Fecha', 'Cliente', 'Producto', 'Cantidad', 'Seña', 'Saldo', 'Estado', 'Sucursal', 'Usuario'],
            $filas,
        );
    }

    /**
     * Genera la venta del apartado sin volver a mover stock (ya estaba reservado).
     * El monto cobrado (seña + abonos) se refleja en la nota.
     */
    private function generarVenta(Apartado $apartado, ?string $medioPago, int $usuarioId): void
    {
        if ($apartado->venta_id) {
            return;
        }

        $subtotal = round((int) $apartado->cantidad * (float) $apartado->precio_unitario, 2);

        $venta = Venta::create([
            'sucursal_id' => $apartado->sucursal_id,
            'cliente_id' => $apartado->cliente_id,
            'user_id' => $usuarioId,
            'tipo' => Venta::TIPO_VENTA,
            'fecha' => now(),
            'medio_pago' => $medioPago ?: 'Efectivo',
            'total' => $subtotal,
            'estado' => Venta::ESTADO_ACTIVA,
        ]);

        VentaItem::create([
            'venta_id' => $venta->id,
            'producto_id' => $apartado->producto_id,
            'descripcion' => $apartado->producto?->nombre ?? 'Producto apartado',
            'cantidad' => $apartado->cantidad,
            'precio_unitario' => $apartado->precio_unitario,
            'subtotal' => $subtotal,
        ]);

        $apartado->update(['venta_id' => $venta->id]);
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function listado($usuario, array $filtros): Builder
    {
        $buscar = (string) ($filtros['buscar'] ?? '');

        return Apartado::query()
            ->with(['cliente', 'producto', 'sucursal', 'usuario'])
            ->when(! $usuario->esGerente(), fn (Builder $q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->when($filtros['estado'] ?? null, fn (Builder $q) => $q->where('estado', $filtros['estado']))
            ->when($filtros['desde'] ?? null, fn (Builder $q) => $q->whereDate('fecha', '>=', $filtros['desde']))
            ->when($filtros['hasta'] ?? null, fn (Builder $q) => $q->whereDate('fecha', '<=', $filtros['hasta']))
            ->when($buscar !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereHas('cliente', fn (Builder $c) => $c->where('nombre', 'like', "%{$buscar}%"))
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

    private function autorizarSucursal(Request $request, Apartado $apartado): void
    {
        $usuario = $request->user();

        abort_unless($usuario->esGerente() || $usuario->sucursal_id === $apartado->sucursal_id, 403, 'El apartado pertenece a otra sucursal.');
    }
}
