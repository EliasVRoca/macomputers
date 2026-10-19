<?php

namespace App\Services\Imagenes;

use App\Exceptions\BusquedaImagenException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class DescargadorImagen
{
    /**
     * @return array{ruta: string, ancho: int, alto: int}
     */
    public function descargar(string $url, ?string $alternativa = null, ?string $referer = null): array
    {
        try {
            return $this->obtener($url, $referer);
        } catch (BusquedaImagenException $e) {
            if (filled($alternativa) && $alternativa !== $url) {
                return $this->obtener($alternativa, $referer);
            }

            throw $e;
        }
    }

    /**
     * @return array{ruta: string, ancho: int, alto: int}
     */
    private function obtener(string $url, ?string $referer): array
    {
        $maximo = max(1, (int) config('buscador_imagenes.max_bytes'));
        $maxRedirecciones = max(0, (int) config('buscador_imagenes.max_redirecciones'));
        $actual = $url;

        try {
            for ($intento = 0; $intento <= $maxRedirecciones; $intento++) {
                $this->validarUrl($actual);

                $respuesta = Http::timeout(max(1, (int) config('buscador_imagenes.descarga_timeout')))
                    ->connectTimeout(10)
                    ->withHeaders($this->cabeceras($referer))
                    ->withOptions([
                        'allow_redirects' => false,
                        'stream' => true,
                    ])
                    ->get($actual);

                if ($respuesta->redirect()) {
                    $ubicacion = $respuesta->header('Location');

                    if (! is_string($ubicacion) || $ubicacion === '' || $intento >= $maxRedirecciones) {
                        throw BusquedaImagenException::descarga('La imagen redirige demasiadas veces.');
                    }

                    $actual = (string) UriResolver::resolve(new Uri($actual), new Uri($ubicacion));

                    continue;
                }

                if ($respuesta->failed()) {
                    throw BusquedaImagenException::descarga('No se pudo descargar la imagen (HTTP '.$respuesta->status().').');
                }

                $largo = $respuesta->header('Content-Length');

                if ($largo !== null && (int) $largo > $maximo) {
                    throw BusquedaImagenException::descarga('La imagen supera el tamaño máximo permitido (5 MB).');
                }

                $cuerpo = '';
                $stream = $respuesta->toPsrResponse()->getBody();

                while (! $stream->eof()) {
                    $bloque = $stream->read(8192);

                    if ($bloque === '') {
                        break;
                    }

                    $cuerpo .= $bloque;

                    if (strlen($cuerpo) > $maximo) {
                        throw BusquedaImagenException::descarga('La imagen supera el tamaño máximo permitido (5 MB).');
                    }
                }

                if ($cuerpo === '') {
                    throw BusquedaImagenException::descarga('La imagen descargada está vacía.');
                }

                return $this->procesar($cuerpo);
            }
        } catch (ConnectionException $e) {
            Log::warning('No se pudo descargar una imagen externa.', [
                'host' => parse_url($actual, PHP_URL_HOST),
                'error' => $e->getMessage(),
            ]);

            throw BusquedaImagenException::descarga('No se pudo descargar la imagen. Probá con otra.');
        }

        throw BusquedaImagenException::descarga('No se pudo descargar la imagen. Probá con otra.');
    }

    private function validarUrl(string $url): void
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw BusquedaImagenException::descarga('La dirección de la imagen no es válida.');
        }

        $partes = parse_url($url);
        $esquema = strtolower((string) ($partes['scheme'] ?? ''));

        if (! in_array($esquema, ['http', 'https'], true)) {
            throw BusquedaImagenException::descarga('La dirección de la imagen no usa un protocolo permitido.');
        }

        if (isset($partes['user']) || isset($partes['pass'])) {
            throw BusquedaImagenException::descarga('La dirección de la imagen no es válida.');
        }

        $host = (string) ($partes['host'] ?? '');

        if ($host === '') {
            throw BusquedaImagenException::descarga('La dirección de la imagen no es válida.');
        }

        $puerto = (int) ($partes['port'] ?? ($esquema === 'https' ? 443 : 80));

        if (! in_array($puerto, [80, 443], true)) {
            throw BusquedaImagenException::descarga('La dirección de la imagen usa un puerto no permitido.');
        }

        $esIp = filter_var($host, FILTER_VALIDATE_IP) !== false;

        if ($esIp) {
            $ips = [$host];
        } else {
            $ips = gethostbynamel($host);

            if (! is_array($ips) || $ips === []) {
                $ips = [];

                $registros = @dns_get_record($host, DNS_AAAA);

                if (is_array($registros)) {
                    foreach ($registros as $registro) {
                        if (isset($registro['ipv6'])) {
                            $ips[] = $registro['ipv6'];
                        }
                    }
                }
            }
        }

        if ($ips === []) {
            throw BusquedaImagenException::descarga('No se pudo resolver el servidor de la imagen.');
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                throw BusquedaImagenException::descarga('La imagen está alojada en una dirección no permitida.');
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function cabeceras(?string $referer): array
    {
        $cabeceras = [
            'Accept' => 'image/avif,image/webp,image/png,image/jpeg,image/*;q=0.8',
            'User-Agent' => trim((string) config('buscador_imagenes.user_agent').' ('.config('app.url').')'),
        ];

        if (ResultadoImagen::esUrlHttp($referer)) {
            $cabeceras['Referer'] = $referer;
        }

        return $cabeceras;
    }

    /**
     * @return array{ruta: string, ancho: int, alto: int}
     */
    private function procesar(string $cuerpo): array
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {
            throw BusquedaImagenException::descarga('La optimización de imágenes no está disponible en el servidor.');
        }

        $temporal = tempnam(sys_get_temp_dir(), 'imagen');

        if ($temporal === false) {
            throw BusquedaImagenException::descarga('No se pudo procesar la imagen.');
        }

        try {
            if (file_put_contents($temporal, $cuerpo) === false) {
                throw BusquedaImagenException::descarga('No se pudo procesar la imagen.');
            }

            $info = @getimagesize($temporal);

            if ($info === false) {
                throw BusquedaImagenException::descarga('El archivo descargado no es una imagen válida.');
            }

            [$ancho, $alto, $tipo] = $info;

            if (! in_array($tipo, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                throw BusquedaImagenException::descarga('El formato de la imagen no está permitido.');
            }

            if (
                $ancho > (int) config('buscador_imagenes.ancho_fuente_max')
                || $alto > (int) config('buscador_imagenes.alto_fuente_max')
                || ($ancho * $alto) > (int) config('buscador_imagenes.max_pixeles')
            ) {
                throw BusquedaImagenException::descarga('La imagen tiene dimensiones demasiado grandes.');
            }

            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($temporal);

            if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                throw BusquedaImagenException::descarga('El archivo descargado no es una imagen válida.');
            }

            $imagen = @imagecreatefromstring($cuerpo);

            if ($imagen === false) {
                throw BusquedaImagenException::descarga('No se pudo procesar la imagen.');
            }

            try {
                $orientacion = 1;
                $exif = @exif_read_data($temporal);

                if (is_array($exif) && isset($exif['Orientation'])) {
                    $orientacion = (int) $exif['Orientation'];
                }

                if (in_array($orientacion, [3, 6, 8], true)) {
                    $grados = [3 => 180, 6 => -90, 8 => 90][$orientacion];
                    $fondo = imagecolorallocatealpha($imagen, 0, 0, 0, 127);
                    $rotada = imagerotate($imagen, $grados, $fondo);

                    if ($rotada !== false) {
                        imagedestroy($imagen);
                        $imagen = $rotada;
                    }
                }

                if ($tipo === IMAGETYPE_PNG || $tipo === IMAGETYPE_WEBP) {
                    imagepalettetotruecolor($imagen);
                    imagealphablending($imagen, false);
                    imagesavealpha($imagen, true);
                }

                $anchoActual = imagesx($imagen);
                $altoActual = imagesy($imagen);
                $escala = min(
                    1,
                    (int) config('buscador_imagenes.ancho_max') / max(1, $anchoActual),
                    (int) config('buscador_imagenes.alto_max') / max(1, $altoActual),
                );

                if ($escala < 1) {
                    $nuevoAncho = max(1, (int) round($anchoActual * $escala));
                    $nuevoAlto = max(1, (int) round($altoActual * $escala));
                    $escalada = imagescale($imagen, $nuevoAncho, $nuevoAlto, IMG_BICUBIC);

                    if ($escalada === false) {
                        throw BusquedaImagenException::descarga('No se pudo optimizar la imagen.');
                    }

                    imagedestroy($imagen);
                    $imagen = $escalada;

                    if ($tipo === IMAGETYPE_PNG || $tipo === IMAGETYPE_WEBP) {
                        imagealphablending($imagen, false);
                        imagesavealpha($imagen, true);
                    }
                }

                $nuevoAncho = imagesx($imagen);
                $nuevoAlto = imagesy($imagen);
                $ruta = 'productos/'.Str::uuid().'.webp';
                $webp = tempnam(sys_get_temp_dir(), 'webp');

                if ($webp === false) {
                    throw BusquedaImagenException::descarga('No se pudo optimizar la imagen.');
                }

                try {
                    if (! imagewebp($imagen, $webp, (int) config('buscador_imagenes.calidad_webp'))) {
                        throw BusquedaImagenException::descarga('No se pudo optimizar la imagen.');
                    }

                    $contenido = file_get_contents($webp);

                    if ($contenido === false || Storage::disk('public')->put($ruta, $contenido) === false) {
                        throw BusquedaImagenException::descarga('No se pudo guardar la imagen.');
                    }
                } finally {
                    @unlink($webp);
                }

                return [
                    'ruta' => $ruta,
                    'ancho' => $nuevoAncho,
                    'alto' => $nuevoAlto,
                ];
            } finally {
                @imagedestroy($imagen);
            }
        } finally {
            @unlink($temporal);
        }
    }
}
