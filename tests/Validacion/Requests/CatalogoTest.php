<?php

namespace Tests\Validacion\Requests;

use App\Models\Categoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Validación tipada del Catálogo: categorías, productos y proveedores.
 */
class CatalogoTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosCategoria(): array
    {
        return [
            'nombre vacío' => ['nombre', ['nombre' => '']],
            'nombre de 101 caracteres' => ['nombre', ['nombre' => str_repeat('a', 101)]],
            'descripción de 256 caracteres' => ['descripcion', ['descripcion' => str_repeat('a', 256)]],
        ];
    }

    #[DataProvider('casosCategoria')]
    public function test_categoria_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $this->actingAs($this->gerente())
            ->post(route('categorias.store'), array_merge($this->datosCategoria(), $cambios))
            ->assertSessionHasErrors($campo);
    }

    public function test_el_nombre_de_categoria_es_unico(): void
    {
        Categoria::create(['nombre' => 'Equipos']);

        $this->actingAs($this->gerente())
            ->post(route('categorias.store'), $this->datosCategoria())
            ->assertSessionHasErrors('nombre');
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosProducto(): array
    {
        return [
            'categoría inexistente' => ['categoria_id', ['categoria_id' => 99999]],
            'nombre vacío' => ['nombre', ['nombre' => '']],
            'nombre de 151 caracteres' => ['nombre', ['nombre' => str_repeat('a', 151)]],
            'marca vacía' => ['marca', ['marca' => '']],
            'marca de 101 caracteres' => ['marca', ['marca' => str_repeat('a', 101)]],
            'modelo de 101 caracteres' => ['modelo', ['modelo' => str_repeat('a', 101)]],
            'código vacío' => ['codigo', ['codigo' => '']],
            'código de 51 caracteres' => ['codigo', ['codigo' => str_repeat('a', 51)]],
            'precio de compra negativo' => ['precio_compra', ['precio_compra' => -1]],
            'precio de compra fuera de rango' => ['precio_compra', ['precio_compra' => 10000000000]],
            'precio de compra no numérico' => ['precio_compra', ['precio_compra' => 'mucho']],
            'precio de venta negativo' => ['precio_venta', ['precio_venta' => -1]],
            'precio de venta fuera de rango' => ['precio_venta', ['precio_venta' => 10000000000]],
            'stock mínimo negativo' => ['stock_minimo', ['stock_minimo' => -1]],
            'stock mínimo decimal' => ['stock_minimo', ['stock_minimo' => 1.5]],
            'condición inválida' => ['condicion', ['condicion' => 'usado']],
        ];
    }

    #[DataProvider('casosProducto')]
    public function test_producto_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $this->actingAs($this->gerente())
            ->post(route('productos.store'), array_merge($this->datosProducto(), $cambios))
            ->assertSessionHasErrors($campo);
    }

    public function test_el_codigo_de_producto_es_unico(): void
    {
        $this->crearProducto(['codigo' => 'LAP-1']);

        $this->actingAs($this->gerente())
            ->post(route('productos.store'), $this->datosProducto())
            ->assertSessionHasErrors('codigo');
    }

    public function test_el_producto_acepta_los_limites_validos(): void
    {
        $this->actingAs($this->gerente())
            ->post(route('productos.store'), $this->datosProducto([
                'precio_compra' => 0,
                'precio_venta' => 9999999999,
                'stock_minimo' => 0,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('productos', ['codigo' => 'LAP-1']);
    }

    /** @return array<string, array{0: string, 1: array<string, mixed>}> */
    public static function casosProveedor(): array
    {
        return [
            'nombre vacío' => ['nombre', ['nombre' => '']],
            'nombre de 151 caracteres' => ['nombre', ['nombre' => str_repeat('a', 151)]],
            'contacto de 151 caracteres' => ['contacto', ['contacto' => str_repeat('a', 151)]],
            'dirección de 256 caracteres' => ['direccion', ['direccion' => str_repeat('a', 256)]],
            'país de 101 caracteres' => ['pais_origen', ['pais_origen' => str_repeat('a', 101)]],
        ];
    }

    #[DataProvider('casosProveedor')]
    public function test_proveedor_rechaza_datos_invalidos(string $campo, array $cambios): void
    {
        $this->actingAs($this->gerente())
            ->post(route('proveedores.store'), array_merge($this->datosProveedor(), $cambios))
            ->assertSessionHasErrors($campo);
    }

    /** @return array<string, mixed> */
    private function datosCategoria(): array
    {
        return ['nombre' => 'Equipos', 'descripcion' => 'Computadoras y accesorios'];
    }

    /** @param  array<string, mixed>  $extra
     *  @return array<string, mixed> */
    private function datosProducto(array $extra = []): array
    {
        return array_merge([
            'categoria_id' => $this->crearCategoria()->id,
            'nombre' => 'Laptop Lenovo',
            'marca' => 'Lenovo',
            'modelo' => 'IdeaPad 3',
            'codigo' => 'LAP-1',
            'condicion' => 'nuevo',
            'precio_compra' => 3000,
            'precio_venta' => 3500,
            'stock_minimo' => 2,
            'activo' => '1',
        ], $extra);
    }

    /** @return array<string, mixed> */
    private function datosProveedor(): array
    {
        return [
            'nombre' => 'Proveedor de prueba',
            'contacto' => 'Juan Pérez',
            'direccion' => 'Calle 1',
            'pais_origen' => 'China',
        ];
    }
}
