<?php

namespace Tests\Seguridad\Autorizacion;

use App\Models\ProductoAtributo;
use App\Models\ProductoImagen;
use App\Models\Servicio;
use App\Models\ServicioRepuesto;
use App\Models\Sucursal;
use App\Models\Traspaso;
use App\Services\ServicioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * OWASP Top 10:2025 · A01 / WSTG v4.2 · 4.5.3 y 4.5.4.
 *
 * Un usuario de una sucursal no puede leer ni operar recursos de otra, ni
 * recursos anidados que no pertenecen al padre indicado en la URL.
 */
class IdorTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_un_vendedor_no_accede_a_una_venta_de_otra_sucursal(): void
    {
        $venta = $this->crearVenta(
            $this->crearProducto(),
            $this->sucursalSegunda(),
            1,
            $this->gerente(),
        );

        $this->actingAs($this->vendedor())
            ->get(route('ventas.show', $venta))
            ->assertForbidden();

        $this->actingAs($this->vendedor())
            ->patch(route('ventas.anular', $venta))
            ->assertForbidden();
    }

    public function test_un_vendedor_si_accede_a_su_propia_venta(): void
    {
        $venta = $this->crearVenta(
            $this->crearProducto(),
            $this->sucursalPrincipal(),
            1,
            $this->vendedor(),
        );

        $this->actingAs($this->vendedor())
            ->get(route('ventas.show', $venta))
            ->assertOk();
    }

    public function test_un_vendedor_no_accede_a_un_apartado_de_otra_sucursal(): void
    {
        $apartado = $this->crearApartado(
            $this->crearProducto(),
            $this->sucursalSegunda(),
            $this->crearCliente(),
            1,
            100,
        );

        $this->actingAs($this->vendedor())->get(route('apartados.show', $apartado))->assertForbidden();
        $this->actingAs($this->vendedor())->get(route('apartados.edit', $apartado))->assertForbidden();
        $this->actingAs($this->vendedor())->put(route('apartados.update', $apartado), ['abono' => 10])->assertForbidden();
        $this->actingAs($this->vendedor())->patch(route('apartados.cancelar', $apartado), [])->assertForbidden();
    }

    public function test_un_tecnico_no_accede_a_una_orden_de_otra_sucursal(): void
    {
        $servicio = $this->crearServicio($this->sucursalPrincipal());

        $this->actingAs($this->tecnico())
            ->get(route('servicios.show', $servicio))
            ->assertForbidden();
    }

    public function test_un_vendedor_no_consulta_el_inventario_de_otra_sucursal(): void
    {
        $this->actingAs($this->vendedor())
            ->get(route('inventario.sucursal', $this->sucursalSegunda()))
            ->assertForbidden();
    }

    public function test_un_vendedor_no_consulta_un_traspaso_ajeno(): void
    {
        $tercera = Sucursal::create([
            'nombre' => 'Tercera sucursal',
            'direccion' => 'Pasillo 9',
            'activa' => true,
        ]);

        $traspaso = Traspaso::create([
            'sucursal_origen_id' => $this->sucursalSegunda()->id,
            'sucursal_destino_id' => $tercera->id,
            'user_id' => $this->gerente()->id,
            'fecha' => now()->toDateString(),
        ]);

        $this->actingAs($this->vendedor())
            ->get(route('traspasos.show', $traspaso))
            ->assertForbidden();
    }

    public function test_no_se_puede_quitar_una_imagen_por_otro_producto(): void
    {
        $productoA = $this->crearProducto();
        $productoB = $this->crearProducto();

        $imagen = ProductoImagen::create([
            'producto_id' => $productoA->id,
            'ruta' => 'productos/foto.jpg',
            'es_principal' => true,
        ]);

        $this->actingAs($this->gerente())
            ->delete(route('productos.imagenes.destroy', [$productoB, $imagen]))
            ->assertNotFound();
    }

    public function test_no_se_puede_quitar_un_atributo_por_otro_producto(): void
    {
        $productoA = $this->crearProducto();
        $productoB = $this->crearProducto();

        $atributo = ProductoAtributo::create([
            'producto_id' => $productoA->id,
            'atributo' => 'RAM',
            'valor' => '8 GB',
        ]);

        $this->actingAs($this->gerente())
            ->delete(route('productos.atributos.destroy', [$productoB, $atributo]))
            ->assertNotFound();
    }

    public function test_no_se_puede_quitar_un_repuesto_por_otra_orden(): void
    {
        $servicioA = $this->crearServicio($this->sucursalPrincipal());
        $servicioB = $this->crearServicio($this->sucursalPrincipal());
        $producto = $this->crearProducto();

        $repuesto = ServicioRepuesto::create([
            'servicio_id' => $servicioA->id,
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 100,
            'subtotal' => 100,
        ]);

        $this->actingAs($this->gerente())
            ->delete(route('servicios.repuestos.destroy', [$servicioB, $repuesto]))
            ->assertNotFound();
    }

    private function crearServicio(Sucursal $sucursal): Servicio
    {
        return app(ServicioService::class)->crear([
            'modo_cliente' => 'existente',
            'cliente_id' => $this->crearCliente()->id,
            'sucursal_id' => $sucursal->id,
            'equipo' => 'Laptop de prueba',
            'diagnostico' => 'Revisión general',
            'mano_de_obra' => 100,
            'prioridad' => 'normal',
            'fecha_recepcion' => now()->toDateString(),
        ], $this->gerente());
    }
}
