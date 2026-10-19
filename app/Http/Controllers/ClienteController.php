<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clientes\CambiarEstadoClienteRequest;
use App\Http\Requests\Clientes\FiltrarClientesRequest;
use App\Http\Requests\Clientes\GuardarClienteRequest;
use App\Models\Cliente;
use App\Models\Venta;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClienteController extends Controller
{
    public function index(FiltrarClientesRequest $request): View
    {
        $filtros = $request->validated();

        return view('clientes.index', [
            'items' => $this->listado($filtros)->paginate(15)->withQueryString(),
            'filtros' => $filtros,
        ]);
    }

    public function create(): View
    {
        return view('clientes.form', ['cliente' => new Cliente(['activo' => true]), 'accion' => route('clientes.store'), 'metodo' => 'POST']);
    }

    public function store(GuardarClienteRequest $request): RedirectResponse
    {
        Cliente::create($request->validated());

        return redirect()->route('clientes.index')->with('status', 'Cliente registrado.');
    }

    public function show(Request $request, Cliente $cliente): View
    {
        return view('clientes.show', [
            'cliente' => $cliente,
            'ventas' => $cliente->ventas()->where('tipo', Venta::TIPO_VENTA)->with('sucursal')->latest('fecha')->limit(10)->get(),
            'apartados' => $cliente->apartados()->with('producto')->latest('id')->limit(10)->get(),
            'encargos' => $cliente->encargos()->latest('id')->limit(10)->get(),
            'servicios' => $request->user()->tienePermiso('servicios')
                ? $cliente->servicios()->latest('id')->limit(10)->get()
                : collect(),
        ]);
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.form', ['cliente' => $cliente, 'accion' => route('clientes.update', $cliente), 'metodo' => 'PUT']);
    }

    public function update(GuardarClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($request->validated());

        return redirect()->route('clientes.index')->with('status', 'Cliente actualizado.');
    }

    /**
     * Baja lógica: desactiva si el cliente tiene historial; si no, lo elimina.
     */
    public function destroy(Cliente $cliente): RedirectResponse
    {
        if ($this->tieneHistorial($cliente)) {
            $cliente->update(['activo' => false]);

            return redirect()->route('clientes.index')->with('status', 'El cliente tiene registros asociados: se desactivó en vez de eliminarlo.');
        }

        $cliente->delete();

        return redirect()->route('clientes.index')->with('status', 'Cliente eliminado.');
    }

    public function estado(CambiarEstadoClienteRequest $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update(['activo' => $request->boolean('activo')]);

        return redirect()->route('clientes.index')->with('status', $cliente->activo ? 'Cliente activado.' : 'Cliente desactivado.');
    }

    public function export(FiltrarClientesRequest $request): StreamedResponse
    {
        $filtros = $request->validated();

        $filas = $this->listado($filtros)->lazy()->map(fn (Cliente $cliente): array => [
            $cliente->nombre,
            $cliente->telefono,
            $cliente->direccion,
            $cliente->carnet,
            $cliente->activo ? 'Activo' : 'Inactivo',
        ]);

        return Csv::descargar(
            'clientes-'.now()->format('Ymd-His').'.csv',
            ['Nombre', 'Teléfono', 'Dirección', 'Carnet', 'Estado'],
            $filas,
        );
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function listado(array $filtros): Builder
    {
        $buscar = (string) ($filtros['buscar'] ?? '');

        return Cliente::query()
            ->when($buscar !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('carnet', 'like', "%{$buscar}%")
                ->orWhere('telefono', 'like', "%{$buscar}%")))
            ->when(array_key_exists('activo', $filtros) && $filtros['activo'] !== null, fn (Builder $q) => $q->where('activo', (bool) $filtros['activo']))
            ->orderBy('nombre');
    }

    private function tieneHistorial(Cliente $cliente): bool
    {
        return $cliente->ventas()->exists()
            || $cliente->apartados()->exists()
            || $cliente->encargos()->exists()
            || $cliente->servicios()->exists();
    }
}
