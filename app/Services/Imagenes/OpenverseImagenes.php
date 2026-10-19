<?php

namespace App\Services\Imagenes;

use App\Exceptions\BusquedaImagenException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class OpenverseImagenes implements ProveedorImagenes
{
    public function __construct(
        private readonly string $url,
        private readonly int $timeout,
        private readonly string $userAgent,
    ) {}

    public function clave(): string
    {
        return 'openverse';
    }

    public function etiqueta(): string
    {
        return 'Openverse';
    }

    public function urlAtribucion(): string
    {
        return 'https://openverse.org/';
    }

    public function buscar(string $consulta, int $limite): array
    {
        $respuesta = Http::timeout($this->timeout)
            ->connectTimeout(10)
            ->withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => $this->userAgent,
            ])
            ->get($this->url, [
                'q' => $consulta,
                'page_size' => max(1, min($limite, 20)),
                'license_type' => 'commercial',
            ]);

        if ($respuesta->failed()) {
            throw BusquedaImagenException::proveedor('No se pudo consultar Openverse (HTTP '.$respuesta->status().').');
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

            $url = $item['url'] ?? null;
            $miniatura = $item['thumbnail'] ?? null;

            if (! ResultadoImagen::esUrlHttp($url) || ! ResultadoImagen::esUrlHttp($miniatura)) {
                continue;
            }

            $origen = $item['foreign_landing_url'] ?? null;
            $origen = ResultadoImagen::esUrlHttp($origen) ? $origen : null;

            $resultados[] = new ResultadoImagen(
                titulo: Str::limit((string) ($item['title'] ?? 'Imagen'), 200, ''),
                miniatura: $miniatura,
                url: $url,
                origenUrl: $origen,
                fuente: ResultadoImagen::dominio($origen),
                ancho: isset($item['width']) ? (int) $item['width'] : null,
                alto: isset($item['height']) ? (int) $item['height'] : null,
                autor: filled($item['creator'] ?? null) ? Str::limit((string) $item['creator'], 150, '') : null,
                licencia: $this->licencia($item['license'] ?? null, $item['license_version'] ?? null),
                licenciaUrl: ResultadoImagen::esUrlHttp($item['license_url'] ?? null) ? $item['license_url'] : null,
            );
        }

        return $resultados;
    }

    private function licencia(?string $codigo, mixed $version): ?string
    {
        if (blank($codigo)) {
            return null;
        }

        $nombre = match ($codigo) {
            'cc0' => 'CC0',
            'pdm' => 'Dominio público',
            'by' => 'CC BY',
            'by-sa' => 'CC BY-SA',
            'by-nd' => 'CC BY-ND',
            'by-nc' => 'CC BY-NC',
            'by-nc-sa' => 'CC BY-NC-SA',
            'by-nc-nd' => 'CC BY-NC-ND',
            default => 'CC '.strtoupper($codigo),
        };

        $version = trim((string) $version);

        return Str::limit($nombre.($version !== '' ? ' '.$version : ''), 100, '');
    }
}
