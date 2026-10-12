<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Servicio;
use App\Models\Sucursal;
use App\Models\Venta;
use App\Models\VentaItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ServicioController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();

        $items = Servicio::with(['cliente', 'sucursal', 'tecnico', 'venta'])
            ->when(! $usuario->esGerente(), fn ($q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->latest('id')
            ->paginate(15);

        return view('servicios.index', ['items' => $items]);
    }

    public function create(Request $request): View
    {
        return view('servicios.form', [
            'servicio' => new Servicio(['estado' => 'recibido']),
            'accion' => route('servicios.store'),
            'metodo' => 'POST',
            'costo' => 0,
            'clientes' => Cliente::orderBy('nombre')->get(),
            'sucursales' => $this->sucursales($request),
            'puedeElegirSucursal' => $request->user()->esGerente(),
        ]);
    }

    /**
     * Registra el servicio como venta de servicio (RF16). No mueve stock.
     */
    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'modo_cliente' => ['required', Rule::in(['existente', 'nuevo'])],
            'cliente_id' => ['nullable', 'required_if:modo_cliente,existente', Rule::exists('clientes', 'id')],
            'cliente_nombre' => ['nullable', 'required_if:modo_cliente,nuevo', 'string', 'max:150'],
            'cliente_telefono' => ['nullable', 'string', 'max:30'],
            'cliente_carnet' => ['nullable', 'string', 'max:30', Rule::unique('clientes', 'carnet')],
            'sucursal_id' => [$usuario->esGerente() ? 'required' : 'nullable', Rule::exists('sucursales', 'id')],
            'equipo' => ['required', 'string', 'max:150'],
            'diagnostico' => ['nullable', 'string', 'max:1000'],
            'costo' => ['required', 'numeric', 'min:0'],
            'estado' => ['required', Rule::in(array_keys(Servicio::ESTADOS))],
        ], [], [
            'cliente_id' => 'cliente',
            'cliente_nombre' => 'nombre del cliente',
            'cliente_telefono' => 'teléfono',
            'cliente_carnet' => 'carnet',
            'sucursal_id' => 'sucursal',
        ]);

        $sucursalId = $usuario->esGerente() ? (int) $datos['sucursal_id'] : (int) $usuario->sucursal_id;

        DB::transaction(function () use ($datos, $sucursalId, $usuario) {
            // El técnico puede registrar el cliente en el mismo paso (alta rápida).
            $clienteId = $datos['cliente_id'] ?? null;

            if ($datos['modo_cliente'] === 'nuevo' || ! $clienteId) {
                $clienteId = Cliente::create([
                    'nombre' => $datos['cliente_nombre'],
                    'telefono' => $datos['cliente_telefono'] ?? null,
                    'carnet' => $datos['cliente_carnet'] ?? null,
                ])->id;
            }

            $venta = Venta::create([
                'sucursal_id' => $sucursalId,
                'cliente_id' => $clienteId,
                'user_id' => $usuario->id,
                'tipo' => Venta::TIPO_SERVICIO,
                'fecha' => now(),
                'medio_pago' => null,
                'total' => $datos['costo'],
                'estado' => Venta::ESTADO_ACTIVA,
            ]);

            VentaItem::create([
                'venta_id' => $venta->id,
                'producto_id' => null,
                'descripcion' => 'Servicio técnico: '.$datos['equipo'],
                'cantidad' => 1,
                'precio_unitario' => $datos['costo'],
                'subtotal' => $datos['costo'],
            ]);

            Servicio::create([
                'venta_id' => $venta->id,
                'cliente_id' => $clienteId,
                'sucursal_id' => $sucursalId,
                'tecnico_id' => $usuario->id,
                'equipo' => $datos['equipo'],
                'diagnostico' => $datos['diagnostico'] ?? null,
                'estado' => $datos['estado'],
            ]);
        });

        return redirect()->route('servicios.index')->with('status', 'Servicio técnico registrado.');
    }

    public function edit(Request $request, Servicio $servicio): View
    {
        $this->autorizarSucursal($request, $servicio);

        return view('servicios.form', [
            'servicio' => $servicio->load(['cliente', 'venta']),
            'accion' => route('servicios.update', $servicio),
            'metodo' => 'PUT',
            'costo' => (float) $servicio->venta->total,
            'clientes' => Cliente::orderBy('nombre')->get(),
            'sucursales' => $this->sucursales($request),
            'puedeElegirSucursal' => $request->user()->esGerente(),
        ]);
    }

    public function update(Request $request, Servicio $servicio): RedirectResponse
    {
        $this->autorizarSucursal($request, $servicio);

        $datos = $request->validate([
            'equipo' => ['required', 'string', 'max:150'],
            'diagnostico' => ['nullable', 'string', 'max:1000'],
            'costo' => ['required', 'numeric', 'min:0'],
            'estado' => ['required', Rule::in(array_keys(Servicio::ESTADOS))],
        ]);

        DB::transaction(function () use ($datos, $servicio) {
            $servicio->update([
                'equipo' => $datos['equipo'],
                'diagnostico' => $datos['diagnostico'] ?? null,
                'estado' => $datos['estado'],
            ]);

            $venta = $servicio->venta;
            $venta->update(['total' => $datos['costo']]);
            $venta->items()->update([
                'descripcion' => 'Servicio técnico: '.$datos['equipo'],
                'precio_unitario' => $datos['costo'],
                'subtotal' => $datos['costo'],
            ]);
        });

        return redirect()->route('servicios.index')->with('status', 'Servicio actualizado.');
    }

    public function destroy(Request $request, Servicio $servicio): RedirectResponse
    {
        $this->autorizarSucursal($request, $servicio);

        // Al eliminar la venta de servicio se elimina también el registro del servicio.
        $servicio->venta()->delete();

        return redirect()->route('servicios.index')->with('status', 'Servicio eliminado.');
    }

    private function sucursales(Request $request)
    {
        $usuario = $request->user();

        return $usuario->esGerente()
            ? Sucursal::where('activa', true)->orderBy('nombre')->get()
            : Sucursal::whereKey($usuario->sucursal_id)->get();
    }

    private function autorizarSucursal(Request $request, Servicio $servicio): void
    {
        $usuario = $request->user();

        abort_unless($usuario->esGerente() || $usuario->sucursal_id === $servicio->sucursal_id, 403, 'El servicio pertenece a otra sucursal.');
    }
}
