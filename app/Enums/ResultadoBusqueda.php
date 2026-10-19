<?php

namespace App\Enums;

/**
 * Resultado de una búsqueda registrada en el buscador inteligente.
 *
 * - encontrado: hay coincidencias y stock disponible (en cualquier sucursal).
 * - sin_stock: hay coincidencias pero ninguna tiene stock.
 * - no_existe: no hay coincidencias en el catálogo activo.
 */
enum ResultadoBusqueda: string
{
    case Encontrado = 'encontrado';
    case SinStock = 'sin_stock';
    case NoExiste = 'no_existe';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Encontrado => 'Encontrado',
            self::SinStock => 'Sin stock',
            self::NoExiste => 'No existe',
        };
    }

    public function colorBadge(): string
    {
        return match ($this) {
            self::Encontrado => 'badge-ok',
            self::SinStock => 'badge-warn',
            self::NoExiste => 'badge-danger',
        };
    }

    /** @return array<string, string> */
    public static function opciones(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $resultado): array => [$resultado->value => $resultado->etiqueta()])
            ->all();
    }

    public static function desde(?string $valor): self
    {
        return self::tryFrom((string) $valor) ?? self::NoExiste;
    }
}
