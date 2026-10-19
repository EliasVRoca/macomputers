<?php

namespace App\Services\Imagenes;

use App\Exceptions\BusquedaImagenException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class WikimediaImagenes implements ProveedorImagenes
{
    public function __construct(
        private readonly string $url,
        private readonly int $timeout,
        private readonly string $userAgent,
    ) {}

    public function clave(): string
    {
        return 'wikimedia';
    }

    public function etiqueta(): string
    {
        return 'Wikimedia Commons';
    }

    public function urlAtribucion(): string
    {
        return 'https://commons.wikimedia.org/';
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
                'action' => 'query',
                'format' => 'json',
                'generator' => 'search',
                'gsrsearch' => $consulta.' filetype:bitmap',
                'gsrnamespace' => 6,
                'gsrlimit' => max(1, min($limite, 20)),
                'prop' => 'imageinfo',
                'iiprop' => 'url|size|mime|extmetadata',
                'iiurlwidth' => 500,
                'iiextmetadatafilter' => 'ImageDescription|Artist|LicenseShortName|LicenseUrl',
            ]);

        if ($respuesta->failed()) {
            throw BusquedaImagenException::proveedor('No se pudo consultar Wikimedia Commons (HTTP '.$respuesta->status().').');
        }

        $paginas = $respuesta->json('query.pages', []);

        if (! is_array($paginas) || $paginas === []) {
            return [];
        }

        $paginas = array_values($paginas);

        usort($paginas, fn (array $a, array $b): int => ((int) ($a['index'] ?? 0)) <=> ((int) ($b['index'] ?? 0)));

        $resultados = [];

        foreach ($paginas as $pagina) {
            $info = $pagina['imageinfo'][0] ?? null;

            if (! is_array($info)) {
                continue;
            }

            $mime = strtolower((string) ($info['mime'] ?? ''));

            if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                continue;
            }

            $url = $info['url'] ?? null;
            $miniatura = $info['thumburl'] ?? null;

            if (! ResultadoImagen::esUrlHttp($url) || ! ResultadoImagen::esUrlHttp($miniatura)) {
                continue;
            }

            $origen = $info['descriptionurl'] ?? null;
            $origen = ResultadoImagen::esUrlHttp($origen) ? $origen : null;
            $meta = is_array($info['extmetadata'] ?? null) ? $info['extmetadata'] : [];
            $licenciaUrl = $this->texto($meta['LicenseUrl']['value'] ?? null, 1000);

            $resultados[] = new ResultadoImagen(
                titulo: $this->titulo($pagina['title'] ?? null),
                miniatura: $miniatura,
                url: $url,
                origenUrl: $origen,
                fuente: 'commons.wikimedia.org',
                ancho: isset($info['width']) ? (int) $info['width'] : null,
                alto: isset($info['height']) ? (int) $info['height'] : null,
                autor: $this->texto($meta['Artist']['value'] ?? null, 150),
                licencia: $this->texto($meta['LicenseShortName']['value'] ?? null, 100),
                licenciaUrl: ResultadoImagen::esUrlHttp($licenciaUrl) ? $licenciaUrl : null,
            );
        }

        return $resultados;
    }

    private function titulo(mixed $valor): string
    {
        $texto = is_string($valor) ? trim(str_replace('_', ' ', Str::after($valor, 'File:'))) : '';
        $texto = (string) preg_replace('/\.(jpe?g|png|webp|gif|tiff?)$/i', '', $texto);

        return $texto === '' ? 'Imagen' : Str::limit($texto, 200, '');
    }

    private function texto(mixed $valor, int $maximo): ?string
    {
        if (! is_string($valor) || trim($valor) === '') {
            return null;
        }

        $texto = html_entity_decode(strip_tags($valor), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/\s+/u', ' ', trim($texto)) ?? '';

        return $texto === '' ? null : Str::limit($texto, $maximo, '');
    }
}
