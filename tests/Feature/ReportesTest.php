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
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaItem;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportesTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

    private User $tecnico;

    private Sucursal $principal;

    private Sucursal $segunda;

    private Categoria $categoria;

    private Cliente $cliente;

    private int $contador = 0;

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

    public function test_gerente_ve_todas_las_secciones_del_reporte(): void
    {
        $this->ventaConItem($this->principal, today()->subDay());
        $this->servicio($this->principal, EstadoServicio::Recibido);
        $this->busqueda($this->principal, ResultadoBusqueda::NoExiste, today());

        $respuesta = $this->actingAs($this->gerente)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('Ventas e ingresos')
            ->assertSee('Inventario')
            ->assertSee('Servicio técnico')
            ->assertSee('Búsquedas de clientes')
            ->assertSee('Ingresos del período');

        $datos = $respuesta->viewData('datos');

        $this->assertArrayHasKey('resumen', $datos);
        $this->assertArrayHasKey('ventas', $datos);
        $this->assertArrayHasKey('inventario', $datos);
        $this->assertArrayHasKey('servicios', $datos);
        $this->assertArrayHasKey('busquedas', $datos);
    }

    public function test_vendedor_no_ve_servicio_tecnico_y_sus_datos_son_de_su_sucursal(): void
    {
        $this->ventaConItem($this->principal, today());
        $this->ventaConItem($this->segunda, today());
        $this->servicio($this->segunda, EstadoServicio::Recibido);

        $respuesta = $this->actingAs($this->vendedor)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('Ventas e ingresos')
            ->assertDontSee('Servicio técnico');

        $datos = $respuesta->viewData('datos');

        $this->assertArrayNotHasKey('servicios', $datos);
        $this->assertSame(1, $datos['resumen']['ventas']);
        $this->assertSame(30.0, $datos['resumen']['utilidad']);
    }

    public function test_tecnico_ve_servicios_y_busquedas_sin_modulos_de_ventas(): void
    {
        $this->servicio($this->principal, EstadoServicio::Recibido);
        $this->busqueda($this->principal, ResultadoBusqueda::SinStock, today());

        $respuesta = $this->actingAs($this->tecnico)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('Servicio técnico')
            ->assertSee('Búsquedas de clientes')
            ->assertDontSee('Ventas e ingresos');

        $datos = $respuesta->viewData('datos');

        $this->assertArrayNotHasKey('resumen', $datos);
        $this->assertArrayNotHasKey('ventas', $datos);
        $this->assertArrayNotHasKey('inventario', $datos);
    }

    public function test_resumen_calcula_ingresos_utilidad_y_ticket(): void
    {
        // 3 unidades: venta 60, costo 30, utilidad 30.
        $this->ventaConItem($this->principal, today());
        // 1 unidad con costo histórico distinto del actual.
        $this->ventaConItem($this->principal, today(), 1, 5.0);

        $respuesta = $this->actingAs($this->gerente)
            ->get(route('reportes.index'))
            ->assertOk();

        $resumen = $respuesta->viewData('datos')['resumen'];

        $this->assertSame(80.0, $resumen['ingresos']);
        $this->assertSame(2, $resumen['ventas']);
        $this->assertSame(4, $resumen['unidades']);
        $this->assertSame(45.0, $resumen['utilidad']);
        $this->assertSame(56.3, $resumen['margen']);
        $this->assertSame(40.0, $resumen['ticket_promedio']);
    }

    public function test_item_sin_costo_historico_usa_el_precio_de_compra_actual(): void
    {
        $venta = $this->venta($this->principal, 20.0);

        VentaItem::create([
            'venta_id' => $venta->id,
            'producto_id' => $this->producto()->id,
            'descripcion' => 'Producto antiguo',
            'cantidad' => 1,
            'precio_unitario' => 20,
            'costo_unitario' => null,
            'subtotal' => 20,
        ]);

        $respuesta = $this->actingAs($this->gerente)
            ->get(route('reportes.index'))
            ->assertOk();

        $this->assertSame(10.0, $respuesta->viewData('datos')['resumen']['utilidad']);
    }

    public function test_filtro_de_rango_y_agrupacion_semanal(): void
    {
        $this->ventaConItem($this->principal, today());
        $this->ventaConItem($this->principal, today()->subDays(60));

        $respuesta = $this->actingAs($this->gerente)
            ->get(route('reportes.index', [
                'desde' => today()->subDays(30)->toDateString(),
                'hasta' => today()->toDateString(),
                'agrupacion' => 'semana',
            ]))
            ->assertOk();

        $resumen = $respuesta->viewData('datos')['resumen'];
        $etiquetas = $respuesta->viewData('datos')['ventas']['tendencia']['etiquetas'];

        $this->assertSame(1, $resumen['ventas']);
        $this->assertStringStartsWith('Sem. ', $etiquetas[0]);
    }

    public function test_gerente_filtra_por_sucursal(): void
    {
        $this->ventaConItem($this->principal, today());
        $this->ventaConItem($this->segunda, today());

        $respuesta = $this->actingAs($this->gerente)
            ->get(route('reportes.index', ['sucursal_id' => $this->segunda->id]))
            ->assertOk();

        $this->assertSame(1, $respuesta->viewData('datos')['resumen']['ventas']);
    }

    public function test_valida_fechas_y_agrupacion(): void
    {
        $this->actingAs($this->gerente)
            ->get(route('reportes.index', ['desde' => '2026-10-20', 'hasta' => '2026-10-01']))
            ->assertSessionHasErrors('hasta');

        $this->actingAs($this->gerente)
            ->get(route('reportes.index', ['agrupacion' => 'anual']))
            ->assertSessionHasErrors('agrupacion');

        $this->actingAs($this->gerente)
            ->get(route('reportes.index', ['sucursal_id' => 999999]))
            ->assertSessionHasErrors('sucursal_id');
    }

    public function test_el_vendedor_no_puede_elegir_sucursal_desde_el_parametro(): void
    {
        $this->ventaConItem($this->principal, today());
        $this->ventaConItem($this->segunda, today());

        $respuesta = $this->actingAs($this->vendedor)
            ->get(route('reportes.index', ['sucursal_id' => $this->segunda->id]))
            ->assertOk();

        $this->assertSame(1, $respuesta->viewData('datos')['resumen']['ventas']);
    }

    public function test_el_inicio_incluye_los_mini_graficos(): void
    {
        $this->ventaConItem($this->principal, today());
        $this->busqueda($this->principal, ResultadoBusqueda::NoExiste, today());

        $respuesta = $this->actingAs($this->gerente)
            ->get(route('inicio'))
            ->assertOk()
            ->assertSee('Ventas · últimos 14 días')
            ->assertSee('Ver todos los reportes');

        $graficos = $respuesta->viewData('graficos');

        $this->assertArrayHasKey('ventas', $graficos);
        $this->assertArrayHasKey('servicios', $graficos);
        $this->assertArrayHasKey('busquedas', $graficos);
    }

    public function test_rol_sin_modulos_ve_el_estado_vacio_del_reporte(): void
    {
        $role = Role::create([
            'nombre' => 'Sin módulos',
            'descripcion' => 'Rol de prueba',
            'permisos' => [],
            'es_sistema' => false,
        ]);

        $persona = Persona::create(['nombre' => 'Usuario sin módulos', 'ci' => '9999998']);

        $usuario = User::create([
            'username' => 'sinmodulos2',
            'email' => 'sinmodulos2@example.test',
            'password' => 'password',
            'persona_id' => $persona->id,
            'role_id' => $role->id,
            'sucursal_id' => $this->principal->id,
            'activo' => true,
        ]);

        $this->actingAs($usuario)
            ->get(route('reportes.index'))
            ->assertOk()
            ->assertSee('Sin reportes para tu rol');
    }

    private function producto(): Producto
    {
        return Producto::create([
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Producto de reporte',
            'marca' => 'Marca',
            'codigo' => 'REP-'.(++$this->contador),
            'condicion' => 'nuevo',
            'precio_compra' => 10,
            'precio_venta' => 20,
            'stock_minimo' => 1,
            'activo' => true,
        ]);
    }

    private function venta(Sucursal $sucursal, float $total, ?Carbon $fecha = null): Venta
    {
        return Venta::create([
            'sucursal_id' => $sucursal->id,
            'cliente_id' => $this->cliente->id,
            'user_id' => $this->gerente->id,
            'tipo' => Venta::TIPO_VENTA,
            'fecha' => $fecha ?? today(),
            'medio_pago' => 'Efectivo',
            'total' => $total,
            'estado' => Venta::ESTADO_ACTIVA,
        ]);
    }

    private function ventaConItem(Sucursal $sucursal, Carbon $fecha, int $cantidad = 3, ?float $costoHistorico = null): Venta
    {
        $producto = $this->producto();
        $subtotal = round($cantidad * (float) $producto->precio_venta, 2);

        $venta = $this->venta($sucursal, $subtotal, $fecha);

        VentaItem::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'descripcion' => $producto->nombre,
            'cantidad' => $cantidad,
            'precio_unitario' => $producto->precio_venta,
            'costo_unitario' => $costoHistorico ?? $producto->precio_compra,
            'subtotal' => $subtotal,
        ]);

        Inventario::create([
            'producto_id' => $producto->id,
            'sucursal_id' => $sucursal->id,
            'cantidad' => 10,
        ]);

        return $venta;
    }

    private function servicio(Sucursal $sucursal, EstadoServicio $estado): Servicio
    {
        $venta = $this->venta($sucursal, 100.0);

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

    private function busqueda(Sucursal $sucursal, ResultadoBusqueda $resultado, Carbon $fecha): void
    {
        DB::table('busquedas_producto')->insert([
            'termino' => 'teclado inalámbrico',
            'sucursal_id' => $sucursal->id,
            'user_id' => $this->gerente->id,
            'resultado' => $resultado->value,
            'producto_id' => null,
            'created_at' => $fecha,
            'updated_at' => $fecha,
        ]);
    }
}
