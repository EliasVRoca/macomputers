<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Persona;
use App\Models\Producto;
use App\Models\Role;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Modo IA del buscador (RF20 · CU19): preguntas en lenguaje natural al
 * catálogo a través de OpenCode Go, con clasificación de categorías,
 * validación, filtros y errores.
 */
class BuscadorIaTest extends TestCase
{
    use RefreshDatabase;

    private User $vendedor;

    private Sucursal $principal;

    private Categoria $categoria;

    private Producto $teclado;

    private Producto $mouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->vendedor = User::where('username', 'vendedor')->firstOrFail();
        $this->principal = Sucursal::where('nombre', 'Sucursal principal')->firstOrFail();
        $this->categoria = Categoria::create(['nombre' => 'Periféricos']);

        $this->teclado = $this->producto([
            'nombre' => 'Teclado mecánico 60%',
            'marca' => 'Redragon',
            'modelo' => 'K617',
            'codigo' => 'TEC-60',
        ]);
        $this->teclado->atributos()->create(['atributo' => 'tipo', 'valor' => 'mecanico']);

        $this->mouse = $this->producto([
            'nombre' => 'Mouse gamer',
            'marca' => 'Logitech',
            'modelo' => 'G203',
            'codigo' => 'MOU-1',
        ]);

        app(StockService::class)->mover($this->teclado->id, $this->principal->id, 5, 'entrada', 'inicial', $this->vendedor->id);
    }

    public function test_responde_con_productos_y_alternativas_del_catalogo(): void
    {
        $this->fakeIa($this->clasificacion(), [
            'hay' => true,
            'respuesta' => 'Sí, tenemos el teclado mecánico Redragon K617 a Bs 150, con 5 unidades en tu sucursal.',
            'productos' => [$this->teclado->id],
            'alternativas' => [$this->mouse->id],
        ]);

        $respuesta = $this->actingAs($this->vendedor)
            ->withHeader('X-Buscador-Sesion', 'sesion-de-prueba-123')
            ->getJson(route('buscador.ia', ['termino' => 'estoy buscando un teclado para jugar']));

        $respuesta->assertOk()
            ->assertJsonPath('termino', 'estoy buscando un teclado para jugar')
            ->assertJsonPath('respuesta', 'Sí, tenemos el teclado mecánico Redragon K617 a Bs 150, con 5 unidades en tu sucursal.')
            ->assertJsonPath('hay', true)
            ->assertJsonPath('productos.0.codigo', 'TEC-60')
            ->assertJsonPath('productos.0.venta_url', route('ventas.create', ['producto' => $this->teclado->id]))
            ->assertJsonPath('alternativas.0.codigo', 'MOU-1')
            ->assertJsonCount(2, 'sucursales');

        Http::assertSent(function (Request $request): bool {
            $datos = $request->data();
            $contenido = (string) ($datos['messages'][1]['content'] ?? '');

            return $request->url() === 'https://opencode.ai/inference/go/openai/v1/chat/completions'
                && ($datos['model'] ?? null) === 'deepseek-v4.1-flash'
                && str_contains($contenido, 'TEC-60')
                && str_contains($contenido, '"precio_bs"')
                && ($request->headers()['X-OpenCode-Session'][0] ?? null) === 'sesion-de-prueba-123';
        });
    }

    public function test_el_prompt_del_sistema_refuerza_el_rol_de_asistente_interno(): void
    {
        $this->fakeIa($this->clasificacion(), [
            'hay' => false,
            'respuesta' => 'No encontramos nada parecido.',
            'productos' => [],
            'alternativas' => [],
        ]);

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.ia', ['termino' => 'tienen algo para un dron acuático']))
            ->assertOk()
            ->assertJsonPath('hay', false);

        Http::assertSent(function (Request $request): bool {
            $sistema = (string) ($request->data()['messages'][0]['content'] ?? '');

            return str_contains($sistema, 'asistente interno de ventas')
                && str_contains($sistema, 'personal de la tienda')
                && str_contains($sistema, 'NO en el cliente final')
                && str_contains($sistema, 'complementos para armar una venta más completa');
        });
    }

    public function test_descarta_ids_invalidos_y_productos_que_no_vio_el_modelo(): void
    {
        $inactivo = $this->producto(['codigo' => 'INACTIVO', 'activo' => false]);

        $this->fakeIa($this->clasificacion(), [
            'hay' => true,
            'respuesta' => 'Encontré varios.',
            // IDs inválidos, un objeto completo (el modelo a veces devuelve
            // el producto en vez del ID) y un producto inactivo: solo debe
            // quedar el teclado.
            'productos' => [999999, 'abc', ['id' => $this->teclado->id, 'nombre' => 'X'], $inactivo->id],
            'alternativas' => [999998, ['id' => $this->teclado->id]],
        ]);

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.ia', ['termino' => 'qué teclado tienen']))
            ->assertOk()
            ->assertJsonPath('hay', true)
            ->assertJsonCount(1, 'productos')
            ->assertJsonPath('productos.0.codigo', 'TEC-60')
            ->assertJsonCount(0, 'alternativas');
    }

    public function test_el_modelo_no_puede_recomendar_fuera_del_catalogo_filtrado(): void
    {
        $this->fakeIa($this->clasificacion(), [
            'hay' => true,
            'respuesta' => 'Tenemos teclados.',
            'productos' => [$this->teclado->id],
            'alternativas' => [],
        ]);

        $otraCategoria = Categoria::create(['nombre' => 'Accesorios']);

        // El teclado pertenece a otra categoría: no va en el catálogo enviado
        // y, por lo tanto, tampoco puede volver como resultado.
        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.ia', ['termino' => 'tienen teclados', 'categoria_id' => $otraCategoria->id]))
            ->assertOk()
            ->assertJsonPath('hay', false)
            ->assertJsonCount(0, 'productos');

        Http::assertSent(function (Request $request): bool {
            $contenido = (string) ($request->data()['messages'][1]['content'] ?? '');

            return ! str_contains($contenido, 'TEC-60');
        });
    }

    public function test_aplica_el_filtro_de_solo_stock_al_catalogo_enviado(): void
    {
        $this->fakeIa($this->clasificacion(), [
            'hay' => true,
            'respuesta' => 'Tenemos teclados con stock.',
            'productos' => [$this->teclado->id],
            'alternativas' => [],
        ]);

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.ia', ['termino' => 'tienen teclados', 'solo_stock' => 1]))
            ->assertOk()
            ->assertJsonPath('hay', true);

        Http::assertSent(function (Request $request): bool {
            $contenido = (string) ($request->data()['messages'][1]['content'] ?? '');

            return str_contains($contenido, 'TEC-60') && ! str_contains($contenido, 'MOU-1');
        });
    }

    public function test_la_clasificacion_envia_solo_categorias_y_limita_el_catalogo(): void
    {
        $accesorios = Categoria::create(['nombre' => 'Accesorios', 'descripcion' => 'Cables, hubs y adaptadores.']);

        $hub = $this->producto([
            'nombre' => 'Hub USB',
            'codigo' => 'HUB-1',
            'categoria_id' => $accesorios->id,
        ]);

        $this->fakeIa(['categorias' => [$accesorios->id], 'respuesta' => null], [
            'hay' => true,
            'respuesta' => 'Para eso tenemos un hub USB.',
            'productos' => [$hub->id],
            'alternativas' => [],
        ]);

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.ia', ['termino' => 'con qué conecto dos monitores a la laptop']))
            ->assertOk()
            ->assertJsonPath('hay', true)
            ->assertJsonPath('productos.0.codigo', 'HUB-1');

        // Fase 1: solo categorías, sin productos.
        Http::assertSent(function (Request $request): bool {
            $contenido = (string) ($request->data()['messages'][1]['content'] ?? '');

            return str_contains($contenido, 'CATEGORÍAS:')
                && str_contains($contenido, '"nombre":"Periféricos"')
                && str_contains($contenido, '"nombre":"Accesorios"')
                && ! str_contains($contenido, 'TEC-60');
        });

        // Fase 2: catálogo limitado a la categoría elegida.
        Http::assertSent(function (Request $request): bool {
            $contenido = (string) ($request->data()['messages'][1]['content'] ?? '');

            return str_contains($contenido, 'CATÁLOGO:')
                && str_contains($contenido, 'HUB-1')
                && ! str_contains($contenido, 'TEC-60')
                && ! str_contains($contenido, 'MOU-1');
        });
    }

    public function test_omite_la_consulta_cuando_ninguna_categoria_coincide(): void
    {
        $this->fakeIa([
            'categorias' => [],
            'respuesta' => 'No trabajamos con ese rubro; no vas a encontrar nada de eso en la tienda.',
        ], [
            'hay' => true,
            'respuesta' => 'Nunca debería llegar acá.',
            'productos' => [$this->teclado->id],
            'alternativas' => [],
        ]);

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.ia', ['termino' => 'venden herramientas de jardinería']))
            ->assertOk()
            ->assertJsonPath('hay', false)
            ->assertJsonPath('respuesta', 'No trabajamos con ese rubro; no vas a encontrar nada de eso en la tienda.')
            ->assertJsonCount(0, 'productos')
            ->assertJsonCount(0, 'alternativas');

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request): bool {
            $contenido = (string) ($request->data()['messages'][1]['content'] ?? '');

            return str_contains($contenido, 'CATEGORÍAS:');
        });
    }

    public function test_hace_fallback_al_catalogo_completo_si_la_clasificacion_falla(): void
    {
        $accesorios = Categoria::create(['nombre' => 'Accesorios']);

        $this->producto([
            'nombre' => 'Hub USB',
            'codigo' => 'HUB-1',
            'categoria_id' => $accesorios->id,
        ]);

        Http::fake(function (Request $request) {
            $contenido = (string) ($request->data()['messages'][1]['content'] ?? '');

            return str_contains($contenido, 'CATEGORÍAS:')
                ? Http::response([], 500)
                : Http::response([
                    'choices' => [
                        ['message' => ['content' => json_encode([
                            'hay' => true,
                            'respuesta' => 'Tenemos el teclado.',
                            'productos' => [$this->teclado->id],
                            'alternativas' => [],
                        ])]],
                    ],
                ]);
        });

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.ia', ['termino' => 'tienen teclados']))
            ->assertOk()
            ->assertJsonPath('hay', true)
            ->assertJsonPath('productos.0.codigo', 'TEC-60');

        // La fase 2 recibió el catálogo completo (ambas categorías).
        Http::assertSentCount(2);
        Http::assertSent(function (Request $request): bool {
            $contenido = (string) ($request->data()['messages'][1]['content'] ?? '');

            return str_contains($contenido, 'CATÁLOGO:')
                && str_contains($contenido, 'TEC-60')
                && str_contains($contenido, 'HUB-1');
        });
    }

    public function test_descarta_ids_de_categoria_invalidos_y_hace_fallback(): void
    {
        $this->fakeIa(['categorias' => [999999], 'respuesta' => null], [
            'hay' => true,
            'respuesta' => 'Tenemos el teclado.',
            'productos' => [$this->teclado->id],
            'alternativas' => [],
        ]);

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.ia', ['termino' => 'tienen teclados']))
            ->assertOk()
            ->assertJsonPath('hay', true)
            ->assertJsonPath('productos.0.codigo', 'TEC-60');

        Http::assertSentCount(2);
    }

    public function test_devuelve_502_cuando_la_api_falla(): void
    {
        Http::fake(['opencode.ai/*' => Http::response([], 500)]);

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.ia', ['termino' => 'tienen teclados']))
            ->assertStatus(502)
            ->assertJsonPath('message', 'La IA no respondió correctamente. Intentá de nuevo o usá el buscador clásico.');
    }

    public function test_devuelve_502_cuando_la_api_no_responde_a_tiempo(): void
    {
        Http::fake(['opencode.ai/*' => fn () => throw new ConnectionException('timeout')]);

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.ia', ['termino' => 'tienen teclados']))
            ->assertStatus(502)
            ->assertJsonPath('message', 'No se pudo consultar la IA. Intentá de nuevo o usá el buscador clásico.');
    }

    public function test_devuelve_502_cuando_el_modelo_no_entrega_json(): void
    {
        Http::fake([
            'opencode.ai/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'No tengo idea de JSON.']],
                ],
            ]),
        ]);

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.ia', ['termino' => 'tienen teclados']))
            ->assertStatus(502)
            ->assertJsonPath('message', 'La IA devolvió una respuesta que no se puede interpretar. Intentá de nuevo.');
    }

    public function test_devuelve_503_sin_clave_configurada(): void
    {
        config(['buscador_ia.clave' => null]);
        Http::fake();

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.ia', ['termino' => 'tienen teclados']))
            ->assertStatus(503)
            ->assertJsonPath('message', 'El Modo IA no está configurado en el servidor. Avisá a un administrador.');

        Http::assertNothingSent();
    }

    public function test_valida_el_termino_minimo_y_maximo(): void
    {
        Http::fake();

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.ia', ['termino' => 'abc']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('termino');

        $this->actingAs($this->vendedor)
            ->getJson(route('buscador.ia', ['termino' => str_repeat('a', 301)]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('termino');

        Http::assertNothingSent();
    }

    public function test_un_rol_sin_permiso_no_accede_al_modo_ia(): void
    {
        $rol = Role::create([
            'nombre' => 'Depósito',
            'descripcion' => 'Sin buscador.',
            'permisos' => ['disponibilidad'],
            'es_sistema' => false,
        ]);

        $persona = Persona::create(['nombre' => 'Depósito', 'ci' => '9000010']);

        $usuario = User::create([
            'username' => 'deposito',
            'email' => 'deposito@macomputers.test',
            'password' => 'password',
            'persona_id' => $persona->id,
            'role_id' => $rol->id,
            'sucursal_id' => $this->principal->id,
        ]);

        $this->actingAs($usuario)
            ->getJson(route('buscador.ia', ['termino' => 'tienen teclados']))
            ->assertForbidden();
    }

    public function test_la_pantalla_muestra_el_modo_ia_solo_cuando_esta_configurado(): void
    {
        $this->actingAs($this->vendedor)->get(route('buscador.index'))
            ->assertOk()
            ->assertSee('data-ia-habilitado="1"', false)
            ->assertSee('data-modo="ia"', false)
            ->assertSee('data-ia-url', false);

        config(['buscador_ia.clave' => null]);

        $this->actingAs($this->vendedor)->get(route('buscador.index'))
            ->assertOk()
            ->assertSee('data-ia-habilitado="0"', false)
            ->assertDontSee('data-modo="ia"', false);
    }

    /**
     * Fase 1 (clasificación) por defecto: la categoría de los productos.
     *
     * @return array{categorias: array<int, int>, respuesta: ?string}
     */
    private function clasificacion(): array
    {
        return ['categorias' => [$this->categoria->id], 'respuesta' => null];
    }

    /**
     * Finge las dos llamadas a OpenCode Go distinguiéndolas por el contenido:
     * la fase 1 lleva "CATEGORÍAS:" y la fase 2 lleva "CATÁLOGO:".
     *
     * @param  array<string, mixed>  $clasificacion
     * @param  array<string, mixed>  $consulta
     */
    private function fakeIa(array $clasificacion, array $consulta): void
    {
        Http::fake(function (Request $request) use ($clasificacion, $consulta) {
            $contenido = (string) ($request->data()['messages'][1]['content'] ?? '');
            $datos = str_contains($contenido, 'CATEGORÍAS:') ? $clasificacion : $consulta;

            return Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode($datos)]],
                ],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function producto(array $extra = []): Producto
    {
        return Producto::create(array_merge([
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Producto',
            'marca' => 'Marca',
            'modelo' => null,
            'codigo' => 'COD-'.uniqid(),
            'condicion' => 'nuevo',
            'precio_compra' => 100,
            'precio_venta' => 150,
            'stock_minimo' => 1,
            'activo' => true,
        ], $extra));
    }
}
