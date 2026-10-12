<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    public function index(): View
    {
        return view('categorias.index', ['items' => Categoria::withCount('productos')->orderBy('nombre')->paginate(15)]);
    }

    public function create(): View
    {
        return view('categorias.form', ['categoria' => new Categoria(), 'accion' => route('categorias.store'), 'metodo' => 'POST']);
    }

    public function store(Request $request): RedirectResponse
    {
        Categoria::create($this->validar($request));

        return redirect()->route('categorias.index')->with('status', 'Categoría registrada.');
    }

    public function edit(Categoria $categoria): View
    {
        return view('categorias.form', ['categoria' => $categoria, 'accion' => route('categorias.update', $categoria), 'metodo' => 'PUT']);
    }

    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($this->validar($request, $categoria));

        return redirect()->route('categorias.index')->with('status', 'Categoría actualizada.');
    }

    public function destroy(Categoria $categoria): RedirectResponse
    {
        try {
            $categoria->delete();
        } catch (QueryException) {
            return back()->withErrors(['eliminar' => 'No se puede eliminar: la categoría tiene productos asociados.']);
        }

        return redirect()->route('categorias.index')->with('status', 'Categoría eliminada.');
    }

    private function validar(Request $request, ?Categoria $categoria = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('categorias', 'nombre')->ignore($categoria?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
