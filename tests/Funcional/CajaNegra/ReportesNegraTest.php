<?php

namespace Tests\Funcional\CajaNegra;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja negra · Reportes y exportaciones (RF18).
 */
class ReportesNegraTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    /** @return array<string, array{0: string}> */
    public static function exportaciones(): array
    {
        $rutas = [
            'categorias.export', 'productos.export', 'proveedores.export',
            'disponibilidad.export', 'inventario.kardex.export', 'inventario.existencias.export',
            'traspasos.export', 'compras.export', 'ventas.export', 'apartados.export',
            'cambios.export', 'clientes.export', 'servicios.export', 'buscador.reporte.export',
        ];

        return array_combine($rutas, array_map(fn (string $ruta): array => [$ruta], $rutas));
    }

    #[DataProvider('exportaciones')]
    public function test_el_gerente_descarga_todas_las_exportaciones(string $ruta): void
    {
        $respuesta = $this->actingAs($this->gerente())->get(route($ruta));

        $respuesta->assertOk();
        $respuesta->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringStartsWith("\xEF\xBB\xBF", $respuesta->streamedContent());
    }

    public function test_la_exportacion_de_ventas_respeta_el_filtro_de_sucursal(): void
    {
        $producto = $this->crearProducto();
        $principal = $this->crearVenta($producto, $this->sucursalPrincipal(), 1, $this->gerente());
        $segunda = $this->crearVenta($producto, $this->sucursalSegunda(), 1, $this->gerente());

        $contenido = $this->actingAs($this->gerente())
            ->get(route('ventas.export', ['sucursal_id' => $this->sucursalSegunda()->id]))
            ->streamedContent();

        $this->assertStringContainsString($segunda->numero(), $contenido);
        $this->assertStringNotContainsString($principal->numero(), $contenido);
    }

    public function test_la_exportacion_de_productos_respeta_la_busqueda(): void
    {
        $this->crearProducto(['nombre' => 'Laptop buscada', 'codigo' => 'BUS-1']);
        $this->crearProducto(['nombre' => 'Mouse oculto', 'codigo' => 'OEM-1']);

        $contenido = $this->actingAs($this->gerente())
            ->get(route('productos.export', ['buscar' => 'Laptop']))
            ->streamedContent();

        $this->assertStringContainsString('BUS-1', $contenido);
        $this->assertStringNotContainsString('OEM-1', $contenido);
    }

    public function test_un_vendedor_no_exporta_modulos_que_no_gestiona(): void
    {
        foreach (['categorias.export', 'productos.export', 'proveedores.export', 'compras.export', 'servicios.export'] as $ruta) {
            $this->actingAs($this->vendedor())->get(route($ruta))->assertForbidden();
        }
    }

    public function test_un_vendedor_exporta_los_modulos_de_su_trabajo(): void
    {
        foreach (['ventas.export', 'apartados.export', 'cambios.export', 'clientes.export', 'inventario.kardex.export'] as $ruta) {
            $this->actingAs($this->vendedor())->get(route($ruta))->assertOk();
        }
    }

    public function test_el_reporte_de_busquedas_es_solo_del_gerente(): void
    {
        $this->actingAs($this->vendedor())
            ->get(route('buscador.reporte.export'))
            ->assertForbidden();
    }
}
