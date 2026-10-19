<?php

namespace App\Services\Imagenes;

use App\Exceptions\BusquedaImagenException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class BuscadorImagenes
{
    /**
     * @return array{proveedor: array{clave: string, etiqueta: string, url: string}, resultados: array<int, array<string, mixed>>}
     */
    public function buscar(string $consulta): array
    {
        $consulta = trim((string) preg_replace('/\s+/u', ' ', $consulta));

        if (mb_strlen($consulta) < 2) {
            throw new BusquedaImagenException('La búsqueda debe tener al menos 2 caracteres.');
        }

        $proveedor = $this->proveedor();
        $claveCache = 'buscador-imagenes:v2:'.$proveedor->clave().':'.sha1(mb_strtolower($consulta));

        $resultados = Cache::remember(
            $claveCache,
            now()->addMinutes(max(1, (int) config('buscador_imagenes.cache_minutos'))),
            function () use ($consulta, $proveedor): array {
                try {
                    return array_map(
                        fn (ResultadoImagen $resultado): array => $resultado->aArray($proveedor->clave()),
                        $proveedor->buscar($consulta, max(1, (int) config('buscador_imagenes.resultados'))),
                    );
                } catch (BusquedaImagenException $e) {
                    throw $e;
                } catch (ConnectionException $e) {
                    Log::warning('No se pudo conectar con el buscador de imágenes.', [
                        'proveedor' => $proveedor->clave(),
                        'error' => $e->getMessage(),
                    ]);

                    throw BusquedaImagenException::proveedor('No se pudo conectar con el buscador de imágenes. Probá de nuevo en unos minutos.');
                }
            },
        );

        return [
            'proveedor' => [
                'clave' => $proveedor->clave(),
                'etiqueta' => $proveedor->etiqueta(),
                'url' => $proveedor->urlAtribucion(),
            ],
            'resultados' => is_array($resultados) ? $resultados : [],
        ];
    }

    private function proveedor(): ProveedorImagenes
    {
        $brave = new BraveImagenes(
            config('buscador_imagenes.proveedores.brave.key'),
            (string) config('buscador_imagenes.proveedores.brave.url'),
            max(1, (int) config('buscador_imagenes.timeout')),
            $this->userAgent(),
        );

        $openverse = new OpenverseImagenes(
            (string) config('buscador_imagenes.proveedores.openverse.url'),
            max(1, (int) config('buscador_imagenes.timeout')),
            $this->userAgent(),
        );

        $wikimedia = new WikimediaImagenes(
            (string) config('buscador_imagenes.proveedores.wikimedia.url'),
            max(1, (int) config('buscador_imagenes.timeout')),
            $this->userAgent(),
        );

        return match ((string) config('buscador_imagenes.driver')) {
            'brave' => $brave,
            'openverse' => $openverse,
            'wikimedia' => $wikimedia,
            'auto' => filled(config('buscador_imagenes.proveedores.brave.key')) ? $brave : $wikimedia,
            default => throw BusquedaImagenException::noConfigurado('El proveedor de búsqueda de imágenes no está configurado.'),
        };
    }

    private function userAgent(): string
    {
        return trim((string) config('buscador_imagenes.user_agent').' ('.config('app.url').')');
    }
}
