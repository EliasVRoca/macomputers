<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardRequest;
use App\Models\Sucursal;
use App\Services\DashboardService;
use App\Support\Menu;
use Illuminate\Contracts\View\View;

class InicioController extends Controller
{
    public function __invoke(DashboardRequest $request, DashboardService $dashboard): View
    {
        $usuario = $request->user();

        if (Menu::para($usuario) === []) {
            return view('inicio');
        }

        $datos = $request->validated();
        $puedeElegirSucursal = $usuario->esGerente();
        $sucursalId = $puedeElegirSucursal
            ? ($datos['sucursal_id'] ?? null)
            : (int) $usuario->sucursal_id;

        return view('dashboard.index', [
            'metricas' => $dashboard->metricas($usuario, $sucursalId),
            'sucursalActual' => $sucursalId ? Sucursal::find($sucursalId) : null,
            'sucursales' => $puedeElegirSucursal
                ? Sucursal::where('activa', true)->orderBy('nombre')->get()
                : collect(),
            'puedeElegirSucursal' => $puedeElegirSucursal,
            'hoy' => today(),
        ]);
    }
}
