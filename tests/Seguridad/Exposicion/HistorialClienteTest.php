<?php

namespace Tests\Seguridad\Exposicion;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreaEscenario;
use Tests\TestCase;

/**
 * Decisión de negocio: el historial del cliente es compartido entre sucursales.
 *
 * La ficha muestra todas las ventas del cliente, sin importar en qué sucursal
 * se registraron. No es una fuga: es el comportamiento esperado (H-09 cerrado).
 */
class HistorialClienteTest extends TestCase
{
    use CreaEscenario, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sembrar();
    }

    public function test_un_vendedor_ve_el_historial_completo_del_cliente(): void
    {
        $cliente = $this->crearCliente();
        $producto = $this->crearProducto();

        $ventaPrincipal = $this->crearVenta($producto, $this->sucursalPrincipal(), 1, $this->gerente(), $cliente);
        $ventaSegunda = $this->crearVenta($producto, $this->sucursalSegunda(), 1, $this->gerente(), $cliente);

        $respuesta = $this->actingAs($this->vendedor())->get(route('clientes.show', $cliente));

        $respuesta->assertOk();
        $respuesta->assertSee($ventaPrincipal->numero());
        $respuesta->assertSee($ventaSegunda->numero());
        $respuesta->assertSee('Sucursal principal');
        $respuesta->assertSee('Segunda sucursal');
    }

    public function test_el_gerente_ve_el_historial_de_todas_las_sucursales(): void
    {
        $cliente = $this->crearCliente();
        $producto = $this->crearProducto();

        $this->crearVenta($producto, $this->sucursalPrincipal(), 1, $this->gerente(), $cliente);
        $this->crearVenta($producto, $this->sucursalSegunda(), 1, $this->gerente(), $cliente);

        $respuesta = $this->actingAs($this->gerente())->get(route('clientes.show', $cliente));

        $respuesta->assertOk();
        $respuesta->assertViewHas('ventas', function ($ventas) {
            return $ventas->count() === 2
                && $ventas->pluck('sucursal_id')->unique()->count() === 2;
        });
    }

    public function test_la_ficha_limita_el_historial_a_las_ultimas_diez_ventas(): void
    {
        $cliente = $this->crearCliente();
        $producto = $this->crearProducto();

        for ($i = 0; $i < 12; $i++) {
            $this->crearVenta($producto, $this->sucursalPrincipal(), 1, $this->gerente(), $cliente);
        }

        $respuesta = $this->actingAs($this->vendedor())->get(route('clientes.show', $cliente));

        $respuesta->assertOk();
        $respuesta->assertViewHas('ventas', fn ($ventas) => $ventas->count() === 10);
    }
}
