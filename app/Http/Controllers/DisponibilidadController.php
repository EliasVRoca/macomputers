<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Sucursal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DisponibilidadController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar', ''));

        $productos = Producto::query()
            ->with('inventarios')
            ->where('activo', true)
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nombre', 'like', "%{$buscar}%")
                ->orWhere('codigo', 'like', "%{$buscar}%")
                ->orWhere('marca', 'like', "%{$buscar}%")))
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('disponibilidad.index', [
            'productos' => $productos,
            'sucursales' => Sucursal::where('activa', true)->orderBy('nombre')->get(),
            'buscar' => $buscar,
        ]);
    }
}
