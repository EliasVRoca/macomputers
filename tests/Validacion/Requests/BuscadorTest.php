<?php

namespace Tests\Validacion\Requests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Validación del buscador y de la búsqueda remota de productos.
 */
class BuscadorTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosFiltro(): array
    {
        return [
            'término de 301 caracteres' => ['termino', ['termino' => str_repeat('a', 301)]],
            'categoría inexistente' => ['categoria_id', ['categoria_id' => 99999]],
            'condición inválida' => ['condicion', ['condicion' => 'usado']],
            'sucursal inexistente' => ['sucursal_id', ['sucursal_id' => 99999]],
            'solo stock no booleano' => ['solo_stock', ['solo_stock' => 'talvez']],
        ];
    }

    #[DataProvider('casosFiltro')]
    public function test_filtro_del_buscador_rechaza_datos_invalidos(string $campo, array $filtros): void
    {
        $this->actingAs($this->vendedor())
            ->get(route('buscador.index', $filtros))
            ->assertSessionHasErrors($campo);
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function casosTermino(): array
    {
        return [
            'sin término' => [['termino' => null]],
            'término de un carácter' => [['termino' => 'a']],
            'término de 101 caracteres' => [['termino' => str_repeat('a', 101)]],
        ];
    }

    #[DataProvider('casosTermino')]
    public function test_la_busqueda_json_exige_un_termino_valido(array $filtros): void
    {
        $this->actingAs($this->vendedor())
            ->getJson(route('buscador.json', $filtros))
            ->assertStatus(422)
            ->assertJsonValidationErrors('termino');
    }

    /** @return array<string, array{0: array<string, mixed>}> */
    public static function casosBusquedaRemota(): array
    {
        return [
            'sin q' => [['q' => null]],
            'q de un carácter' => [['q' => 'a']],
            'q de 101 caracteres' => [['q' => str_repeat('a', 101)]],
            'sucursal inexistente' => [['q' => 'laptop', 'sucursal_id' => 99999]],
        ];
    }

    #[DataProvider('casosBusquedaRemota')]
    public function test_la_busqueda_remota_exige_datos_validos(array $filtros): void
    {
        $this->actingAs($this->vendedor())
            ->getJson(route('productos.opciones', $filtros))
            ->assertStatus(422);
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosReporte(): array
    {
        return [
            'desde inválido' => ['desde', ['desde' => '26/10/2026']],
            'rango invertido' => ['hasta', ['desde' => '2026-10-26', 'hasta' => '2026-10-25']],
            'resultado inválido' => ['resultado', ['resultado' => 'otro']],
            'sucursal inexistente' => ['sucursal_id', ['sucursal_id' => 99999]],
        ];
    }

    #[DataProvider('casosReporte')]
    public function test_el_reporte_rechaza_filtros_invalidos(string $campo, array $filtros): void
    {
        $this->actingAs($this->gerente())
            ->get(route('buscador.reporte', $filtros))
            ->assertSessionHasErrors($campo);
    }
}
