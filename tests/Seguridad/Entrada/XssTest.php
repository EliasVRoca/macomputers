<?php

namespace Tests\Seguridad\Entrada;

use App\Models\Cambio;
use App\Models\Categoria;
use App\Services\ServicioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * OWASP Top 10:2025 · A05 Injection (XSS) / ASVS 5.0 · V1.
 *
 * La salida debe estar escapada incluso si un dato peligroso llegó a la BD por
 * una vía distinta al formulario (importación, seeder, integración futura).
 */
class XssTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_el_nombre_de_un_cliente_se_escapa_en_el_listado(): void
    {
        $this->crearCliente('<script>alert("xss")</script>');

        $respuesta = $this->actingAs($this->vendedor())->get(route('clientes.index'));

        $respuesta->assertOk();
        $respuesta->assertDontSee('<script>alert("xss")</script>', false);
        $respuesta->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false);
    }

    public function test_el_nombre_de_un_producto_se_escapa_en_el_listado(): void
    {
        $this->crearProducto(['nombre' => '<img src=x onerror=alert(1)>']);

        $respuesta = $this->actingAs($this->gerente())->get(route('productos.index'));

        $respuesta->assertOk();
        $respuesta->assertDontSee('<img src=x onerror=alert(1)>', false);
        $respuesta->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);
    }

    public function test_la_descripcion_de_una_categoria_se_escapa(): void
    {
        Categoria::create([
            'nombre' => 'Con descripción peligrosa',
            'descripcion' => '<script>alert(1)</script>',
        ]);

        $respuesta = $this->actingAs($this->gerente())->get(route('categorias.index'));

        $respuesta->assertOk();
        $respuesta->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_el_diagnostico_de_un_servicio_se_escapa_en_la_ficha(): void
    {
        $servicio = app(ServicioService::class)->crear([
            'modo_cliente' => 'existente',
            'cliente_id' => $this->crearCliente()->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'equipo' => 'Laptop',
            'diagnostico' => '<script>alert("ot")</script>',
            'mano_de_obra' => 100,
            'prioridad' => 'normal',
            'fecha_recepcion' => now()->toDateString(),
        ], $this->gerente());

        $respuesta = $this->actingAs($this->gerente())->get(route('servicios.show', $servicio));

        $respuesta->assertOk();
        $respuesta->assertDontSee('<script>alert("ot")</script>', false);
    }

    public function test_el_motivo_de_un_cambio_se_escapa(): void
    {
        $producto = $this->crearProducto();
        $venta = $this->crearVenta($producto, $this->sucursalPrincipal(), 1, $this->gerente());

        $cambio = Cambio::create([
            'venta_id' => $venta->id,
            'producto_id' => $producto->id,
            'sucursal_id' => $this->sucursalPrincipal()->id,
            'user_id' => $this->gerente()->id,
            'tipo' => Cambio::TIPO_DEVOLUCION,
            'motivo' => '<script>alert("cambio")</script>',
            'fecha' => now()->toDateString(),
        ]);

        $respuesta = $this->actingAs($this->gerente())->get(route('cambios.show', $cambio));

        $respuesta->assertOk();
        $respuesta->assertDontSee('<script>alert("cambio")</script>', false);
    }

    public function test_el_json_de_inventario_no_permite_cerrar_la_etiqueta_script(): void
    {
        $this->crearProducto(['nombre' => '</script><script>alert(1)</script>']);

        $respuesta = $this->actingAs($this->gerente())->get(route('inventario.index'));

        $respuesta->assertOk();
        $respuesta->assertDontSee('</script><script>alert(1)</script>', false);
    }
}
