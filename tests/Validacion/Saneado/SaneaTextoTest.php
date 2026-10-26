<?php

namespace Tests\Validacion\Saneado;

use App\Models\Categoria;
use App\Models\Cliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Política de saneado de texto libre (docs/modulos/00-contexto.md §13).
 *
 * Se verifica el valor que llega a la base de datos, no solo la respuesta.
 */
class SaneaTextoTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function casos(): array
    {
        return [
            'quita etiquetas' => ['<b>Hola</b> mundo', 'Hola mundo'],
            'colapsa espacios' => ['  Hola     mundo  ', 'Hola mundo'],
            'quita caracteres de control' => ["Hola\x00Mundo", 'HolaMundo'],
            'convierte tabulaciones en espacio' => ["Hola\tmundo", 'Holamundo'],
            'conserva acentos y eñes' => ['Ñandú computación', 'Ñandú computación'],
            'conserva signos permitidos' => ['Laptop (14") #1 - 50%', 'Laptop (14") #1 - 50%'],
        ];
    }

    #[DataProvider('casos')]
    public function test_el_nombre_de_categoria_se_sanea(string $entrada, string $esperado): void
    {
        $this->actingAs($this->gerente())
            ->post(route('categorias.store'), ['nombre' => $entrada])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categorias', ['nombre' => $esperado]);
    }

    #[DataProvider('casos')]
    public function test_el_nombre_de_cliente_se_sanea(string $entrada, string $esperado): void
    {
        // El cliente no aplica allowlist de caracteres, solo limpieza.
        $this->actingAs($this->vendedor())
            ->post(route('clientes.store'), ['nombre' => $entrada])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('clientes', ['nombre' => $esperado]);
    }

    public function test_un_texto_que_queda_vacio_es_tratado_como_ausente(): void
    {
        $this->actingAs($this->gerente())
            ->post(route('categorias.store'), ['nombre' => '<b>   </b>'])
            ->assertSessionHasErrors('nombre');

        $this->assertDatabaseCount('categorias', 0);
    }

    public function test_la_descripcion_opcional_queda_nula_si_solo_tiene_espacios(): void
    {
        $this->actingAs($this->gerente())
            ->post(route('categorias.store'), [
                'nombre' => 'Equipos',
                'descripcion' => '   <b></b>  ',
            ])
            ->assertSessionHasNoErrors();

        $categoria = Categoria::where('nombre', 'Equipos')->firstOrFail();

        $this->assertNull($categoria->descripcion);
    }

    public function test_el_carnet_se_limpia_antes_de_validar_unicidad(): void
    {
        Cliente::create(['nombre' => 'Existente', 'carnet' => '123456']);

        $this->actingAs($this->vendedor())
            ->post(route('clientes.store'), [
                'nombre' => 'Nuevo',
                'carnet' => '  <b>123456</b>  ',
            ])
            ->assertSessionHasErrors('carnet');
    }
}
