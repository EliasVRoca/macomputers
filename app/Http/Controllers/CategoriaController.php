<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categorias\FiltrarCategoriasRequest;
use App\Http\Requests\Categorias\GuardarCategoriaRequest;
use App\Models\Categoria;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategoriaController extends Controller
{
    public function index(FiltrarCategoriasRequest $request): View
    {
        $buscar = (string) ($request->validated()['buscar'] ?? '');

        return view('categorias.index', [
            'items' => $this->listado($buscar)->paginate(15)->withQueryString(),
            'buscar' => $buscar,
        ]);
    }

    public function create(): View
    {
        return view('categorias.form', ['categoria' => new Categoria, 'accion' => route('categorias.store'), 'metodo' => 'POST']);
    }

    public function store(GuardarCategoriaRequest $request): RedirectResponse
    {
        Categoria::create($request->validated());

        return redirect()->route('categorias.index')->with('status', 'Categoría registrada.');
    }

    public function edit(Categoria $categoria): View
    {
        return view('categorias.form', ['categoria' => $categoria, 'accion' => route('categorias.update', $categoria), 'metodo' => 'PUT']);
    }

    public function update(GuardarCategoriaRequest $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($request->validated());

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

    public function export(FiltrarCategoriasRequest $request): StreamedResponse
    {
        $buscar = (string) ($request->validated()['buscar'] ?? '');

        $filas = $this->listado($buscar)->lazy()->map(fn (Categoria $categoria): array => [
            $categoria->nombre,
            $categoria->descripcion,
            $categoria->productos_count,
        ]);

        return Csv::descargar(
            'categorias-'.now()->format('Ymd-His').'.csv',
            ['Nombre', 'Descripción', 'Productos'],
            $filas,
        );
    }

    private function listado(string $buscar)
    {
        return Categoria::query()
            ->withCount('productos')
            ->when($buscar !== '', fn ($query) => $query->where('nombre', 'like', "%{$buscar}%"))
            ->orderBy('nombre');
    }
}
