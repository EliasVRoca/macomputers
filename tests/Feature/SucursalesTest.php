<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SucursalesTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

    private Sucursal $principal;

    private Sucursal $segunda;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->firstOrFail();
        $this->vendedor = User::where('username', 'vendedor')->firstOrFail();
        $this->principal = Sucursal::where('nombre', 'Sucursal principal')->firstOrFail();
        $this->segunda = Sucursal::where('nombre', 'Segunda sucursal')->firstOrFail();
    }

    public function test_la_baja_es_logica_y_conserva_la_sucursal(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'Sucursal Norte',
            'direccion' => 'Av. Banzer',
            'activa' => true,
        ]);

        $this->actingAs($this->gerente)
            ->delete(route('sucursales.destroy', $sucursal))
            ->assertRedirect(route('sucursales.index'));

        $this->assertDatabaseHas('sucursales', ['id' => $sucursal->id, 'activa' => false]);
    }

    public function test_no_da_de_baja_una_sucursal_con_stock(): void
    {
        $this->crearStockEn($this->principal, 5);

        $this->actingAs($this->gerente)
            ->delete(route('sucursales.destroy', $this->principal))
            ->assertSessionHasErrors('eliminar');

        $this->assertTrue($this->principal->fresh()->activa);
    }

    public function test_no_da_de_baja_una_sucursal_con_usuarios_activos(): void
    {
        $this->actingAs($this->gerente)
            ->delete(route('sucursales.destroy', $this->segunda))
            ->assertSessionHasErrors('eliminar');

        $this->assertTrue($this->segunda->fresh()->activa);
    }

    public function test_no_da_de_baja_la_ultima_sucursal_activa(): void
    {
        Sucursal::query()->update(['activa' => false]);

        $unica = Sucursal::create([
            'nombre' => 'Única sucursal',
            'direccion' => 'Calle 1',
            'activa' => true,
        ]);

        $this->actingAs($this->gerente)
            ->delete(route('sucursales.destroy', $unica))
            ->assertSessionHasErrors('eliminar');

        $this->assertTrue($unica->fresh()->activa);
    }

    public function test_activa_y_desactiva_una_sucursal_vacia(): void
    {
        $sucursal = Sucursal::create([
            'nombre' => 'Sucursal Norte',
            'direccion' => 'Av. Banzer',
            'activa' => true,
        ]);

        $this->actingAs($this->gerente)
            ->patch(route('sucursales.estado', $sucursal), ['activa' => 0])
            ->assertRedirect(route('sucursales.index'));

        $this->assertFalse($sucursal->fresh()->activa);

        $this->actingAs($this->gerente)
            ->patch(route('sucursales.estado', $sucursal), ['activa' => 1])
            ->assertRedirect(route('sucursales.index'));

        $this->assertTrue($sucursal->fresh()->activa);
    }

    public function test_el_formulario_no_permite_desactivar_con_stock(): void
    {
        $this->crearStockEn($this->principal, 3);

        $this->actingAs($this->gerente)
            ->put(route('sucursales.update', $this->principal), [
                'nombre' => $this->principal->nombre,
                'direccion' => $this->principal->direccion,
                'telefono' => $this->principal->telefono,
                'activa' => 0,
            ])
            ->assertSessionHasErrors('activa');

        $this->assertTrue($this->principal->fresh()->activa);
    }

    public function test_un_vendedor_no_accede_al_modulo(): void
    {
        $this->actingAs($this->vendedor)->get(route('sucursales.index'))->assertForbidden();
        $this->actingAs($this->vendedor)
            ->delete(route('sucursales.destroy', $this->segunda))
            ->assertForbidden();
    }

    private function crearStockEn(Sucursal $sucursal, int $cantidad): void
    {
        $categoria = Categoria::create(['nombre' => 'Laptops']);

        $producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Laptop Lenovo',
            'marca' => 'Lenovo',
            'codigo' => 'LAP-1',
            'precio_compra' => 3000,
            'precio_venta' => 3500,
            'stock_minimo' => 1,
            'activo' => true,
        ]);

        Inventario::create([
            'producto_id' => $producto->id,
            'sucursal_id' => $sucursal->id,
            'cantidad' => $cantidad,
        ]);
    }
}
