<?php

namespace App\Http\Controllers;

use App\Models\Apartado;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Services\StockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApartadoController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index(Request $request): View
    {
        $usuario = $request->user();

        $items = Apartado::with(['cliente', 'producto', 'sucursal'])
            ->when(! $usuario->esGerente(), fn ($q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->latest('id')
            ->paginate(15);

        return view('apartados.index', ['items' => $items]);
    }

    public function create(Request $request): View
    {
        return view('apartados.form', [
            'apartado' => new Apartado(['cantidad' => 1, 'sena' => 0, 'fecha' => now()->toDateString()]),
            'accion' => route('apartados.store'),
            'metodo' => 'POST',
            'clientes' => Cliente::orderBy('nombre')->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'sucursales' => $this->sucursales($request),
            'puedeElegirSucursal' => $request->user()->esGerente(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'cliente_id' => ['required', Rule::exists('clientes', 'id')],
            'sucursal_id' => [$usuario->esGerente() ? 'required' : 'nullable', Rule::exists('sucursales', 'id')],
            'producto_id' => ['required', Rule::exists('productos', 'id')],
            'cantidad' => ['required', 'integer', 'min:1'],
            'sena' => ['required', 'numeric', 'min:0'],
            'fecha' => ['required', 'date'],
        ], [], [
            'cliente_id' => 'cliente',
            'sucursal_id' => 'sucursal',
            'producto_id' => 'producto',
            'sena' => 'seña',
        ]);

        $sucursalId = $usuario->esGerente() ? (int) $datos['sucursal_id'] : (int) $usuario->sucursal_id;
        $producto = Producto::findOrFail($datos['producto_id']);
        $total = round($datos['cantidad'] * (float) $producto->precio_venta, 2);

        if ((float) $datos['sena'] > $total) {
            return back()->withErrors(['sena' => 'La seña no puede ser mayor al valor total del apartado.'])->withInput();
        }

        DB::transaction(function () use ($datos, $sucursalId, $usuario, $producto, $total) {
            $apartado = Apartado::create([
                'cliente_id' => $datos['cliente_id'],
                'sucursal_id' => $sucursalId,
                'user_id' => $usuario->id,
                'producto_id' => $producto->id,
                'cantidad' => $datos['cantidad'],
                'precio_unitario' => $producto->precio_venta,
                'sena' => $datos['sena'],
                'saldo' => round($total - (float) $datos['sena'], 2),
                'estado' => Apartado::ESTADO_ACTIVO,
                'fecha' => $datos['fecha'],
            ]);

            // La reserva descuenta el stock de la sucursal hasta que se cancele o se elimine el apartado.
            $this->stock->mover($producto->id, $sucursalId, -(int) $datos['cantidad'], 'apartado', "Apartado #{$apartado->id}", $usuario->id);
        });

        return redirect()->route('apartados.index')->with('status', 'Apartado registrado y producto reservado.');
    }

    public function edit(Request $request, Apartado $apartado): View|RedirectResponse
    {
        $this->autorizarSucursal($request, $apartado);

        if ($apartado->estado === Apartado::ESTADO_COMPLETADO) {
            return redirect()->route('apartados.index')->withErrors(['apartado' => 'El apartado ya está completado y no admite más pagos.']);
        }

        return view('apartados.form', [
            'apartado' => $apartado->load(['cliente', 'producto', 'sucursal']),
            'accion' => route('apartados.update', $apartado),
            'metodo' => 'PUT',
        ]);
    }

    public function update(Request $request, Apartado $apartado): RedirectResponse
    {
        $this->autorizarSucursal($request, $apartado);

        if ($apartado->estado === Apartado::ESTADO_COMPLETADO) {
            return redirect()->route('apartados.index')->withErrors(['apartado' => 'El apartado ya está completado.']);
        }

        $datos = $request->validate([
            'abono' => ['required', 'numeric', 'gt:0', 'lte:'.$apartado->saldo],
        ], [
            'abono.lte' => 'El pago no puede ser mayor al saldo pendiente ('.number_format($apartado->saldo, 2, ',', '.').').',
        ], ['abono' => 'pago del saldo']);

        $saldo = round((float) $apartado->saldo - (float) $datos['abono'], 2);

        $apartado->update([
            'saldo' => $saldo,
            'estado' => $saldo <= 0 ? Apartado::ESTADO_COMPLETADO : Apartado::ESTADO_ACTIVO,
        ]);

        return redirect()->route('apartados.index')->with('status', $saldo <= 0
            ? 'Saldo completado. El apartado queda finalizado.'
            : 'Pago registrado. Saldo pendiente actualizado.');
    }

    public function destroy(Request $request, Apartado $apartado): RedirectResponse
    {
        $this->autorizarSucursal($request, $apartado);

        DB::transaction(function () use ($apartado, $request) {
            if ($apartado->estado === Apartado::ESTADO_ACTIVO) {
                $this->stock->mover($apartado->producto_id, $apartado->sucursal_id, (int) $apartado->cantidad, 'liberacion_apartado', "Apartado #{$apartado->id} eliminado", $request->user()->id);
            }

            $apartado->delete();
        });

        return redirect()->route('apartados.index')->with('status', 'Apartado eliminado y stock liberado.');
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
