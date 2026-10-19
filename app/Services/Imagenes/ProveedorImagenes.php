<?php

namespace App\Services\Imagenes;

interface ProveedorImagenes
{
    public function clave(): string;

    public function etiqueta(): string;

    public function urlAtribucion(): string;

    /**
     * @return array<int, ResultadoImagen>
     */
    public function buscar(string $consulta, int $limite): array;
}
