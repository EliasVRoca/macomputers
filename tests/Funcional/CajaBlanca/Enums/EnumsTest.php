<?php

namespace Tests\Funcional\CajaBlanca\Enums;

use App\Enums\CondicionProducto;
use App\Enums\EstadoServicio;
use App\Enums\ResultadoBusqueda;
use App\Enums\TipoMovimiento;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Caja blanca · Enums de dominio (tabla de transición y listas blancas).
 */
class EnumsTest extends TestCase
{
    /** @return array<string, array{0: EstadoServicio, 1: EstadoServicio, 2: bool}> */
    public static function transiciones(): array
    {
        $casos = [];

        foreach (EstadoServicio::cases() as $actual) {
            foreach (EstadoServicio::cases() as $nuevo) {
                $casos["{$actual->value} → {$nuevo->value}"] = [
                    $actual,
                    $nuevo,
                    in_array($nuevo, $actual->siguientes(), true),
                ];
            }
        }

        return $casos;
    }

    #[DataProvider('transiciones')]
    public function test_la_tabla_de_transiciones_de_servicio(
        EstadoServicio $actual,
        EstadoServicio $nuevo,
        bool $esperado,
    ): void {
        $this->assertSame($esperado, $actual->puedeTransicionarA($nuevo));
    }

    public function test_los_estados_finales_no_tienen_siguientes(): void
    {
        $this->assertSame([], EstadoServicio::Entregado->siguientes());
        $this->assertSame([], EstadoServicio::Cancelado->siguientes());
        $this->assertTrue(EstadoServicio::Entregado->esFinal());
        $this->assertTrue(EstadoServicio::Cancelado->esFinal());
        $this->assertFalse(EstadoServicio::Recibido->esFinal());
    }

    public function test_el_estado_desconocido_cae_en_recibido(): void
    {
        $this->assertSame(EstadoServicio::Recibido, EstadoServicio::desde('volando'));
        $this->assertSame(EstadoServicio::Recibido, EstadoServicio::desde(null));
    }

    public function test_solo_tres_tipos_de_movimiento_son_manuales(): void
    {
        $this->assertSame(
            ['entrada', 'salida', 'ajuste'],
            array_map(fn (TipoMovimiento $tipo): string => $tipo->value, TipoMovimiento::manuales()),
        );
    }

    public function test_las_condiciones_desconocidas_caen_en_nuevo(): void
    {
        $this->assertSame(CondicionProducto::Nuevo, CondicionProducto::desde('usado'));
        $this->assertSame(['nuevo', 'remanufacturado', 'seminuevo'], CondicionProducto::valores());
    }

    public function test_el_resultado_desconocido_cae_en_no_existe(): void
    {
        $this->assertSame(ResultadoBusqueda::NoExiste, ResultadoBusqueda::desde('otro'));
        $this->assertSame('Sin stock', ResultadoBusqueda::SinStock->etiqueta());
    }
}
