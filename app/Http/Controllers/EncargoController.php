<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Encargo;
use App\Models\Sucursal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EncargoController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();

        $items = Encargo::with(['cliente', 'sucursal'])
            ->when(! $usuario->esGerente(), fn ($q) => $q->where('sucursal_id', $usuario->sucursal_id))
            ->latest('id')
            ->paginate(15);

        return view('encargos.index', ['items' => $items]);
    }

    public function create(Request $request): View
    {
        return view('encargos.form', [
            'encargo' => new Encargo(['anticipo' => 0, 'saldo' => 0, 'fecha' => now()->toDateString()]),
            'accion' => route('encargos.store'),
            'metodo' => 'POST',
            'clientes' => Cliente::orderBy('nombre')->get(),
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
            'descripcion' => ['required', 'string', 'max:255'],
            'anticipo' => ['required', 'numeric', 'min:0'],
            'saldo' => ['required', 'numeric', 'min:0'],
            'fecha' => ['required', 'date'],
        ], [], [
            'cliente_id' => 'cliente',
            'sucursal_id' => 'sucursal',
            'descripcion' => 'descripción del producto',
            'anticipo' => 'anticipo',
        ]);

        Encargo::create([
            'cliente_id' => $datos['cliente_id'],
            'sucursal_id' => $usuario->esGerente() ? (int) $datos['sucursal_id'] : (int) $usuario->sucursal_id,
            'user_id' => $usuario->id,
            'descripcion' => $datos['descripcion'],
            'anticipo' => $datos['anticipo'],
            'saldo' => $datos['saldo'],
            'estado' => (float) $datos['saldo'] <= 0 ? Encargo::ESTADO_COMPLETADO : Encargo::ESTADO_PENDIENTE,
            'fecha' => $datos['fecha'],
        ]);

        return redirect()->route('encargos.index')->with('status', 'Encargo registrado.');
    }

    public function edit(Request $request, Encargo $encargo): View|RedirectResponse
    {
        $this->autorizarSucursal($request, $encargo);

        if ($encargo->estado === Encargo::ESTADO_COMPLETADO) {
            return redirect()->route('encargos.index')->withErrors(['encargo' => 'El encargo ya está completado.']);
        }

        return view('encargos.form', [
            'encargo' => $encargo->load('cliente'),
            'accion' => route('encargos.update', $encargo),
            'metodo' => 'PUT',
        ]);
    }

    /**
     * Registra el pago del saldo pendiente al entregar el producto (CU12).
     */
    public function update(Request $request, Encargo $encargo): RedirectResponse
    {
        $this->autorizarSucursal($request, $encargo);

        if ($encargo->estado === Encargo::ESTADO_COMPLETADO) {
            return redirect()->route('encargos.index')->withErrors(['encargo' => 'El encargo ya está completado.']);
        }

        $datos = $request->validate([
            'abono' => ['required', 'numeric', 'gt:0', 'lte:'.$encargo->saldo],
        ], [
            'abono.lte' => 'El pago no puede ser mayor al saldo pendiente ('.number_format($encargo->saldo, 2, ',', '.').').',
        ], ['abono' => 'pago del saldo']);

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
