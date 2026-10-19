<?php

namespace App\Http\Controllers;

use App\Http\Requests\Proveedores\FiltrarProveedoresRequest;
use App\Http\Requests\Proveedores\GuardarProveedorRequest;
use App\Models\Proveedor;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProveedorController extends Controller
{
    public function index(FiltrarProveedoresRequest $request): View
    {
        $buscar = (string) ($request->validated()['buscar'] ?? '');

        return view('proveedores.index', [
            'items' => $this->listado($buscar)->paginate(15)->withQueryString(),
            'buscar' => $buscar,
        ]);
    }

    public function create(): View
    {
        return view('proveedores.form', ['proveedor' => new Proveedor, 'accion' => route('proveedores.store'), 'metodo' => 'POST']);
    }

    public function store(GuardarProveedorRequest $request): RedirectResponse
    {
        Proveedor::create($request->validated());

        return redirect()->route('proveedores.index')->with('status', 'Proveedor registrado.');
    }

    public function show(Proveedor $proveedor): View
    {
        return view('proveedores.show', [
            'proveedor' => $proveedor,
            'compras' => $proveedor->compras()
                ->with(['sucursal', 'usuario'])
                ->withCount('items')
                ->latest('fecha')
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function edit(Proveedor $proveedor): View
    {
        return view('proveedores.form', ['proveedor' => $proveedor, 'accion' => route('proveedores.update', $proveedor), 'metodo' => 'PUT']);
    }

    public function update(GuardarProveedorRequest $request, Proveedor $proveedor): RedirectResponse
    {
        $proveedor->update($request->validated());

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

    public function export(FiltrarProveedoresRequest $request): StreamedResponse
    {
        $buscar = (string) ($request->validated()['buscar'] ?? '');

        $filas = $this->listado($buscar)->lazy()->map(fn (Proveedor $proveedor): array => [
            $proveedor->nombre,
            $proveedor->contacto,
            $proveedor->direccion,
            $proveedor->pais_origen,
            $proveedor->compras_count,
        ]);

        return Csv::descargar(
            'proveedores-'.now()->format('Ymd-His').'.csv',
            ['Nombre', 'Contacto', 'Dirección', 'País de origen', 'Compras'],
            $filas,
        );
    }

    private function listado(string $buscar): Builder
    {
        return Proveedor::query()
            ->withCount('compras')
            ->when($buscar !== '', fn (Builder $query) => $query->where(fn (Builder $where) => $where
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('contacto', 'like', "%{$buscar}%")
                ->orWhere('pais_origen', 'like', "%{$buscar}%")))
            ->orderBy('nombre');
    }
}
