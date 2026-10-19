<?php

namespace App\Enums;

/**
 * Estados de una orden de trabajo de servicio técnico (CU14 / RF16).
 *
 * Flujo: Recibido → EnProceso → Listo → Entregado. Cancelado es un estado
 * final alcanzable desde cualquier estado no final.
 */
enum EstadoServicio: string
{
    case Recibido = 'recibido';
    case EnProceso = 'en_proceso';
    case Listo = 'listo';
    case Entregado = 'entregado';
    case Cancelado = 'cancelado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Recibido => 'Recibido',
            self::EnProceso => 'En proceso',
            self::Listo => 'Listo',
            self::Entregado => 'Entregado',
            self::Cancelado => 'Cancelado',
        };
    }

    public function colorBadge(): string
    {
        return match ($this) {
            self::Recibido => 'badge-neutral',
            self::EnProceso => 'badge-warn',
            self::Listo => 'badge-brand',
            self::Entregado => 'badge-ok',
            self::Cancelado => 'badge-danger',
        };
    }

    public function esFinal(): bool
    {
        return in_array($this, [self::Entregado, self::Cancelado], true);
    }

    /**
     * Estados a los que se puede avanzar desde el actual.
     *
     * @return array<int, self>
     */
    public function siguientes(): array
    {
        return match ($this) {
            self::Recibido => [self::EnProceso, self::Cancelado],
            self::EnProceso => [self::Listo, self::Cancelado],
            self::Listo => [self::Entregado, self::Cancelado],
            self::Entregado, self::Cancelado => [],
        };
    }

    public function puedeTransicionarA(self $nuevo): bool
    {
        return in_array($nuevo, $this->siguientes(), true);
    }

    /**
     * Opciones para poblar selectores: valor => etiqueta.
     *
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $estado) => [$estado->value => $estado->etiqueta()])
            ->all();
    }

    public static function desde(?string $valor): self
    {
        return self::tryFrom((string) $valor) ?? self::Recibido;
    }
}
