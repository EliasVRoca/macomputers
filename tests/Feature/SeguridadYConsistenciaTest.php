<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Persona;
use App\Models\Producto;
use App\Models\Role;
use App\Models\Servicio;
use App\Models\Sucursal;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaItem;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguridadYConsistenciaTest extends TestCase
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

    public function test_no_se_puede_registrar_un_cambio_sobre_una_venta_de_otra_sucursal(): void
    {
        $producto = $this->producto();
        $venta = $this->venta($this->segunda, $producto);

        $this->actingAs($this->vendedor)->post(route('cambios.store'), [
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'tipo' => 'devolucion',
            'cantidad' => 1,
            'motivo' => 'Falla',
            'fecha' => now()->toDateString(),
        ])->assertForbidden();

        $this->assertDatabaseCount('cambios', 0);
    }

    public function test_un_gestor_de_usuarios_no_puede_asignar_el_rol_de_sistema(): void
    {
        $rol = Role::create([
            'nombre' => 'Gestor de usuarios',
            'descripcion' => 'Administra cuentas.',
            'permisos' => ['usuarios'],
            'es_sistema' => false,
        ]);

        $persona = Persona::create([
            'nombre' => 'Gestor de usuarios',
            'ci' => '9000011',
        ]);

        $gestor = User::create([
            'username' => 'gestor',
            'email' => 'gestor@macomputers.test',
            'password' => 'password',
            'persona_id' => $persona->id,
            'role_id' => $rol->id,
            'sucursal_id' => $this->principal->id,
            'activo' => true,
        ]);

        $rolGerente = Role::where('nombre', 'Gerente')->firstOrFail();

        $this->actingAs($gestor)->post(route('usuarios.store'), [
            'nombre' => 'Nuevo Gerente',
            'ci' => '9000012',
            'username' => 'nuevo_gerente',
            'email' => 'nuevo.gerente@macomputers.test',
            'password' => 'Clave12345',
            'password_confirmation' => 'Clave12345',
            'role_id' => $rolGerente->id,
            'sucursal_id' => $this->principal->id,
            'activo' => '1',
        ])->assertSessionHasErrors('role_id');

        $this->assertDatabaseMissing('users', ['username' => 'nuevo_gerente']);
    }

    public function test_un_vendedor_no_ve_las_ordenes_de_servicio_en_la_ficha_del_cliente(): void
    {
        $cliente = Cliente::create(['nombre' => 'Cliente con servicio', 'activo' => true]);
        $venta = $this->venta($this->principal, $this->producto(), Venta::TIPO_SERVICIO, $cliente);

        $servicio = Servicio::create([
            'venta_id' => $venta->id,
            'cliente_id' => $cliente->id,
            'sucursal_id' => $this->principal->id,
            'tecnico_id' => $this->gerente->id,
            'numero' => 'OT-999999',
            'equipo' => 'Laptop de prueba',
            'estado' => 'recibido',
            'fecha_recepcion' => now()->toDateString(),
            'prioridad' => 'normal',
            'mano_de_obra' => 100,
        ]);

        $this->actingAs($this->vendedor)
            ->get(route('clientes.show', $cliente))
            ->assertOk()
            ->assertDontSee('Servicios técnicos')
            ->assertDontSee($servicio->numero);

        $this->actingAs($this->gerente)
            ->get(route('clientes.show', $cliente))
            ->assertOk()
            ->assertSee('Servicios técnicos')
            ->assertSee($servicio->numero);
    }

    public function test_la_busqueda_de_ventas_encuentra_por_numero_de_nota(): void
    {
        $venta = $this->venta($this->principal, $this->producto());

        $this->actingAs($this->vendedor)
            ->get(route('ventas.index', ['buscar' => $venta->numero()]))
            ->assertOk()
            ->assertSee($venta->numero());
    }

    public function test_los_nombres_de_clientes_no_se_interpolan_dentro_de_onsubmit(): void
    {
        Cliente::create([
            'nombre' => "x');alert(1);//",
            'activo' => true,
        ]);

        $this->actingAs($this->gerente)
            ->get(route('clientes.index'))
            ->assertOk()
            ->assertDontSee('onsubmit=', false)
            ->assertSee('data-confirm="¿Eliminar al cliente x&#039;);alert(1);//?', false);
    }

    private function producto(): Producto
    {
        return Producto::create([
            'categoria_id' => Categoria::create(['nombre' => 'Pruebas'])->id,
            'nombre' => 'Producto de prueba',
            'marca' => 'Marca',
            'codigo' => 'PRU-'.uniqid(),
            'precio_compra' => 100,
            'precio_venta' => 150,
            'stock_minimo' => 1,
            'activo' => true,
        ]);
    }

    private function venta(Sucursal $sucursal, Producto $producto, string $tipo = Venta::TIPO_VENTA, ?Cliente $cliente = null): Venta
    {
        $venta = Venta::create([
            'sucursal_id' => $sucursal->id,
            'cliente_id' => $cliente?->id,
            'user_id' => $this->gerente->id,
            'tipo' => $tipo,
            'fecha' => now(),
            'medio_pago' => 'Efectivo',
            'total' => $producto->precio_venta,
            'estado' => Venta::ESTADO_ACTIVA,
        ]);

        VentaItem::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'descripcion' => $producto->nombre,
            'cantidad' => 1,
            'precio_unitario' => $producto->precio_venta,
            'subtotal' => $producto->precio_venta,
        ]);

        return $venta;
    }
}
