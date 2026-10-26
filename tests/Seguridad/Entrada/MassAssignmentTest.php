<?php

namespace Tests\Seguridad\Entrada;

use App\Models\Compra;
use App\Models\Inventario;
use App\Models\Role;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * OWASP Top 10:2025 · A01/A06 / ASVS 5.0 · V2.
 *
 * Campos que el formulario no expone (total, estado, rol, sucursal, stock) no
 * deben poder inyectarse por mass assignment.
 */
class MassAssignmentTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_una_venta_ignora_total_estado_tipo_y_usuario_enviados(): void
    {
        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 10);

        $this->actingAs($this->vendedor())->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo',
            'items' => [['producto_id' => $producto->id, 'cantidad' => 1]],
            'total' => 1,
            'estado' => Venta::ESTADO_ANULADA,
            'tipo' => Venta::TIPO_SERVICIO,
            'user_id' => $this->tecnico()->id,
            'sucursal_id' => $this->sucursalSegunda()->id,
        ])->assertRedirect();

        $venta = Venta::firstOrFail();

        $this->assertSame(3500.0, (float) $venta->total);
        $this->assertSame(Venta::ESTADO_ACTIVA, $venta->estado);
        $this->assertSame(Venta::TIPO_VENTA, $venta->tipo);
        $this->assertSame($this->vendedor()->id, $venta->user_id);
        $this->assertSame($this->sucursalPrincipal()->id, $venta->sucursal_id);
    }

    public function test_una_compra_calcula_el_total_en_el_servidor(): void
    {
        $proveedor = $this->crearProveedor();
        $producto = $this->crearProducto();

        $this->actingAs($this->gerente())->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'fecha' => now()->toDateString(),
            'total' => 1,
            'items' => [['producto_id' => $producto->id, 'cantidad' => 2, 'precio_compra' => 100]],
        ])->assertRedirect();

        $this->assertSame(200.0, (float) Compra::firstOrFail()->total);
    }

    public function test_el_perfil_ignora_rol_sucursal_y_estado(): void
    {
        $usuario = $this->vendedor();
        $rolGerente = Role::where('nombre', 'Gerente')->firstOrFail();

        $this->actingAs($usuario)->put(route('perfil.update'), [
            'nombre' => 'Vendedor Renombrado',
            'email' => 'vendedor@macomputers.test',
            'role_id' => $rolGerente->id,
            'sucursal_id' => $this->sucursalSegunda()->id,
            'activo' => 0,
        ])->assertRedirect();

        $usuario->refresh();

        $this->assertSame($this->vendedor()->role_id, $usuario->role_id);
        $this->assertSame($this->sucursalPrincipal()->id, $usuario->sucursal_id);
        $this->assertTrue($usuario->activo);
    }

    public function test_el_alta_de_rol_ignora_es_sistema(): void
    {
        $this->actingAs($this->gerente())->post(route('roles.store'), [
            'nombre' => 'Rol inyectado',
            'descripcion' => 'Prueba de mass assignment',
            'permisos' => ['ventas'],
            'es_sistema' => 1,
        ])->assertRedirect();

        $rol = Role::where('nombre', 'Rol inyectado')->firstOrFail();

        $this->assertFalse($rol->es_sistema);
    }

    public function test_el_alta_de_producto_no_permite_inyectar_stock(): void
    {
        $categoria = $this->crearCategoria();

        $this->actingAs($this->gerente())->post(route('productos.store'), [
            'categoria_id' => $categoria->id,
            'nombre' => 'Producto con stock inyectado',
            'marca' => 'Marca',
            'codigo' => 'INY-1',
            'precio_compra' => 100,
            'precio_venta' => 150,
            'stock_minimo' => 1,
            'activo' => '1',
            'stock' => 999,
            'inventarios' => [['sucursal_id' => $this->sucursalPrincipal()->id, 'cantidad' => 999]],
        ])->assertRedirect();

        $this->assertSame(0, Inventario::count());
    }
}
