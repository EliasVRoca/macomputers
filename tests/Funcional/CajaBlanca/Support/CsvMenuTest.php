<?php

namespace Tests\Funcional\CajaBlanca\Support;

use App\Support\Csv;
use App\Support\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Caja blanca · Helpers de soporte (CSV seguro y menú por permisos).
 */
class CsvMenuTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    public function test_el_csv_neutraliza_formulas_y_conserva_numeros(): void
    {
        $this->assertSame("'=1+1", Csv::celda('=1+1'));
        $this->assertSame("'+SUM(A1:A9)", Csv::celda('+SUM(A1:A9)'));
        $this->assertSame("'@cmd", Csv::celda('@cmd'));
        $this->assertSame("'-cmd", Csv::celda('-cmd'));
        $this->assertSame("'\t=1", Csv::celda("\t=1"));
        $this->assertSame(-3, Csv::celda(-3));
        $this->assertSame(-3.5, Csv::celda(-3.5));
        $this->assertSame('-3.50', Csv::celda('-3.50'));
        $this->assertSame('Traspaso #12', Csv::celda('Traspaso #12'));
        $this->assertSame('', Csv::celda(null));
    }

    public function test_el_csv_incluye_bom_y_encabezados(): void
    {
        $respuesta = Csv::descargar('prueba.csv', ['Nombre', 'Total'], [['=cmd', 10]]);

        ob_start();
        $respuesta->sendContent();
        $contenido = (string) ob_get_clean();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);
        $this->assertStringContainsString('Nombre;Total', $contenido);
        $this->assertStringContainsString("'=cmd;10", $contenido);
        $this->assertSame('text/csv; charset=UTF-8', $respuesta->headers->get('Content-Type'));
    }

    public function test_el_menu_del_gerente_incluye_los_cinco_grupos(): void
    {
        $this->sembrar();

        $grupos = Menu::para($this->gerente());

        $this->assertCount(5, $grupos);
        $this->assertSame(
            ['Administración', 'Catálogo', 'Inventario', 'Ventas', 'Servicio técnico'],
            array_column($grupos, 'nombre'),
        );
    }

    public function test_el_menu_del_vendedor_no_incluye_administracion_ni_catalogo(): void
    {
        $this->sembrar();

        $nombres = array_column(Menu::para($this->vendedor()), 'nombre');

        $this->assertNotContains('Administración', $nombres);
        $this->assertNotContains('Catálogo', $nombres);
        $this->assertContains('Ventas', $nombres);
        $this->assertContains('Inventario', $nombres);
    }

    public function test_el_menu_del_tecnico_solo_incluye_sus_grupos(): void
    {
        $this->sembrar();

        $nombres = array_column(Menu::para($this->tecnico()), 'nombre');

        $this->assertSame(['Inventario', 'Servicio técnico'], $nombres);
    }

    public function test_sin_usuario_no_hay_menu(): void
    {
        $this->assertSame([], Menu::para(null));
    }

    public function test_las_acciones_rapidas_respetan_el_permiso(): void
    {
        $this->sembrar();

        $gerente = array_column(Menu::acciones($this->gerente()), 'label');
        $vendedor = array_column(Menu::acciones($this->vendedor()), 'label');

        $this->assertContains('Nuevo producto', $gerente);
        $this->assertNotContains('Nuevo producto', $vendedor);
        $this->assertContains('Nueva venta', $vendedor);
    }

    public function test_el_menu_marca_activo_el_modulo_actual(): void
    {
        $this->sembrar();

        $this->actingAs($this->gerente())->get(route('productos.index'));

        $grupos = Menu::para($this->gerente());
        $catalogo = collect($grupos)->firstWhere('nombre', 'Catálogo');

        $this->assertTrue($catalogo['activo']);
        $this->assertTrue(collect($catalogo['modulos'])->firstWhere('clave', 'productos')['activo']);
    }
}
