<?php

namespace Tests\Integracion\BaseDatos;

use App\Models\Inventario;
use App\Models\Persona;
use App\Models\Role;
use App\Models\Servicio;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Services\ServicioService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Integración · Restricciones del motor, borrado lógico y datos semilla.
 */
class ConsistenciaTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_no_se_duplica_el_inventario_de_un_producto_en_una_sucursal(): void
    {
        $producto = $this->crearProducto();

        Inventario::create([
            'producto_id' => $producto->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'cantidad' => 1,
        ]);

        $this->expectException(QueryException::class);

        Inventario::create([
            'producto_id' => $producto->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'cantidad' => 2,
        ]);
    }

    public function test_no_se_borra_una_categoria_con_productos(): void
    {
        $categoria = $this->crearCategoria();
        $this->crearProducto(['categoria_id' => $categoria->id]);

        $this->expectException(QueryException::class);

        $categoria->delete();
    }

    public function test_no_se_borra_un_producto_con_movimientos(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 1, $this->gerente());

        $this->expectException(QueryException::class);

        $producto->delete();
    }

    public function test_la_orden_de_servicio_usa_borrado_logico(): void
    {
        $servicio = app(ServicioService::class)->crear([
            'modo_cliente' => 'existente',
            'cliente_id' => $this->crearCliente()->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'equipo' => 'Laptop',
            'diagnostico' => 'Revisión',
            'mano_de_obra' => 100,
            'prioridad' => 'normal',
            'fecha_recepcion' => now()->toDateString(),
        ], $this->gerente());

        app(ServicioService::class)->eliminar($servicio, $this->gerente());

        $this->assertSoftDeleted('servicios', ['id' => $servicio->id]);
        $this->assertNotNull(Servicio::withTrashed()->find($servicio->id));
    }

    public function test_el_seeder_es_idempotente(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, User::count());
        $this->assertSame(3, Role::count());
        $this->assertSame(2, Sucursal::count());
    }

    public function test_no_se_duplica_el_username(): void
    {
        $persona = Persona::create(['nombre' => 'Duplicado', 'ci' => '9999999']);

        $this->expectException(QueryException::class);

        User::create([
            'username' => 'gerente',
            'email' => 'otro@macomputers.test',
            'password' => 'password',
            'persona_id' => $persona->id,
            'role_id' => $this->gerente()->role_id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'activo' => true,
        ]);
    }

    public function test_el_total_de_la_venta_coincide_con_la_suma_de_sus_items(): void
    {
        $productoA = $this->crearProducto(['precio_venta' => 123.45]);
        $productoB = $this->crearProducto(['precio_venta' => 67.89]);
        $this->darStock($productoA, $this->sucursalPrincipal(), 5, $this->gerente());
        $this->darStock($productoB, $this->sucursalPrincipal(), 5, $this->gerente());

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [
                ['producto_id' => $productoA->id, 'cantidad' => 3],
                ['producto_id' => $productoB->id, 'cantidad' => 2],
            ],
        ]);

        $venta = Venta::firstOrFail();
        $suma = (float) $venta->items->sum('subtotal');

        $this->assertSame(round($suma, 2), round((float) $venta->total, 2));
    }

    public function test_la_sucursal_no_se_borra_fisicamente(): void
    {
        $sucursal = $this->sucursalSegunda();
        $sucursal->update(['activa' => false]);

        $this->assertDatabaseHas('sucursales', ['id' => $sucursal->id]);
    }
}
