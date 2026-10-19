<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sucursales\CambiarEstadoSucursalRequest;
use App\Http\Requests\Sucursales\GuardarSucursalRequest;
use App\Models\Sucursal;
use App\Services\SucursalService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SucursalController extends Controller
{
    public function __construct(private SucursalService $sucursales) {}

    public function index(): View
    {
        return view('sucursales.index', ['items' => Sucursal::orderBy('nombre')->paginate(15)]);
    }

    public function create(): View
    {
        return view('sucursales.form', ['sucursal' => new Sucursal(['activa' => true]), 'accion' => route('sucursales.store'), 'metodo' => 'POST']);
    }

    public function store(GuardarSucursalRequest $request): RedirectResponse
    {
        Sucursal::create($request->validated());

        return redirect()->route('sucursales.index')->with('status', 'Sucursal registrada.');
    }

    public function edit(Sucursal $sucursal): View
    {
        return view('sucursales.form', ['sucursal' => $sucursal, 'accion' => route('sucursales.update', $sucursal), 'metodo' => 'PUT']);
    }

    public function update(GuardarSucursalRequest $request, Sucursal $sucursal): RedirectResponse
    {
        $sucursal->update($request->validated());

        return redirect()->route('sucursales.index')->with('status', 'Sucursal actualizada.');
    }

    /**
     * Baja lógica: nunca se borra la sucursal.
     */
    public function destroy(Sucursal $sucursal): RedirectResponse
    {
        return $this->darDeBaja($sucursal, 'eliminar');
    }

    public function estado(CambiarEstadoSucursalRequest $request, Sucursal $sucursal): RedirectResponse
    {
        if ($request->boolean('activa')) {
            $sucursal->update(['activa' => true]);

            return redirect()->route('sucursales.index')->with('status', 'Sucursal activada.');
        }

        return $this->darDeBaja($sucursal, 'estado');
    }

    private function darDeBaja(Sucursal $sucursal, string $errorKey): RedirectResponse
    {
        if (! $sucursal->activa) {
            return redirect()->route('sucursales.index')->with('status', 'La sucursal ya está dada de baja.');
        }

        $motivo = $this->sucursales->motivoNoDesactivable($sucursal);

        if ($motivo !== null) {
            return back()->withErrors([$errorKey => 'No se puede dar de baja: '.$motivo]);
        }

        $sucursal->update(['activa' => false]);

        return redirect()->route('sucursales.index')
            ->with('status', 'Sucursal dada de baja. Puede reactivarla cuando lo necesite.');
    }
}
