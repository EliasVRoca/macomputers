<?php

namespace App\Http\Controllers;

use App\Http\Requests\Productos\GuardarProductoAtributoRequest;
use App\Http\Requests\Productos\GuardarProductoImagenRequest;
use App\Models\Producto;
use App\Models\ProductoAtributo;
use App\Models\ProductoImagen;
use App\Services\Productos\ProductoImagenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Imágenes y ficha técnica del producto (soporte del buscador inteligente).
 */
class ProductoMediaController extends Controller
{
    public function __construct(private readonly ProductoImagenService $imagenes) {}

    public function agregarImagen(GuardarProductoImagenRequest $request, Producto $producto): RedirectResponse
    {
        $datos = $request->validated();
        $ruta = $request->hasFile('archivo')
            ? $request->file('archivo')->store('productos', 'public')
            : null;

        $this->imagenes->registrar($producto, $ruta, $datos);

        return back()->with('status', 'Imagen agregada al producto.');
    }

    public function quitarImagen(Producto $producto, ProductoImagen $imagen): RedirectResponse
    {
        abort_unless($imagen->producto_id === $producto->id, 404);

        $eraPrincipal = $imagen->es_principal;

        if ($imagen->ruta) {
            Storage::disk('public')->delete($imagen->ruta);
        }

        $imagen->delete();

        if ($eraPrincipal) {
            $producto->imagenes()->orderBy('orden')->orderBy('id')->first()?->update(['es_principal' => true]);
        }

        return back()->with('status', 'Imagen quitada.');
    }

    public function principalImagen(Producto $producto, ProductoImagen $imagen): RedirectResponse
    {
        abort_unless($imagen->producto_id === $producto->id, 404);

        DB::transaction(function () use ($producto, $imagen): void {
            $producto->imagenes()->update(['es_principal' => false]);
            $imagen->update(['es_principal' => true]);
        });

        return back()->with('status', 'Imagen principal actualizada.');
    }

    public function agregarAtributo(GuardarProductoAtributoRequest $request, Producto $producto): RedirectResponse
    {
        $datos = $request->validated();

        $producto->atributos()->updateOrCreate(
            ['atributo' => $datos['atributo']],
            ['valor' => $datos['valor']],
        );

        return back()->with('status', 'Atributo guardado.');
    }

    public function quitarAtributo(Producto $producto, ProductoAtributo $atributo): RedirectResponse
    {
        abort_unless($atributo->producto_id === $producto->id, 404);

        $atributo->delete();

        return back()->with('status', 'Atributo quitado.');
    }
}
