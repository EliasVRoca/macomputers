<?php

namespace App\Http\Controllers;

use App\Exceptions\BusquedaImagenException;
use App\Http\Requests\Productos\BuscarImagenesInternetRequest;
use App\Http\Requests\Productos\GuardarImagenInternetRequest;
use App\Models\Producto;
use App\Services\Imagenes\BuscadorImagenes;
use App\Services\Imagenes\DescargadorImagen;
use App\Services\Productos\ProductoImagenService;
use Illuminate\Http\JsonResponse;

class ProductoImagenInternetController extends Controller
{
    public function __construct(
        private readonly BuscadorImagenes $buscador,
        private readonly DescargadorImagen $descargador,
        private readonly ProductoImagenService $imagenes,
    ) {}

    public function buscar(BuscarImagenesInternetRequest $request, Producto $producto): JsonResponse
    {
        try {
            $datos = $this->buscador->buscar((string) $request->validated('q'));
        } catch (BusquedaImagenException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        return response()->json($datos);
    }

    public function guardar(GuardarImagenInternetRequest $request, Producto $producto): JsonResponse
    {
        $datos = $request->validated();

        try {
            $archivo = $this->descargador->descargar(
                $datos['url'],
                $datos['miniatura'] ?? null,
                $datos['origen_url'] ?? null,
            );
        } catch (BusquedaImagenException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        $imagen = $this->imagenes->registrar($producto, $archivo['ruta'], [
            'proveedor' => $datos['proveedor'] ?? null,
            'origen_url' => $datos['origen_url'] ?? null,
            'titulo' => $datos['titulo'] ?? null,
            'autor' => $datos['autor'] ?? null,
            'licencia' => $datos['licencia'] ?? null,
            'licencia_url' => $datos['licencia_url'] ?? null,
            'es_principal' => $request->boolean('es_principal'),
        ]);

        return response()->json([
            'ok' => true,
            'imagen' => [
                'id' => $imagen->id,
                'src' => $imagen->src(),
                'es_principal' => $imagen->es_principal,
            ],
        ]);
    }
}
