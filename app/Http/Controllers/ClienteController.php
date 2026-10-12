<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar', ''));

        $items = Cliente::query()
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('carnet', 'like', "%{$buscar}%")))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return view('clientes.index', ['items' => $items, 'buscar' => $buscar]);
    }

    public function create(): View
    {
        return view('clientes.form', ['cliente' => new Cliente(), 'accion' => route('clientes.store'), 'metodo' => 'POST']);
    }

    public function store(Request $request): RedirectResponse
    {
        Cliente::create($this->validar($request));

        return redirect()->route('clientes.index')->with('status', 'Cliente registrado.');
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.form', ['cliente' => $cliente, 'accion' => route('clientes.update', $cliente), 'metodo' => 'PUT']);
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($this->validar($request, $cliente));

        return redirect()->route('clientes.index')->with('status', 'Cliente actualizado.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        try {
            $cliente->delete();
        } catch (QueryException) {
            return back()->withErrors(['eliminar' => 'No se puede eliminar: el cliente tiene registros asociados.']);
        }

        return redirect()->route('clientes.index')->with('status', 'Cliente eliminado.');
    }

    private function validar(Request $request, ?Cliente $cliente = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'carnet' => ['nullable', 'string', 'max:30', Rule::unique('clientes', 'carnet')->ignore($cliente?->id)],
        ]);
    }
}
