<?php

namespace App\Http\Controllers;

use App\Enums\CondicionProducto;
use App\Enums\ResultadoBusqueda;
use App\Http\Requests\Buscador\BuscarRequest;
use App\Http\Requests\Buscador\FiltrarBusquedasRequest;
use App\Http\Requests\Buscador\IndexBuscadorRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Services\BuscadorService;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Buscador inteligente de productos (RF20 · CU19).
 */
class BuscadorController extends Controller
{
    public function __construct(private BuscadorService $buscador) {}

    public function index(IndexBuscadorRequest $request): View
    {
        $usuario = $request->user();

        return view('buscador.index', [
            'filtros' => $request->validated(),
            'sucursales' => Sucursal::where('activa', true)->orderBy('nombre')->get(),
            'sucursalUsuarioId' => $usuario->sucursal_id,
            'puedeElegirSucursal' => $usuario->esGerente(),
            'puedeTraspasar' => $usuario->tienePermiso('traspasos'),
            'puedeVerFicha' => $usuario->tienePermiso('productos'),
            'puedeComprar' => $usuario->tienePermiso('compras'),
            'puedeEncargar' => $usuario->tienePermiso('encargos'),
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Producto::where('activo', true)->select('marca')->distinct()->orderBy('marca')->pluck('marca'),
            'condiciones' => CondicionProducto::opciones(),
        ]);
    }

    public function json(BuscarRequest $request): JsonResponse
    {
        $resultado = $this->buscador->buscar($request->validated(), $request->user());
        $puedeVerFicha = $request->user()->tienePermiso('productos');

        $mapear = function (array $producto) use ($puedeVerFicha): array {
            $producto['ficha_url'] = $puedeVerFicha ? route('productos.show', $producto['id']) : null;
            $producto['disponibilidad_url'] = route('disponibilidad.index', ['buscar' => $producto['codigo']]);

            return $producto;
        };

        $resultado['productos'] = array_map($mapear, $resultado['productos']);
        $resultado['similares'] = array_map($mapear, $resultado['similares']);

        return response()->json($resultado);
    }

    public function reporte(FiltrarBusquedasRequest $request): View
    {
        abort_unless($request->user()->esGerente(), 403, 'El reporte de búsquedas es exclusivo del gerente.');

        return view('buscador.reporte', [
            'filtros' => $request->validated(),
            'datos' => $this->buscador->reporte($request->validated()),
            'sucursales' => Sucursal::orderBy('nombre')->get(),
            'resultados' => [
                ResultadoBusqueda::SinStock->value => ResultadoBusqueda::SinStock->etiqueta(),
                ResultadoBusqueda::NoExiste->value => ResultadoBusqueda::NoExiste->etiqueta(),
            ],
        ]);
    }

    public function exportReporte(FiltrarBusquedasRequest $request): StreamedResponse
    {
        abort_unless($request->user()->esGerente(), 403, 'El reporte de búsquedas es exclusivo del gerente.');

        $filas = $this->buscador->exportarReporte($request->validated())
            ->map(fn ($fila): array => [
                $fila->termino,
                (int) $fila->veces,
                (int) $fila->no_existe,
                (int) $fila->sin_stock,
                $fila->ultima ? Carbon::parse($fila->ultima)->format('d/m/Y H:i') : '—',
            ]);

        return Csv::descargar(
            'busquedas-sin-resultado-'.now()->format('Ymd-His').'.csv',
            ['Término', 'Veces', 'No existe', 'Sin stock', 'Última búsqueda'],
            $filas,
        );
    }
}
