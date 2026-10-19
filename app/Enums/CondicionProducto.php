<?php

namespace App\Enums;

/**
 * Condición de un producto del catálogo (RF6 · CU5).
 *
 * Distingue mercadería nueva de remanufacturada o seminueva; se muestra como
 * etiqueta en el listado y la ficha del producto.
 */
enum CondicionProducto: string
{
    case Nuevo = 'nuevo';
    case Remanufacturado = 'remanufacturado';
    case Seminuevo = 'seminuevo';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Nuevo => 'Nuevo',
            self::Remanufacturado => 'Remanufacturado',
            self::Seminuevo => 'Seminuevo',
        };
    }

    public function colorBadge(): string
    {
        return match ($this) {
            self::Nuevo => 'badge-ok',
            self::Remanufacturado => 'badge-warn',
            self::Seminuevo => 'badge-neutral',
        };
    }

    /** @return array<int, string> Valores de todas las condiciones (para validar). */
    public static function valores(): array
    {
        return array_map(fn (self $condicion): string => $condicion->value, self::cases());
    }

    /** @return array<string, string> valor => etiqueta, para poblar selectores. */
    public static function opciones(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $condicion): array => [$condicion->value => $condicion->etiqueta()])
            ->all();
    }

    public static function desde(?string $valor): self
    {
        return self::tryFrom((string) $valor) ?? self::Nuevo;
    }
}
