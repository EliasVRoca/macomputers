<?php

namespace Tests\Integracion\BaseDatos;

use App\Models\Categoria;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\Concerns\CreaEscenario;
use Tests\Support\Concerns\RequiereMariaDB;
use Tests\TestCase;

/**
 * Integración con el motor real (MariaDB/MySQL).
 *
 * Se salta con el perfil SQLite. Ejecutar con:
 *   DB_CONNECTION=mysql DB_DATABASE=macomputers_test php artisan test tests/Integracion/BaseDatos/MariaDBTest.php
 */
class MariaDBTest extends TestCase
{
    use CreaEscenario, RefreshDatabase, RequiereMariaDB;

    public function test_la_collation_de_mariadb_es_case_insensitive(): void
    {
        $this->requiereMariaDB();
        $this->sembrar();

        Categoria::create(['nombre' => 'Equipos']);

        $this->expectException(QueryException::class);

        DB::table('categorias')->insert([
            'nombre' => 'equipos',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_las_claves_foraneas_restrict_funcionan_en_mariadb(): void
    {
        $this->requiereMariaDB();
        $this->sembrar();

        $categoria = $this->crearCategoria();
        $this->crearProducto(['categoria_id' => $categoria->id]);

        $this->expectException(QueryException::class);

        $categoria->delete();
    }

    public function test_el_motor_soporta_lock_for_update(): void
    {
        $this->requiereMariaDB();
        $this->sembrar();

        $producto = $this->crearProducto();
        $this->darStock($producto, $this->sucursalPrincipal(), 5, $this->gerente());

        $saldo = DB::transaction(function () use ($producto) {
            return DB::table('inventarios')
                ->where('producto_id', $producto->id)
                ->lockForUpdate()
                ->value('cantidad');
        });

        $this->assertSame(5, (int) $saldo);
    }
}
