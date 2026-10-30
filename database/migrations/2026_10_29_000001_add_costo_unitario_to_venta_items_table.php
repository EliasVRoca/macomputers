<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reportes: guarda el costo del producto al momento de la venta para poder
// calcular la utilidad bruta histórica sin depender del precio de compra actual.
// Las filas anteriores quedan con NULL y los reportes usan el costo actual como aproximación.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta_items', function (Blueprint $table) {
            $table->decimal('costo_unitario', 12, 2)->nullable()->after('precio_unitario');
        });
    }

    public function down(): void
    {
        Schema::table('venta_items', function (Blueprint $table) {
            $table->dropColumn('costo_unitario');
        });
    }
};
