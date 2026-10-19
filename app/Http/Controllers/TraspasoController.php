<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\Traspaso;
use App\Services\TraspasoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TraspasoController extends Controller
{
    public function __construct(private TraspasoService $traspasos) {}

    public function index(): View
    {
        $items = Traspaso::with(['origen', 'destino', 'usuario'])
            ->withCount('items')
            ->latest('fecha')
            ->latest('id')
            ->paginate(15);

        return view('traspasos.index', ['items' => $items]);
    }

    public function create(Request $request): View
    {
        return view('traspasos.create', $this->datosFormulario($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'sucursal_origen_id' => [$usuario->esGerente() ? 'required' : 'nullable', Rule::exists('sucursales', 'id')],
            'sucursal_destino_id' => ['required', Rule::exists('sucursales', 'id')],
            'fecha' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', Rule::exists('productos', 'id')],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
        ], [
            'items.required' => 'Agregue al menos un producto al traspaso.',
            'items.min' => 'Agregue al menos un producto al traspaso.',
        ], [
            'sucursal_origen_id' => 'sucursal de origen',
            'sucursal_destino_id' => 'sucursal de destino',
        ]);

        $origenId = $usuario->esGerente() ? (int) $datos['sucursal_origen_id'] : (int) $usuario->sucursal_id;
        $destinoId = (int) $datos['sucursal_destino_id'];

        $this->traspasos->crear($origenId, $destinoId, $datos['items'], $usuario, $datos['fecha']);

        return redirect()->route('traspasos.index')->with('status', 'Traspaso registrado y stock actualizado en ambas sucursales.');
    }

    public function show(Traspaso $traspaso): View
    {
        return view('traspasos.show', [
            'traspaso' => $traspaso->load(['origen', 'destino', 'usuario', 'items.producto']),
        ]);
    }

    private function datosFormulario(Request $request): array
    {
        $usuario = $request->user();

        return [
            'sucursales' => $usuario->esGerente()
                ? Sucursal::where('activa', true)->orderBy('nombre')->get()
                : Sucursal::whereKey($usuario->sucursal_id)->get(),
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'puedeElegirOrigen' => $usuario->esGerente(),
        ];
    }
}
