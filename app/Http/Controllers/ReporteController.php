<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReporteRequest;
use App\Models\Sucursal;
use App\Services\ReporteService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

class ReporteController extends Controller
{
    public function __construct(private readonly ReporteService $reportes) {}

    public function index(ReporteRequest $request): View
    {
        $usuario = $request->user();
        $filtros = $this->filtros($request);

        return view('reportes.index', [
            'datos' => $this->reportes->generar(
                $usuario,
                $filtros['desde'],
                $filtros['hasta'],
                $filtros['sucursal_id'],
                $filtros['agrupacion'],
            ),
            'filtros' => $filtros,
            'sucursales' => $usuario->esGerente()
                ? Sucursal::where('activa', true)->orderBy('nombre')->get()
                : collect(),
            'puedeElegirSucursal' => $usuario->esGerente(),
        ]);
    }

    /**
     * Filtros normalizados: rango por defecto de 30 días, sucursal forzada
     * para usuarios que no son gerentes.
     *
     * @return array{desde: CarbonImmutable, hasta: CarbonImmutable, sucursal_id: ?int, agrupacion: string}
     */
    private function filtros(ReporteRequest $request): array
    {
        $usuario = $request->user();
        $validados = $request->validated();

        $desde = isset($validados['desde'])
            ? CarbonImmutable::parse($validados['desde'])->startOfDay()
            : CarbonImmutable::today()->subDays(29)->startOfDay();

        $hasta = isset($validados['hasta'])
            ? CarbonImmutable::parse($validados['hasta'])->endOfDay()
            : CarbonImmutable::today()->endOfDay();

        // Evita gráficos ilegibles: rango máximo de dos años.
        if ($hasta->diffInDays($desde) > 730) {
            $desde = $hasta->subDays(730)->startOfDay();
        }

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'sucursal_id' => $usuario->esGerente()
                ? ($validados['sucursal_id'] ?? null)
                : ($usuario->sucursal_id ? (int) $usuario->sucursal_id : null),
            'agrupacion' => (string) ($validados['agrupacion'] ?? 'dia'),
        ];
    }
}
