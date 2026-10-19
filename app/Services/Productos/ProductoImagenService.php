<?php

namespace App\Services\Productos;

use App\Models\Producto;
use App\Models\ProductoImagen;
use Illuminate\Support\Facades\DB;

final class ProductoImagenService
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public function registrar(Producto $producto, ?string $ruta, array $datos): ProductoImagen
    {
        $principal = (bool) ($datos['es_principal'] ?? false) || ! $producto->imagenes()->exists();

        return DB::transaction(function () use ($producto, $ruta, $datos, $principal): ProductoImagen {
            if ($principal) {
                $producto->imagenes()->update(['es_principal' => false]);
            }

            return $producto->imagenes()->create([
                'ruta' => $ruta,
                'url' => $datos['url'] ?? null,
                'orden' => (int) ($datos['orden'] ?? ($producto->imagenes()->max('orden') + 1)),
                'es_principal' => $principal,
                'proveedor' => $datos['proveedor'] ?? null,
                'origen_url' => $datos['origen_url'] ?? null,
                'titulo' => $datos['titulo'] ?? null,
                'autor' => $datos['autor'] ?? null,
                'licencia' => $datos['licencia'] ?? null,
                'licencia_url' => $datos['licencia_url'] ?? null,
            ]);
        });
    }
}
