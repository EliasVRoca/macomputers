<?php

namespace App\Exceptions;

use RuntimeException;

class BusquedaImagenException extends RuntimeException
{
    public function __construct(string $message, public readonly int $status = 422)
    {
        parent::__construct($message);
    }

    public static function noConfigurado(string $message): self
    {
        return new self($message, 503);
    }

    public static function proveedor(string $message): self
    {
        return new self($message, 502);
    }

    public static function descarga(string $message): self
    {
        return new self($message, 422);
    }
}
