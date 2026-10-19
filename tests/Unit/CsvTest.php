<?php

namespace Tests\Unit;

use App\Support\Csv;
use Tests\TestCase;

class CsvTest extends TestCase
{
    public function test_neutraliza_las_formulas_peligrosas(): void
    {
        $this->assertSame("'=1+1", Csv::celda('=1+1'));
        $this->assertSame("'+SUM(A1:A9)", Csv::celda('+SUM(A1:A9)'));
        $this->assertSame("'@cmd", Csv::celda('@cmd'));
        $this->assertSame("'-cmd", Csv::celda('-cmd'));
        $this->assertSame("'\t=1", Csv::celda("\t=1"));
    }

    public function test_conserva_numeros_y_texto_normal(): void
    {
        $this->assertSame(-3, Csv::celda(-3));
        $this->assertSame(-3.5, Csv::celda(-3.5));
        $this->assertSame('-3.50', Csv::celda('-3.50'));
        $this->assertSame('Traspaso #12', Csv::celda('Traspaso #12'));
        $this->assertSame('', Csv::celda(null));
    }

    public function test_descarga_con_bom_utf8_y_encabezados(): void
    {
        $respuesta = Csv::descargar('x.csv', ['A', 'B'], [['=1', 'hola']]);

        $this->assertSame('text/csv; charset=UTF-8', $respuesta->headers->get('Content-Type'));

        ob_start();
        $respuesta->sendContent();
        $contenido = (string) ob_get_clean();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $contenido);
        $this->assertStringContainsString('A;B', $contenido);
        $this->assertStringContainsString("'=1", $contenido);
        $this->assertStringContainsString('hola', $contenido);
    }
}
