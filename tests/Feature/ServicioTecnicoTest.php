<?php

namespace Tests\Feature;

use App\Enums\EstadoServicio;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Inventario;
use App\Models\Persona;
use App\Models\Producto;
use App\Models\Role;
use App\Models\Servicio;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Orden de trabajo del servicio técnico: transiciones, repuestos que consumen
 * inventario, costos, permisos e impresión (RF16 / CU14).
 */
class ServicioTecnicoTest extends TestCase
{
    use RefreshDatabase;

    private User $gerente;

    private User $vendedor;

    private User $tecnico;

    private Sucursal $principal;

    private Sucursal $segunda;

    private Cliente $cliente;

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

        $this->cliente = Cliente::create(['nombre' => 'María Rojas', 'telefono' => '70012345']);

        $this->producto = Producto::create([
            'categoria_id' => Categoria::create(['nombre' => 'Repuestos'])->id,
            'nombre' => 'Memoria RAM 8GB',
            'marca' => 'Kingston',
            'codigo' => 'RAM-8',
            'precio_compra' => 200,
            'precio_venta' => 350,
            'stock_minimo' => 1,
            'activo' => true,
        ]);

        app(StockService::class)->mover($this->producto->id, $this->principal->id, 10, 'compra', 'inicial', $this->gerente->id);
    }

    public function test_crear_orden_genera_venta_de_servicio_con_numero_y_historial(): void
    {
        $orden = $this->crearOrden();

        $this->assertMatchesRegularExpression('/^OT-\d{6}$/', $orden->numero);
        $this->assertSame('recibido', $orden->estado);
        $this->assertSame('servicio', $orden->venta->tipo);
        $this->assertSame(200.0, (float) $orden->venta->total);
        $this->assertSame(1, $orden->historial()->count());
        $this->assertSame(EstadoServicio::Recibido->value, $orden->historial()->first()->estado_nuevo);
    }

    public function test_las_transiciones_validas_avanzan_y_dejan_historial(): void
    {
        $orden = $this->crearOrden();

        $this->cambiarEstado($orden, EstadoServicio::EnProceso);
        $this->cambiarEstado($orden, EstadoServicio::Listo);
        $this->cambiarEstado($orden, EstadoServicio::Entregado);

        $orden->refresh();
        $this->assertSame('entregado', $orden->estado);
        $this->assertNotNull($orden->fecha_entrega);
        $this->assertSame('Efectivo', $orden->venta->medio_pago);
        $this->assertSame(4, $orden->historial()->count());
    }

    public function test_no_se_puede_saltar_de_recibido_a_entregado(): void
    {
        $orden = $this->crearOrden();

        $this->actingAs($this->gerente)
            ->patch(route('servicios.estado', $orden), [
                'estado' => EstadoServicio::Entregado->value,
                'medio_pago' => 'Efectivo',
            ])
            ->assertSessionHasErrors('estado');

        $this->assertSame('recibido', $orden->fresh()->estado);
    }

    public function test_no_se_marca_listo_sin_diagnostico(): void
    {
        $orden = $this->crearOrden(['diagnostico' => null]);

        $this->cambiarEstado($orden, EstadoServicio::EnProceso);

        $this->actingAs($this->gerente)
            ->patch(route('servicios.estado', $orden), ['estado' => EstadoServicio::Listo->value])
            ->assertSessionHasErrors('estado');

        $this->assertSame('en_proceso', $orden->fresh()->estado);
    }

    public function test_agregar_repuesto_consume_inventario_y_suma_al_total(): void
    {
        $orden = $this->crearOrden();

        $this->actingAs($this->gerente)->post(route('servicios.repuestos.store', $orden), [
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
        ])->assertRedirect(route('servicios.show', $orden));

        $this->assertSame(8, $this->stockEn($this->principal));
        $this->assertSame(200.0 + 700.0, (float) $orden->fresh()->venta->total);
        $this->assertSame(1, $orden->repuestos()->count());
    }

    public function test_agregar_repuesto_sin_stock_no_cambia_nada(): void
    {
        $orden = $this->crearOrden();

        $this->actingAs($this->gerente)->post(route('servicios.repuestos.store', $orden), [
            'producto_id' => $this->producto->id,
            'cantidad' => 99,
        ])->assertSessionHasErrors('stock');

        $this->assertSame(10, $this->stockEn($this->principal));
        $this->assertSame(0, $orden->repuestos()->count());
        $this->assertSame(200.0, (float) $orden->fresh()->venta->total);
    }

    public function test_quitar_repuesto_devuelve_inventario_y_recalcula(): void
    {
        $orden = $this->crearOrden();

        $this->actingAs($this->gerente)->post(route('servicios.repuestos.store', $orden), [
            'producto_id' => $this->producto->id, 'cantidad' => 2,
        ])->assertRedirect();

        $repuesto = $orden->repuestos()->firstOrFail();

        $this->actingAs($this->gerente)
            ->delete(route('servicios.repuestos.destroy', [$orden, $repuesto]))
            ->assertRedirect(route('servicios.show', $orden));

        $this->assertSame(10, $this->stockEn($this->principal));
        $this->assertSame(200.0, (float) $orden->fresh()->venta->total);
        $this->assertSame(0, $orden->repuestos()->count());
    }

    public function test_cancelar_la_orden_devuelve_los_repuestos_al_inventario(): void
    {
        $orden = $this->crearOrden();

        $this->actingAs($this->gerente)->post(route('servicios.repuestos.store', $orden), [
            'producto_id' => $this->producto->id, 'cantidad' => 3,
        ])->assertRedirect();

        $this->assertSame(7, $this->stockEn($this->principal));

        $this->cambiarEstado($orden, EstadoServicio::Cancelado);

        $this->assertSame(10, $this->stockEn($this->principal));
        $this->assertSame('cancelado', $orden->fresh()->estado);
    }

    public function test_una_orden_final_no_admite_repuestos(): void
    {
        $orden = $this->crearOrden();
        $this->cambiarEstado($orden, EstadoServicio::EnProceso);
        $this->cambiarEstado($orden, EstadoServicio::Listo);
        $this->cambiarEstado($orden, EstadoServicio::Entregado);

        $this->actingAs($this->gerente)->post(route('servicios.repuestos.store', $orden), [
            'producto_id' => $this->producto->id, 'cantidad' => 1,
        ])->assertSessionHasErrors('repuesto');

        $this->assertSame(10, $this->stockEn($this->principal));
    }

    public function test_la_impresion_de_la_orden_responde(): void
    {
        $orden = $this->crearOrden();

        $this->actingAs($this->gerente)
            ->get(route('servicios.imprimir', $orden))
            ->assertOk()
            ->assertSee($orden->numero);
    }

    public function test_permisos_por_sucursal_y_rol(): void
    {
        $orden = $this->crearOrden();

        // El técnico pertenece a la segunda sucursal: no ve la orden de la principal.
        $this->actingAs($this->tecnico)->get(route('servicios.show', $orden))->assertForbidden();

        // El vendedor no tiene el permiso del módulo de servicios.
        $this->actingAs($this->vendedor)->get(route('servicios.index'))->assertForbidden();
    }

    public function test_solo_el_gerente_edita_o_elimina_una_orden_cerrada(): void
    {
        // Orden cerrada (entregada) en la misma sucursal del técnico.
        $orden = $this->crearOrden(['sucursal_id' => $this->segunda->id]);
        $this->cambiarEstado($orden, EstadoServicio::EnProceso);
        $this->cambiarEstado($orden, EstadoServicio::Listo);
        $this->cambiarEstado($orden, EstadoServicio::Entregado);

        $datos = [
            'equipo' => 'Laptop de prueba', 'diagnostico' => 'Revisión general',
            'mano_de_obra' => 300, 'prioridad' => 'normal', 'fecha_recepcion' => now()->toDateString(),
        ];

        $this->actingAs($this->tecnico)->get(route('servicios.edit', $orden))->assertForbidden();
        $this->actingAs($this->tecnico)->put(route('servicios.update', $orden), $datos)->assertForbidden();
        $this->actingAs($this->tecnico)->delete(route('servicios.destroy', $orden))->assertForbidden();

        $this->assertSame(200.0, (float) $orden->fresh()->venta->total);

        // El gerente sí puede editarla y eliminarla.
        $this->actingAs($this->gerente)->put(route('servicios.update', $orden), $datos)
            ->assertRedirect(route('servicios.show', $orden));
        $this->assertSame(300.0, (float) $orden->fresh()->venta->total);

        $this->actingAs($this->gerente)->delete(route('servicios.destroy', $orden))
            ->assertRedirect(route('servicios.index'));
        $this->assertSoftDeleted('servicios', ['id' => $orden->id]);
        $this->assertDatabaseHas('ventas', ['id' => $orden->venta_id, 'estado' => 'anulada']);
    }

    public function test_un_rol_personalizado_con_permiso_de_servicios_no_gestiona_ordenes_cerradas(): void
    {
        $rol = Role::create([
            'nombre' => 'Jefe de taller',
            'descripcion' => 'Accede al servicio técnico.',
            'permisos' => ['servicios', 'disponibilidad'],
            'es_sistema' => false,
        ]);

        $persona = Persona::create(['nombre' => 'Jefe de taller', 'ci' => '9000009']);

        $jefe = User::create([
            'username' => 'jefe', 'email' => 'jefe@macomputers.test', 'password' => 'password',
            'persona_id' => $persona->id, 'role_id' => $rol->id,
            'sucursal_id' => $this->segunda->id, 'activo' => true,
        ]);

        $this->assertFalse($jefe->esGerente());
        $this->assertTrue($jefe->tienePermiso('servicios'));

        $orden = $this->crearOrden(['sucursal_id' => $this->segunda->id]);
        $this->cambiarEstado($orden, EstadoServicio::EnProceso);
        $this->cambiarEstado($orden, EstadoServicio::Listo);
        $this->cambiarEstado($orden, EstadoServicio::Entregado);

        $this->actingAs($jefe)->get(route('servicios.edit', $orden))->assertForbidden();
        $this->actingAs($jefe)->delete(route('servicios.destroy', $orden))->assertForbidden();
    }

    public function test_el_tecnico_si_puede_gestionar_una_orden_abierta(): void
    {
        $orden = $this->crearOrden(['sucursal_id' => $this->segunda->id]);

        $this->actingAs($this->tecnico)->get(route('servicios.edit', $orden))->assertOk();

        $this->actingAs($this->tecnico)->put(route('servicios.update', $orden), [
            'equipo' => 'Laptop de prueba', 'diagnostico' => 'Diagnóstico del técnico',
            'mano_de_obra' => 150, 'prioridad' => 'alta', 'fecha_recepcion' => now()->toDateString(),
        ])->assertRedirect(route('servicios.show', $orden));

        $this->assertSame('alta', $orden->fresh()->prioridad);
    }

    public function test_entregar_exige_medio_de_pago(): void
    {
        $orden = $this->crearOrden();
        $this->cambiarEstado($orden, EstadoServicio::EnProceso);
        $this->cambiarEstado($orden, EstadoServicio::Listo);

        $this->actingAs($this->gerente)
            ->patch(route('servicios.estado', $orden), ['estado' => EstadoServicio::Entregado->value])
            ->assertSessionHasErrors('medio_pago');

        $orden->refresh();

        $this->assertSame('listo', $orden->estado);
        $this->assertNull($orden->venta->medio_pago);
    }

    public function test_baja_logica_de_orden_abierta_devuelve_repuestos_y_anula_la_venta(): void
    {
        $orden = $this->crearOrden();

        $this->actingAs($this->gerente)->post(route('servicios.repuestos.store', $orden), [
            'producto_id' => $this->producto->id,
            'cantidad' => 3,
        ])->assertRedirect();

        $this->assertSame(7, $this->stockEn($this->principal));

        $this->actingAs($this->gerente)
            ->delete(route('servicios.destroy', $orden))
            ->assertRedirect(route('servicios.index'));

        $this->assertSoftDeleted('servicios', ['id' => $orden->id]);
        $this->assertDatabaseHas('ventas', ['id' => $orden->venta_id, 'estado' => 'anulada']);
        $this->assertSame(10, $this->stockEn($this->principal));
    }

    public function test_baja_logica_de_orden_cancelada_no_devuelve_repuestos_dos_veces(): void
    {
        $orden = $this->crearOrden();

        $this->actingAs($this->gerente)->post(route('servicios.repuestos.store', $orden), [
            'producto_id' => $this->producto->id,
            'cantidad' => 3,
        ])->assertRedirect();

        $this->cambiarEstado($orden, EstadoServicio::Cancelado);
        $this->assertSame(10, $this->stockEn($this->principal));

        $this->actingAs($this->gerente)
            ->delete(route('servicios.destroy', $orden))
            ->assertRedirect(route('servicios.index'));

        $this->assertSoftDeleted('servicios', ['id' => $orden->id]);
        $this->assertSame(10, $this->stockEn($this->principal));
    }

    public function test_baja_logica_de_orden_entregada_conserva_el_consumo_de_repuestos(): void
    {
        $orden = $this->crearOrden();

        $this->actingAs($this->gerente)->post(route('servicios.repuestos.store', $orden), [
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
        ])->assertRedirect();

        $this->cambiarEstado($orden, EstadoServicio::EnProceso);
        $this->cambiarEstado($orden, EstadoServicio::Listo);
        $this->cambiarEstado($orden, EstadoServicio::Entregado);

        $this->assertSame(8, $this->stockEn($this->principal));

        $this->actingAs($this->gerente)
            ->delete(route('servicios.destroy', $orden))
            ->assertRedirect(route('servicios.index'));

        $this->assertSoftDeleted('servicios', ['id' => $orden->id]);
        $this->assertDatabaseHas('ventas', ['id' => $orden->venta_id, 'estado' => 'anulada']);
        $this->assertSame(8, $this->stockEn($this->principal));
    }

    public function test_la_orden_dada_de_baja_no_se_muestra(): void
    {
        $orden = $this->crearOrden();

        $this->actingAs($this->gerente)->delete(route('servicios.destroy', $orden));

        $this->actingAs($this->gerente)->get(route('servicios.index'))
            ->assertOk()
            ->assertDontSee($orden->numero);

        $this->actingAs($this->gerente)->get(route('servicios.show', $orden))->assertNotFound();
    }

    public function test_el_gerente_reasigna_cliente_sucursal_y_tecnico(): void
    {
        $otroCliente = Cliente::create(['nombre' => 'Otro Cliente', 'telefono' => '70099999']);
        $orden = $this->crearOrden();
        $ventaId = $orden->venta_id;

        $this->actingAs($this->gerente)->put(route('servicios.update', $orden), [
            'equipo' => 'Laptop de prueba',
            'diagnostico' => 'Revisión general',
            'mano_de_obra' => 200,
            'prioridad' => 'normal',
            'fecha_recepcion' => now()->toDateString(),
            'cliente_id' => $otroCliente->id,
            'sucursal_id' => $this->segunda->id,
            'tecnico_id' => $this->tecnico->id,
        ])->assertRedirect(route('servicios.show', $orden));

        $orden->refresh();

        $this->assertSame($otroCliente->id, $orden->cliente_id);
        $this->assertSame($this->segunda->id, $orden->sucursal_id);
        $this->assertSame($this->tecnico->id, $orden->tecnico_id);
        $this->assertDatabaseHas('ventas', [
            'id' => $ventaId,
            'cliente_id' => $otroCliente->id,
            'sucursal_id' => $this->segunda->id,
        ]);
    }

    public function test_no_cambia_la_sucursal_de_una_orden_con_repuestos(): void
    {
        $orden = $this->crearOrden();

        $this->actingAs($this->gerente)->post(route('servicios.repuestos.store', $orden), [
            'producto_id' => $this->producto->id,
            'cantidad' => 1,
        ])->assertRedirect();

        $this->actingAs($this->gerente)->put(route('servicios.update', $orden), [
            'equipo' => 'Laptop de prueba',
            'diagnostico' => 'Revisión general',
            'mano_de_obra' => 200,
            'prioridad' => 'normal',
            'fecha_recepcion' => now()->toDateString(),
            'sucursal_id' => $this->segunda->id,
        ])->assertSessionHasErrors('sucursal_id');

        $this->assertSame($this->principal->id, $orden->fresh()->sucursal_id);
    }

    public function test_el_tecnico_cambia_cliente_pero_no_sucursal_ni_tecnico(): void
    {
        $otroCliente = Cliente::create(['nombre' => 'Otro Cliente', 'telefono' => '70099999']);

        $orden = $this->crearOrden([
            'sucursal_id' => $this->segunda->id,
            'tecnico_id' => $this->tecnico->id,
        ]);

        $this->actingAs($this->tecnico)->put(route('servicios.update', $orden), [
            'equipo' => 'Laptop de prueba',
            'diagnostico' => 'Revisión general',
            'mano_de_obra' => 200,
            'prioridad' => 'normal',
            'fecha_recepcion' => now()->toDateString(),
            'cliente_id' => $otroCliente->id,
            'sucursal_id' => $this->principal->id,
            'tecnico_id' => $this->gerente->id,
        ])->assertRedirect(route('servicios.show', $orden));

        $orden->refresh();

        $this->assertSame($otroCliente->id, $orden->cliente_id);
        $this->assertSame($this->segunda->id, $orden->sucursal_id);
        $this->assertSame($this->tecnico->id, $orden->tecnico_id);
    }

    public function test_no_reasigna_una_orden_cerrada(): void
    {
        $otroCliente = Cliente::create(['nombre' => 'Otro Cliente', 'telefono' => '70099999']);
        $orden = $this->crearOrden();

        $this->cambiarEstado($orden, EstadoServicio::EnProceso);
        $this->cambiarEstado($orden, EstadoServicio::Listo);
        $this->cambiarEstado($orden, EstadoServicio::Entregado);

        $this->actingAs($this->gerente)->put(route('servicios.update', $orden), [
            'equipo' => 'Laptop de prueba',
            'diagnostico' => 'Revisión general',
            'mano_de_obra' => 200,
            'prioridad' => 'normal',
            'fecha_recepcion' => now()->toDateString(),
            'cliente_id' => $otroCliente->id,
        ])->assertSessionHasErrors('cliente_id');

        $this->assertSame($this->cliente->id, $orden->fresh()->cliente_id);
    }

    public function test_el_export_respeta_filtros_permisos_y_bajas(): void
    {
        $orden = $this->crearOrden();
        $this->cambiarEstado($orden, EstadoServicio::EnProceso);
        $this->cambiarEstado($orden, EstadoServicio::Listo);

        $respuesta = $this->actingAs($this->gerente)
            ->get(route('servicios.export', ['estado' => EstadoServicio::Listo->value]));

        $respuesta->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertStringContainsString($orden->numero, $respuesta->streamedContent());

        $this->actingAs($this->gerente)->delete(route('servicios.destroy', $orden));

        $sinBaja = $this->actingAs($this->gerente)->get(route('servicios.export'));
        $sinBaja->assertOk();
        $this->assertStringNotContainsString($orden->numero, $sinBaja->streamedContent());

        $this->actingAs($this->vendedor)->get(route('servicios.export'))->assertForbidden();
    }

    private function crearOrden(array $extra = []): Servicio
    {
        $this->actingAs($this->gerente)->post(route('servicios.store'), array_merge([
            'modo_cliente' => 'existente',
            'cliente_id' => $this->cliente->id,
            'sucursal_id' => $this->principal->id,
            'equipo' => 'Laptop de prueba',
            'diagnostico' => 'Revisión general',
            'mano_de_obra' => 200,
            'prioridad' => 'normal',
            'fecha_recepcion' => now()->toDateString(),
        ], $extra))->assertRedirect();

        return Servicio::latest('id')->firstOrFail();
    }

    private function cambiarEstado(Servicio $orden, EstadoServicio $estado): void
    {
        $datos = ['estado' => $estado->value];

        if ($estado === EstadoServicio::Entregado) {
            $datos['medio_pago'] = 'Efectivo';
        }

        $this->actingAs($this->gerente)
            ->patch(route('servicios.estado', $orden), $datos)
            ->assertRedirect(route('servicios.show', $orden));
    }

    private function stockEn(Sucursal $sucursal): int
    {
        return (int) (Inventario::where('producto_id', $this->producto->id)
            ->where('sucursal_id', $sucursal->id)
            ->value('cantidad') ?? 0);
    }
}
