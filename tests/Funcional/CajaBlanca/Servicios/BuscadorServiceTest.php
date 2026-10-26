<?php

namespace Tests\Funcional\CajaBlanca\Servicios;

use App\Models\BusquedaProducto;
use App\Services\BuscadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja blanca · BuscadorService: normalización, prioridad, límite y bitácora.
 */
class BuscadorServiceTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_normaliza_el_termino_antes_de_buscar(): void
    {
        $producto = $this->crearProducto(['nombre' => 'Laptop Lenovo']);
        $this->darStock($producto, $this->sucursalPrincipal(), 1, $this->gerente());

        $resultado = app(BuscadorService::class)->buscar(['termino' => '  <b>Laptop</b>   Lenovo  '], $this->vendedor());

        $this->assertSame('laptop lenovo', $resultado['termino']);
        $this->assertSame(1, $resultado['total']);
    }

    public function test_prioriza_la_coincidencia_exacta_de_codigo(): void
    {
        $porNombre = $this->crearProducto(['nombre' => 'ABC', 'codigo' => 'ZZZ-1']);
        $porCodigo = $this->crearProducto(['nombre' => 'Otro', 'codigo' => 'ABC']);
        $this->darStock($porNombre, $this->sucursalPrincipal(), 1, $this->gerente());
        $this->darStock($porCodigo, $this->sucursalPrincipal(), 1, $this->gerente());

        $resultado = app(BuscadorService::class)->buscar(['termino' => 'ABC'], $this->vendedor());

        $this->assertSame($porCodigo->id, $resultado['productos'][0]['id']);
    }

    public function test_limita_los_resultados_a_veinticuatro(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            $producto = $this->crearProducto(['nombre' => "Laptop {$i}"]);
            $this->darStock($producto, $this->sucursalPrincipal(), 1, $this->gerente());
        }

        $resultado = app(BuscadorService::class)->buscar(['termino' => 'Laptop'], $this->gerente());

        $this->assertCount(24, $resultado['productos']);
        $this->assertSame(24, $resultado['total']);
    }

    public function test_registra_la_busqueda_una_sola_vez_en_cinco_segundos(): void
    {
        $this->crearProducto(['nombre' => 'Producto deduplicado']);
        $service = app(BuscadorService::class);

        $service->buscar(['termino' => 'Producto deduplicado'], $this->vendedor());
        $service->buscar(['termino' => 'Producto deduplicado'], $this->vendedor());

        $this->assertSame(1, BusquedaProducto::count());
    }

    public function test_no_registra_terminos_de_un_caracter(): void
    {
        app(BuscadorService::class)->buscar(['termino' => 'a'], $this->vendedor());

        $this->assertSame(0, BusquedaProducto::count());
    }

    public function test_el_vendedor_solo_ve_el_stock_de_su_sucursal(): void
    {
        $producto = $this->crearProducto(['nombre' => 'Producto sucursal']);
        $this->darStock($producto, $this->sucursalPrincipal(), 2, $this->gerente());
        $this->darStock($producto, $this->sucursalSegunda(), 9, $this->gerente());

        $resultado = app(BuscadorService::class)->buscar(['termino' => 'Producto sucursal'], $this->vendedor());
        $fila = $resultado['productos'][0];

        $this->assertSame(2, $fila['stock'][(string) $this->sucursalPrincipal()->id]);
        $this->assertSame(2, $fila['local']);
        $this->assertSame(11, $fila['total']);
    }

    public function test_el_gerente_puede_consultar_otra_sucursal(): void
    {
        $producto = $this->crearProducto(['nombre' => 'Producto otra sucursal']);
        $this->darStock($producto, $this->sucursalSegunda(), 4, $this->gerente());

        $resultado = app(BuscadorService::class)->buscar([
            'termino' => 'Producto otra sucursal',
            'sucursal_id' => $this->sucursalSegunda()->id,
        ], $this->gerente());

        $this->assertSame(1, $resultado['total']);
        $this->assertSame('encontrado', $resultado['resultado']);
    }

    public function test_el_reporte_agrupa_terminos_y_cuenta_sin_resultado(): void
    {
        $service = app(BuscadorService::class);
        $service->buscar(['termino' => 'inexistente uno'], $this->vendedor());
        $service->buscar(['termino' => 'inexistente uno'], $this->tecnico());

        $reporte = $service->reporte([]);

        $this->assertSame(1, $reporte['terminos']);
        $this->assertSame('inexistente uno', $reporte['top']);
    }
}
