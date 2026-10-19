<?php

namespace App\Services\Imagenes;

use App\Exceptions\BusquedaImagenException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class BraveImagenes implements ProveedorImagenes
{
    public function __construct(
        private readonly ?string $key,
        private readonly string $url,
        private readonly int $timeout,
        private readonly string $userAgent,
    ) {}

    public function clave(): string
    {
        return 'brave';
    }

    public function etiqueta(): string
    {
        return 'Brave Search';
    }

    public function urlAtribucion(): string
    {
        return 'https://brave.com/search/api/';
    }

    public function buscar(string $consulta, int $limite): array
    {
        if (blank($this->key)) {
            throw BusquedaImagenException::noConfigurado('Configurá BRAVE_SEARCH_KEY para buscar imágenes con Brave.');
        }

        $respuesta = Http::timeout($this->timeout)
            ->connectTimeout(10)
            ->withHeaders([
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip',
                'User-Agent' => $this->userAgent,
                'X-Subscription-Token' => $this->key,
            ])
            ->get($this->url, [
                'q' => $consulta,
                'count' => max(1, min($limite, 50)),
                'safesearch' => 'strict',
                'search_lang' => 'es',
                'spellcheck' => 1,
            ]);

        if ($respuesta->failed()) {
            throw BusquedaImagenException::proveedor('No se pudo consultar Brave Search (HTTP '.$respuesta->status().').');
        }

        $items = $respuesta->json('results', []);

        if (! is_array($items)) {
            return [];
        }

        $resultados = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $url = $item['properties']['url'] ?? null;
            $miniatura = $item['thumbnail']['src'] ?? null;

            if (! ResultadoImagen::esUrlHttp($url) || ! ResultadoImagen::esUrlHttp($miniatura)) {
                continue;
            }

            $origen = $item['url'] ?? null;
            $origen = ResultadoImagen::esUrlHttp($origen) ? $origen : null;

            $resultados[] = new ResultadoImagen(
                titulo: Str::limit((string) ($item['title'] ?? 'Imagen'), 200, ''),
                miniatura: $miniatura,
                url: $url,
                origenUrl: $origen,
                fuente: $item['source'] ?? ResultadoImagen::dominio($origen),
                ancho: isset($item['properties']['width']) ? (int) $item['properties']['width'] : null,
                alto: isset($item['properties']['height']) ? (int) $item['properties']['height'] : null,
            );
        }

        return $resultados;
    }
}
