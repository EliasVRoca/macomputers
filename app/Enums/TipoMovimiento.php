<?php

namespace App\Enums;

/**
 * Tipos de movimiento del kardex (columna movimientos_inventario.tipo).
 *
 * Los tres primeros (entrada, salida y ajuste) los registra a mano el módulo
 * de Inventario; el resto los generan automáticamente los demás módulos
 * (compras, ventas, traspasos, apartados, cambios y servicio técnico).
 */
enum TipoMovimiento: string
{
    case Entrada = 'entrada';
    case Salida = 'salida';
    case Ajuste = 'ajuste';
    case Compra = 'compra';
    case Venta = 'venta';
    case AnulacionVenta = 'anulacion_venta';
    case Apartado = 'apartado';
    case LiberacionApartado = 'liberacion_apartado';
    case TraspasoSalida = 'traspaso_salida';
    case TraspasoEntrada = 'traspaso_entrada';
    case Cambio = 'cambio';
    case Devolucion = 'devolucion';
    case ConsumoServicio = 'consumo_servicio';
    case DevolucionServicio = 'devolucion_servicio';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Salida => 'Salida',
            self::Ajuste => 'Ajuste',
            self::Compra => 'Compra',
            self::Venta => 'Venta',
            self::AnulacionVenta => 'Anulación de venta',
            self::Apartado => 'Apartado',
            self::LiberacionApartado => 'Liberación de apartado',
            self::TraspasoSalida => 'Traspaso (salida)',
            self::TraspasoEntrada => 'Traspaso (entrada)',
            self::Cambio => 'Cambio',
            self::Devolucion => 'Devolución',
            self::ConsumoServicio => 'Consumo de servicio',
            self::DevolucionServicio => 'Devolución de servicio',
        };
    }

    /** @return array<int, self> Tipos que un usuario puede registrar a mano. */
    public static function manuales(): array
    {
        return [self::Entrada, self::Salida, self::Ajuste];
    }

    /** @return array<int, string> Valores de todos los tipos (para validar el filtro). */
    public static function valores(): array
    {
        return array_map(fn (self $tipo): string => $tipo->value, self::cases());
    }

    /** @return array<int, string> Valores de los tipos manuales. */
    public static function valoresManuales(): array
    {
        return array_map(fn (self $tipo): string => $tipo->value, self::manuales());
    }

    /** @return array<string, string> valor => etiqueta, para poblar selectores. */
    public static function opciones(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $tipo): array => [$tipo->value => $tipo->etiqueta()])
            ->all();
    }

    public static function desde(?string $valor): self
    {
        return self::tryFrom((string) $valor) ?? self::Entrada;
    }
}
