<?php

namespace Tests\Seguridad\Entrada;

use App\Models\Categoria;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * OWASP Top 10:2025 · A05 / App\Support\Csv.
 *
 * Un valor que empieza con =, +, - o @ se convierte en fórmula al abrir el CSV
 * en Excel/LibreOffice; el helper debe neutralizarlo con un apóstrofo.
 */
class CsvInjectionTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_las_categorias_exportadas_neutralizan_formulas(): void
    {
        Categoria::create(['nombre' => '=cmd|\' /C calc\'!A0']);

        $contenido = $this->actingAs($this->gerente())
            ->get(route('categorias.export'))
            ->streamedContent();

        $this->assertStringContainsString("'=cmd", $contenido);
        $this->assertStringNotContainsString("\n=cmd", $contenido);
    }

    public function test_los_productos_exportados_neutralizan_formulas(): void
    {
        $this->crearProducto(['nombre' => '+SUM(A1:A9)']);

        $contenido = $this->actingAs($this->gerente())
            ->get(route('productos.export'))
            ->streamedContent();

        $this->assertStringContainsString("'+SUM", $contenido);
    }

    public function test_los_clientes_exportados_neutralizan_formulas(): void
    {
        Cliente::create(['nombre' => '@SUM(1+1)', 'activo' => true]);

        $contenido = $this->actingAs($this->gerente())
            ->get(route('clientes.export'))
            ->streamedContent();

        $this->assertStringContainsString("'@SUM", $contenido);
    }

    public function test_las_ventas_exportadas_neutralizan_formulas_en_el_cliente(): void
    {
        $producto = $this->crearProducto();
        $cliente = $this->crearCliente('-2+3+cmd');
        $this->crearVenta($producto, $this->sucursalPrincipal(), 1, $this->gerente(), $cliente);

        $contenido = $this->actingAs($this->gerente())
            ->get(route('ventas.export'))
            ->streamedContent();

        $this->assertStringContainsString("'-2+3+cmd", $contenido);
    }

    public function test_el_csv_incluye_bom_utf8(): void
    {
        $contenido = $this->actingAs($this->gerente())
            ->get(route('categorias.export'))
            ->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);
    }
}
