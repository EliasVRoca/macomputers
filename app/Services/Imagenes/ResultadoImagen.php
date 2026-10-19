<?php

namespace App\Services\Imagenes;

final readonly class ResultadoImagen
{
    public function __construct(
        public string $titulo,
        public string $miniatura,
        public string $url,
        public ?string $origenUrl,
        public ?string $fuente,
        public ?int $ancho,
        public ?int $alto,
        public ?string $autor = null,
        public ?string $licencia = null,
        public ?string $licenciaUrl = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function aArray(string $proveedor): array
    {
        return [
            'proveedor' => $proveedor,
            'titulo' => $this->titulo,
            'miniatura' => $this->miniatura,
            'url' => $this->url,
            'origen_url' => $this->origenUrl,
            'fuente' => $this->fuente,
            'ancho' => $this->ancho,
            'alto' => $this->alto,
            'autor' => $this->autor,
            'licencia' => $this->licencia,
            'licencia_url' => $this->licenciaUrl,
        ];
    }

    public static function dominio(?string $url): ?string
    {
        $host = parse_url((string) $url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return preg_replace('/^www\./i', '', $host);
    }

    public static function esUrlHttp(?string $url): bool
    {
        return is_string($url) && preg_match('#^https?://#i', $url) === 1;
    }
}
