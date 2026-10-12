<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    public function index(): View
    {
        return view('proveedores.index', ['items' => Proveedor::orderBy('nombre')->paginate(15)]);
    }

    public function create(): View
    {
        return view('proveedores.form', ['proveedor' => new Proveedor(), 'accion' => route('proveedores.store'), 'metodo' => 'POST']);
    }

    public function store(Request $request): RedirectResponse
    {
        Proveedor::create($this->validar($request));

        return redirect()->route('proveedores.index')->with('status', 'Proveedor registrado.');
    }

    public function edit(Proveedor $proveedor): View
    {
        return view('proveedores.form', ['proveedor' => $proveedor, 'accion' => route('proveedores.update', $proveedor), 'metodo' => 'PUT']);
    }

    public function update(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $proveedor->update($this->validar($request));

        return redirect()->route('proveedores.index')->with('status', 'Proveedor actualizado.');
    }

    public function destroy(Proveedor $proveedor): RedirectResponse
    {
        try {
            $proveedor->delete();
        } catch (QueryException) {
            return back()->withErrors(['eliminar' => 'No se puede eliminar: el proveedor tiene compras registradas.']);
        }

        return redirect()->route('proveedores.index')->with('status', 'Proveedor eliminado.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'contacto' => ['nullable', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'pais_origen' => ['nullable', 'string', 'max:100'],
        ]);
    }
}
