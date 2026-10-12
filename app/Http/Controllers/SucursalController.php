<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SucursalController extends Controller
{
    public function index(): View
    {
        return view('sucursales.index', ['items' => Sucursal::orderBy('nombre')->paginate(15)]);
    }

    public function create(): View
    {
        return view('sucursales.form', ['sucursal' => new Sucursal(['activa' => true]), 'accion' => route('sucursales.store'), 'metodo' => 'POST']);
    }

    public function store(Request $request): RedirectResponse
    {
        Sucursal::create($this->validar($request));

        return redirect()->route('sucursales.index')->with('status', 'Sucursal registrada.');
    }

    public function edit(Sucursal $sucursal): View
    {
        return view('sucursales.form', ['sucursal' => $sucursal, 'accion' => route('sucursales.update', $sucursal), 'metodo' => 'PUT']);
    }

    public function update(Request $request, Sucursal $sucursal): RedirectResponse
    {
        $sucursal->update($this->validar($request, $sucursal));

        return redirect()->route('sucursales.index')->with('status', 'Sucursal actualizada.');
    }

    private function validar(Request $request, ?Sucursal $sucursal = null): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('sucursales', 'nombre')->ignore($sucursal?->id)],
            'direccion' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
        ]);

        $datos['activa'] = $request->boolean('activa');

        return $datos;
    }
}
