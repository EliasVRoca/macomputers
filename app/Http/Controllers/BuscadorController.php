<?php

namespace App\Http\Controllers;

use App\Enums\CondicionProducto;
use App\Enums\ResultadoBusqueda;
use App\Http\Requests\Buscador\BuscarRequest;
use App\Http\Requests\Buscador\FiltrarBusquedasRequest;
use App\Http\Requests\Buscador\IaBuscarRequest;
use App\Http\Requests\Buscador\IndexBuscadorRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\BuscadorIaService;
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
    public function __construct(private BuscadorService $buscador, private BuscadorIaService $buscadorIa) {}

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
            'puedeVender' => $usuario->tienePermiso('ventas'),
            'puedeComprar' => $usuario->tienePermiso('compras'),
            'puedeEncargar' => $usuario->tienePermiso('encargos'),
            'iaHabilitada' => config('buscador_ia.habilitado') && filled(config('buscador_ia.clave')),
            'categorias' => Categoria::orderBy('nombre')->get(),
            'marcas' => Producto::where('activo', true)->select('marca')->distinct()->orderBy('marca')->pluck('marca'),
            'condiciones' => CondicionProducto::opciones(),
        ]);
    }

    public function json(BuscarRequest $request): JsonResponse
    {
        $resultado = $this->buscador->buscar($request->validated(), $request->user());

        return response()->json($this->mapearResultado($resultado, $request->user()));
    }

    public function ia(IaBuscarRequest $request): JsonResponse
    {
        $filtros = $request->validated();
        $sesion = $request->header('X-Buscador-Sesion');
        $sesionId = is_string($sesion) && preg_match('/^[A-Za-z0-9-]{8,64}$/', $sesion) === 1 ? $sesion : null;

        $resultado = $this->buscadorIa->preguntar($filtros, $request->user(), $sesionId);

        if (isset($resultado['error'])) {
            $estado = $resultado['error']['codigo'] === 'sin_configuracion' ? 503 : 502;

            return response()->json(['message' => $resultado['error']['mensaje']], $estado);
        }

        return response()->json($this->mapearResultado($resultado, $request->user()));
    }

    /**
     * Agrega las URLs de acción (ficha, venta, disponibilidad) según los
     * permisos del usuario, para búsqueda clásica y Modo IA.
     *
     * @param  array<string, mixed>  $resultado
     * @return array<string, mixed>
     */
    private function mapearResultado(array $resultado, User $usuario): array
    {
        $puedeVerFicha = $usuario->tienePermiso('productos');
        $puedeVender = $usuario->tienePermiso('ventas');

        $mapear = function (array $producto) use ($puedeVerFicha, $puedeVender): array {
            $producto['ficha_url'] = $puedeVerFicha ? route('productos.show', $producto['id']) : null;
            $producto['venta_url'] = $puedeVender ? route('ventas.create', ['producto' => $producto['id']]) : null;
            $producto['disponibilidad_url'] = route('disponibilidad.index', ['buscar' => $producto['codigo']]);

            return $producto;
        };

        foreach (['productos', 'similares', 'alternativas'] as $clave) {
            if (isset($resultado[$clave])) {
                $resultado[$clave] = array_map($mapear, $resultado[$clave]);
            }
        }

        return $resultado;
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
