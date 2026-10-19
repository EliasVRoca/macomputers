<?php

namespace Tests\Feature;

use App\Models\Apartado;
use App\Models\Cambio;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\Encargo;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Role;
use App\Models\Servicio;
use App\Models\Sucursal;
use App\Models\Traspaso;
use App\Models\User;
use App\Models\Venta;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que cada formulario se envíe correctamente con el rol que le corresponde.
 */
class FormulariosTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

    private User $tecnico;

    private Sucursal $principal;

    private Sucursal $segunda;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->first();
        $this->vendedor = User::where('username', 'vendedor')->first();
        $this->tecnico = User::where('username', 'tecnico')->first();
        $this->principal = Sucursal::where('nombre', 'Sucursal principal')->first();
        $this->segunda = Sucursal::where('nombre', 'Segunda sucursal')->first();

        $categoria = Categoria::create(['nombre' => 'Laptops']);
        $this->producto = Producto::create([
            'categoria_id' => $categoria->id, 'nombre' => 'Laptop Lenovo', 'marca' => 'Lenovo', 'codigo' => 'LAP-1',
            'precio_compra' => 3000, 'precio_venta' => 3500, 'stock_minimo' => 1, 'activo' => true,
        ]);

        app(StockService::class)->mover($this->producto->id, $this->principal->id, 20, 'compra', 'inicial', $this->gerente->id);
    }

    // ---------------------------------------------------------- Gerente

    public function test_gerente_crea_categoria_sucursal_proveedor_producto(): void
    {
        $this->actingAs($this->gerente)->post(route('categorias.store'), ['nombre' => 'Accesorios', 'descripcion' => 'Periféricos'])
            ->assertRedirect(route('categorias.index'));
        $this->assertDatabaseHas('categorias', ['nombre' => 'Accesorios']);

        $this->actingAs($this->gerente)->post(route('sucursales.store'), [
            'nombre' => 'Sucursal Norte', 'direccion' => 'Av. Banzer', 'telefono' => '3333333', 'activa' => '1',
        ])->assertRedirect(route('sucursales.index'));
        $this->assertDatabaseHas('sucursales', ['nombre' => 'Sucursal Norte', 'activa' => true]);

        $this->actingAs($this->gerente)->post(route('proveedores.store'), [
            'nombre' => 'Importadora XYZ', 'contacto' => 'ventas@xyz.com', 'pais_origen' => 'Estados Unidos',
        ])->assertRedirect(route('proveedores.index'));
        $this->assertDatabaseHas('proveedores', ['nombre' => 'Importadora XYZ']);

        $this->actingAs($this->gerente)->post(route('productos.store'), [
            'categoria_id' => Categoria::first()->id, 'nombre' => 'Monitor 24"', 'marca' => 'Samsung', 'codigo' => 'MON-9',
            'precio_compra' => 700, 'precio_venta' => 950, 'stock_minimo' => 2, 'activo' => '1',
        ])->assertRedirect(route('productos.index'));
        $this->assertDatabaseHas('productos', ['codigo' => 'MON-9']);
    }

    public function test_gerente_crea_usuario_y_rol(): void
    {
        $this->actingAs($this->gerente)->post(route('usuarios.store'), [
            'nombre' => 'Nuevo Empleado', 'ci' => '9000001', 'telefono' => '70000001', 'direccion' => 'Calle 1',
            'username' => 'empleado1', 'email' => 'empleado1@macomputers.test',
            'password' => 'Clave12345', 'password_confirmation' => 'Clave12345',
            'role_id' => Role::where('nombre', 'Vendedor')->value('id'), 'sucursal_id' => $this->principal->id, 'activo' => '1',
        ])->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseHas('users', ['username' => 'empleado1']);
        $this->assertDatabaseHas('personas', ['ci' => '9000001']);

        $this->actingAs($this->gerente)->post(route('roles.store'), [
            'nombre' => 'Almacén', 'descripcion' => 'Solo inventario', 'permisos' => ['disponibilidad', 'inventario'],
        ])->assertRedirect(route('roles.index'));

        $usuario = User::where('username', 'empleado1')->first();
        $this->assertTrue($usuario->fresh()->tienePermiso('ventas'));
    }

    public function test_gerente_registra_compra_y_suma_stock(): void
    {
        $proveedor = Proveedor::create(['nombre' => 'Proveedor Uno']);

        $this->actingAs($this->gerente)->post(route('compras.store'), [
            'proveedor_id' => $proveedor->id, 'sucursal_id' => $this->principal->id, 'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 5, 'precio_compra' => 2900]],
        ])->assertRedirect(route('compras.index'));

        $this->assertSame(25, $this->stock($this->principal));
        $this->assertSame(1, Compra::count());
    }

    // --------------------------------------------------------- Vendedor

    public function test_vendedor_crea_cliente_apartado_encargo(): void
    {
        $this->actingAs($this->vendedor)->post(route('clientes.store'), [
            'nombre' => 'Cliente Nuevo', 'telefono' => '70011223', 'carnet' => '5555555',
        ])->assertRedirect(route('clientes.index'));
        $cliente = Cliente::firstOrFail();

        $this->actingAs($this->vendedor)->post(route('apartados.store'), [
            'cliente_id' => $cliente->id, 'producto_id' => $this->producto->id, 'cantidad' => 2,
            'sena' => 1000, 'fecha' => now()->toDateString(),
        ])->assertRedirect(route('apartados.index'));
        $this->assertSame(18, $this->stock($this->principal));
        $this->assertSame(1, Apartado::count());

        $this->actingAs($this->vendedor)->post(route('encargos.store'), [
            'cliente_id' => $cliente->id, 'descripcion' => 'Monitor 27"', 'total' => 1000,
            'anticipo' => 200, 'saldo' => 800, 'fecha' => now()->toDateString(),
        ])->assertRedirect(route('encargos.index'));
        $this->assertSame(1, Encargo::count());
    }

    public function test_vendedor_registra_venta_y_cambio(): void
    {
        $this->actingAs($this->vendedor)->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo', 'items' => [['producto_id' => $this->producto->id, 'cantidad' => 3]],
        ])->assertRedirect();
        $venta = Venta::firstOrFail();
        $this->assertSame(17, $this->stock($this->principal));

        $this->actingAs($this->vendedor)->post(route('cambios.store'), [
            'venta_id' => $venta->id, 'producto_id' => $this->producto->id, 'tipo' => 'devolucion',
            'cantidad' => 1, 'motivo' => 'Falla de fábrica', 'fecha' => now()->toDateString(),
        ])->assertRedirect(route('cambios.index'));

        $this->assertSame(18, $this->stock($this->principal));
        $this->assertSame(1, Cambio::count());
    }

    public function test_vendedor_registra_movimiento_de_inventario(): void
    {
        $this->actingAs($this->vendedor)->post(route('inventario.movimientos.store'), [
            'producto_id' => $this->producto->id, 'tipo' => 'entrada', 'cantidad' => 4, 'referencia' => 'Reposición',
        ])->assertRedirect();

        $this->assertSame(24, $this->stock($this->principal));
    }

    public function test_vendedor_registra_traspaso(): void
    {
        $this->actingAs($this->vendedor)->post(route('traspasos.store'), [
            'sucursal_destino_id' => $this->segunda->id, 'fecha' => now()->toDateString(),
            'items' => [['producto_id' => $this->producto->id, 'cantidad' => 6]],
        ])->assertRedirect(route('traspasos.index'));

        $this->assertSame(14, $this->stock($this->principal));
        $this->assertSame(6, $this->stock($this->segunda));
        $this->assertSame(1, Traspaso::count());
    }

    // ---------------------------------------------------------- Técnico

    public function test_tecnico_registra_servicio_como_venta(): void
    {
        $cliente = Cliente::create(['nombre' => 'Cliente Servicio', 'telefono' => '70099887']);

        $this->actingAs($this->tecnico)->post(route('servicios.store'), [
            'modo_cliente' => 'existente', 'cliente_id' => $cliente->id, 'equipo' => 'Laptop HP', 'diagnostico' => 'Limpieza interna',
            'mano_de_obra' => 150, 'prioridad' => 'normal', 'fecha_recepcion' => now()->toDateString(),
        ])->assertRedirect();

        $servicio = Servicio::firstOrFail();
        $this->assertSame('recibido', $servicio->estado);
        $this->assertSame('servicio', $servicio->venta->tipo);
        $this->assertSame(150.0, (float) $servicio->venta->total);
        $this->assertMatchesRegularExpression('/^OT-\d{6}$/', $servicio->numero);

        $this->actingAs($this->tecnico)->put(route('servicios.update', $servicio), [
            'equipo' => 'Laptop HP', 'diagnostico' => 'Limpieza interna', 'mano_de_obra' => 200,
            'prioridad' => 'alta', 'fecha_recepcion' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertSame(200.0, (float) $servicio->fresh()->venta->total);
        $this->assertSame('alta', $servicio->fresh()->prioridad);
    }

    public function test_tecnico_registra_servicio_creando_el_cliente(): void
    {
        $this->actingAs($this->tecnico)->post(route('servicios.store'), [
            'modo_cliente' => 'nuevo',
            'cliente_nombre' => 'Cliente Ocasional',
            'cliente_telefono' => '70055555',
            'cliente_carnet' => '9988776',
            'equipo' => 'PC de escritorio', 'diagnostico' => 'Formateo e instalación',
            'mano_de_obra' => 250, 'prioridad' => 'normal', 'fecha_recepcion' => now()->toDateString(),
        ])->assertRedirect();

        $cliente = Cliente::where('carnet', '9988776')->firstOrFail();
        $servicio = Servicio::firstOrFail();

        $this->assertSame('Cliente Ocasional', $cliente->nombre);
        $this->assertSame($cliente->id, $servicio->cliente_id);
        $this->assertSame($cliente->id, $servicio->venta->cliente_id);
        $this->assertSame('servicio', $servicio->venta->tipo);
    }

    public function test_cliente_nuevo_sin_nombre_es_rechazado(): void
    {
        $this->actingAs($this->tecnico)->post(route('servicios.store'), [
            'modo_cliente' => 'nuevo', 'equipo' => 'PC', 'mano_de_obra' => 100,
            'prioridad' => 'normal', 'fecha_recepcion' => now()->toDateString(),
        ])->assertSessionHasErrors('cliente_nombre');

        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('servicios', 0);
    }

    public function test_cliente_nuevo_con_carnet_duplicado_es_rechazado(): void
    {
        Cliente::create(['nombre' => 'Cliente Existente', 'carnet' => '1111111']);

        $this->actingAs($this->tecnico)->post(route('servicios.store'), [
            'modo_cliente' => 'nuevo', 'cliente_nombre' => 'Otro Cliente', 'cliente_carnet' => '1111111',
            'equipo' => 'PC', 'mano_de_obra' => 100, 'prioridad' => 'normal', 'fecha_recepcion' => now()->toDateString(),
        ])->assertSessionHasErrors('cliente_carnet');

        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('servicios', 0);
    }

    public function test_el_tecnico_no_accede_al_modulo_clientes(): void
    {
        $this->actingAs($this->tecnico)->get(route('clientes.index'))->assertForbidden();
        $this->actingAs($this->tecnico)->get(route('clientes.create'))->assertForbidden();
    }

    // ---------------------------------------------------- Validaciones

    public function test_la_validacion_muestra_errores_en_los_formularios(): void
    {
        $this->actingAs($this->gerente)->post(route('categorias.store'), ['nombre' => ''])
            ->assertSessionHasErrors('nombre');

        $this->actingAs($this->gerente)->post(route('usuarios.store'), [])
            ->assertSessionHasErrors(['nombre', 'ci', 'username', 'email', 'password', 'role_id', 'sucursal_id']);

        $this->actingAs($this->gerente)->post(route('productos.store'), [])
            ->assertSessionHasErrors(['categoria_id', 'nombre', 'marca', 'codigo', 'precio_compra', 'precio_venta', 'stock_minimo']);

        $this->actingAs($this->vendedor)->post(route('ventas.store'), ['medio_pago' => 'Efectivo', 'items' => []])
            ->assertSessionHasErrors('items');

        $this->actingAs($this->vendedor)->post(route('traspasos.store'), [
            'sucursal_origen_id' => $this->principal->id, 'sucursal_destino_id' => $this->principal->id,
            'fecha' => now()->toDateString(), 'items' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        ])->assertSessionHasErrors('sucursal_destino_id');
    }

    public function test_un_rol_no_puede_enviar_formularios_de_otro(): void
    {
        $this->actingAs($this->vendedor)->post(route('categorias.store'), ['nombre' => 'X'])
            ->assertForbidden();

        $this->actingAs($this->tecnico)->post(route('ventas.store'), [
            'medio_pago' => 'Efectivo', 'items' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        ])->assertForbidden();

        $this->actingAs($this->vendedor)->post(route('servicios.store'), [])->assertForbidden();
    }

    private function stock(Sucursal $sucursal): int
    {
        return (int) (Inventario::where('producto_id', $this->producto->id)
            ->where('sucursal_id', $sucursal->id)
            ->value('cantidad') ?? 0);
    }
}
