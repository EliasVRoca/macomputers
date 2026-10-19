<?php

namespace Tests\Feature;

use App\Enums\EstadoServicio;
use App\Enums\ResultadoBusqueda;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\Persona;
use App\Models\Producto;
use App\Models\Role;
use App\Models\Servicio;
use App\Models\Sucursal;
use App\Models\Traspaso;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

    private User $tecnico;

    private Sucursal $principal;

    private Sucursal $segunda;

    private Categoria $categoria;

    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->gerente = User::where('username', 'gerente')->firstOrFail();
        $this->vendedor = User::where('username', 'vendedor')->firstOrFail();
        $this->tecnico = User::where('username', 'tecnico')->firstOrFail();
        $this->principal = Sucursal::where('nombre', 'Sucursal principal')->firstOrFail();
        $this->segunda = Sucursal::where('nombre', 'Segunda sucursal')->firstOrFail();
        $this->categoria = Categoria::create(['nombre' => 'Periféricos']);
        $this->cliente = Cliente::create(['nombre' => 'Cliente de prueba', 'activo' => true]);
    }

    public function test_gerente_ve_las_cinco_tarjetas_y_sus_metricas(): void
    {
        $this->producto('BAJO-1', 5, 2, 1);
        $this->producto('ALTO-1', 2, 10, 5);
        $this->producto('SIN-STOCK', 1);

        $this->venta($this->principal, 100);
        $this->venta($this->principal, 50);
        $this->venta($this->segunda, 200);
        $this->venta($this->principal, 999, Venta::TIPO_VENTA, Carbon::yesterday()->setTime(12, 0));
        $this->venta($this->principal, 777, Venta::TIPO_VENTA, null, Venta::ESTADO_ANULADA);
        $this->venta($this->principal, 300, Venta::TIPO_SERVICIO);

        $this->servicio($this->principal, EstadoServicio::Recibido);
        $this->servicio($this->principal, EstadoServicio::EnProceso);
        $this->servicio($this->principal, EstadoServicio::Listo);
        $this->servicio($this->segunda, EstadoServicio::Recibido);
        $this->servicio($this->segunda, EstadoServicio::Listo);
        $this->servicio($this->principal, EstadoServicio::Entregado);
        $this->servicio($this->principal, EstadoServicio::Cancelado);

        $this->traspaso($this->principal, $this->segunda);
        $this->traspaso($this->principal, $this->segunda, Carbon::yesterday());

        $this->busqueda($this->principal, ResultadoBusqueda::NoExiste);
        $this->busqueda($this->principal, ResultadoBusqueda::SinStock);
        $this->busqueda($this->principal, ResultadoBusqueda::Encontrado);
        $this->busqueda($this->segunda, ResultadoBusqueda::NoExiste);
        $this->busqueda($this->principal, ResultadoBusqueda::NoExiste, Carbon::yesterday());

        $respuesta = $this->actingAs($this->gerente)
            ->get(route('inicio'))
            ->assertOk()
            ->assertSee('data-kpi="ventas"', false)
            ->assertSee('data-kpi="bajo-minimo"', false)
            ->assertSee('data-kpi="servicios"', false)
            ->assertSee('data-kpi="traspasos"', false)
            ->assertSee('data-kpi="busquedas"', false);

        $metricas = $respuesta->viewData('metricas');

        $this->assertSame(350.0, $metricas['ventas']['total']);
        $this->assertSame(3, $metricas['ventas']['cantidad']);
        $this->assertSame(2, $metricas['bajo_minimo']);
        $this->assertSame(3, $metricas['ot_pendientes']);
        $this->assertSame(2, $metricas['ot_listas']);
        $this->assertSame(1, $metricas['traspasos']);
        $this->assertSame(3, $metricas['busquedas_sin_resultado']);
    }

    public function test_vendedor_solo_ve_sus_tarjetas_y_su_sucursal(): void
    {
        $this->producto('BAJO-1', 5, 2, 1);

        $this->venta($this->principal, 150);
        $this->venta($this->segunda, 300);
        $this->servicio($this->principal, EstadoServicio::Recibido);
        $this->traspaso($this->principal, $this->segunda);
        $this->busqueda($this->principal, ResultadoBusqueda::NoExiste);
        $this->busqueda($this->segunda, ResultadoBusqueda::NoExiste);

        $respuesta = $this->actingAs($this->vendedor)
            ->get(route('inicio'))
            ->assertOk()
            ->assertSee('data-kpi="ventas"', false)
            ->assertSee('data-kpi="bajo-minimo"', false)
            ->assertSee('data-kpi="traspasos"', false)
            ->assertSee('data-kpi="busquedas"', false)
            ->assertDontSee('data-kpi="servicios"', false);

        $metricas = $respuesta->viewData('metricas');

        $this->assertSame(150.0, $metricas['ventas']['total']);
        $this->assertSame(1, $metricas['ventas']['cantidad']);
        $this->assertSame(1, $metricas['busquedas_sin_resultado']);
        $this->assertSame(1, $metricas['traspasos']);
        $this->assertArrayNotHasKey('ot_pendientes', $metricas);
    }

    public function test_tecnico_ve_servicios_y_busquedas_de_su_sucursal(): void
    {
        $this->servicio($this->principal, EstadoServicio::Recibido);
        $this->servicio($this->segunda, EstadoServicio::Recibido);
        $this->servicio($this->segunda, EstadoServicio::Listo);
        $this->busqueda($this->principal, ResultadoBusqueda::NoExiste);
        $this->busqueda($this->segunda, ResultadoBusqueda::NoExiste);

        $respuesta = $this->actingAs($this->tecnico)
            ->get(route('inicio'))
            ->assertOk()
            ->assertSee('data-kpi="servicios"', false)
            ->assertSee('data-kpi="busquedas"', false)
            ->assertDontSee('data-kpi="ventas"', false)
            ->assertDontSee('data-kpi="bajo-minimo"', false)
            ->assertDontSee('data-kpi="traspasos"', false);

        $metricas = $respuesta->viewData('metricas');

        $this->assertSame(1, $metricas['ot_pendientes']);
        $this->assertSame(1, $metricas['ot_listas']);
        $this->assertSame(1, $metricas['busquedas_sin_resultado']);
        $this->assertArrayNotHasKey('ventas', $metricas);
        $this->assertArrayNotHasKey('bajo_minimo', $metricas);
        $this->assertArrayNotHasKey('traspasos', $metricas);
    }

    public function test_gerente_puede_filtrar_por_sucursal(): void
    {
        $this->venta($this->principal, 100);
        $this->venta($this->segunda, 200);
        $this->servicio($this->principal, EstadoServicio::Recibido);
        $this->servicio($this->segunda, EstadoServicio::Listo);
        $this->traspaso($this->principal, $this->segunda);
        $this->busqueda($this->principal, ResultadoBusqueda::NoExiste);
        $this->busqueda($this->segunda, ResultadoBusqueda::NoExiste);

        $respuesta = $this->actingAs($this->gerente)
            ->get(route('inicio', ['sucursal_id' => $this->segunda->id]))
            ->assertOk()
            ->assertSee('Segunda sucursal');

        $metricas = $respuesta->viewData('metricas');

        $this->assertSame(200.0, $metricas['ventas']['total']);
        $this->assertSame(1, $metricas['ventas']['cantidad']);
        $this->assertSame(0, $metricas['ot_pendientes']);
        $this->assertSame(1, $metricas['ot_listas']);
        $this->assertSame(1, $metricas['traspasos']);
        $this->assertSame(1, $metricas['busquedas_sin_resultado']);
    }

    public function test_valida_la_sucursal_del_selector(): void
    {
        $this->actingAs($this->gerente)
            ->get(route('inicio', ['sucursal_id' => 999999]))
            ->assertSessionHasErrors('sucursal_id');
    }

    public function test_el_vendedor_no_puede_cambiar_de_sucursal_con_el_parametro(): void
    {
        $this->venta($this->principal, 150);
        $this->venta($this->segunda, 300);

        $respuesta = $this->actingAs($this->vendedor)
            ->get(route('inicio', ['sucursal_id' => $this->segunda->id]))
            ->assertOk();

        $this->assertSame(150.0, $respuesta->viewData('metricas')['ventas']['total']);
    }

    public function test_rol_sin_modulos_ve_el_fallback(): void
    {
        $role = Role::create([
            'nombre' => 'Sin módulos',
            'descripcion' => 'Rol de prueba',
            'permisos' => [],
            'es_sistema' => false,
        ]);

        $persona = Persona::create(['nombre' => 'Usuario sin módulos', 'ci' => '9999999']);

        $usuario = User::create([
            'username' => 'sinmodulos',
            'email' => 'sinmodulos@example.test',
            'password' => 'password',
            'persona_id' => $persona->id,
            'role_id' => $role->id,
            'sucursal_id' => $this->principal->id,
            'activo' => true,
        ]);

        $this->actingAs($usuario)
            ->get(route('inicio'))
            ->assertOk()
            ->assertSee('Su rol no tiene módulos asignados')
            ->assertDontSee('data-kpi="ventas"', false);
    }

    private function producto(string $codigo, int $minimo, ?int $principal = null, ?int $segunda = null): Producto
    {
        $producto = Producto::create([
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Producto '.$codigo,
            'marca' => 'Marca',
            'codigo' => $codigo,
            'condicion' => 'nuevo',
            'precio_compra' => 10,
            'precio_venta' => 20,
            'stock_minimo' => $minimo,
            'activo' => true,
        ]);

        if ($principal !== null) {
            Inventario::create([
                'producto_id' => $producto->id,
                'sucursal_id' => $this->principal->id,
                'cantidad' => $principal,
            ]);
        }

        if ($segunda !== null) {
            Inventario::create([
                'producto_id' => $producto->id,
                'sucursal_id' => $this->segunda->id,
                'cantidad' => $segunda,
            ]);
        }

        return $producto;
    }

    private function venta(
        Sucursal $sucursal,
        float $total,
        string $tipo = Venta::TIPO_VENTA,
        ?Carbon $fecha = null,
        string $estado = Venta::ESTADO_ACTIVA,
    ): Venta {
        return Venta::create([
            'sucursal_id' => $sucursal->id,
            'cliente_id' => $this->cliente->id,
            'user_id' => $this->gerente->id,
            'tipo' => $tipo,
            'fecha' => $fecha ?? now(),
            'medio_pago' => 'Efectivo',
            'total' => $total,
            'estado' => $estado,
        ]);
    }

    private function servicio(Sucursal $sucursal, EstadoServicio $estado): Servicio
    {
        $venta = $this->venta($sucursal, 100, Venta::TIPO_SERVICIO);

        return Servicio::create([
            'venta_id' => $venta->id,
            'cliente_id' => $this->cliente->id,
            'sucursal_id' => $sucursal->id,
            'tecnico_id' => $this->tecnico->id,
            'numero' => 'OT-'.str_pad((string) (((int) Servicio::max('id')) + 1), 6, '0', STR_PAD_LEFT),
            'equipo' => 'Laptop',
            'estado' => $estado->value,
            'fecha_recepcion' => today()->toDateString(),
            'prioridad' => 'normal',
            'mano_de_obra' => 100,
        ]);
    }

    private function traspaso(Sucursal $origen, Sucursal $destino, ?Carbon $fecha = null): Traspaso
    {
        return Traspaso::create([
            'sucursal_origen_id' => $origen->id,
            'sucursal_destino_id' => $destino->id,
            'user_id' => $this->gerente->id,
            'fecha' => ($fecha ?? today())->toDateString(),
        ]);
    }

    private function busqueda(
        Sucursal $sucursal,
        ResultadoBusqueda $resultado,
        ?Carbon $fecha = null,
    ): void {
        $fecha = $fecha ?? now();

        DB::table('busquedas_producto')->insert([
            'termino' => 'consulta',
            'sucursal_id' => $sucursal->id,
            'user_id' => $this->gerente->id,
            'resultado' => $resultado->value,
            'producto_id' => null,
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);
    }
}
