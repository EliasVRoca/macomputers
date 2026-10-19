<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ajustes de los módulos de Ventas (RF12–RF15, RF17):
//  - clientes: dirección (CU15) y baja lógica (activo).
//  - apartados: venta generada al completar, motivo y seña retenida al cancelar.
//  - encargos: precio total y producto del catálogo (opcional) para el saldo automático.
//  - cambios: cantidad variable y monto informativo de reembolso/diferencia.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('direccion', 200)->nullable()->after('telefono');
            $table->boolean('activo')->default(true)->after('carnet');
        });

        Schema::table('apartados', function (Blueprint $table) {
            $table->foreignId('venta_id')->nullable()->unique()->after('user_id')
                ->constrained('ventas')->nullOnDelete();
            $table->string('motivo_cancelacion')->nullable()->after('estado');
            $table->decimal('sena_retenida', 12, 2)->default(0)->after('sena');
        });

        Schema::table('encargos', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->after('user_id')
                ->constrained('productos')->nullOnDelete();
            $table->decimal('total', 12, 2)->default(0)->after('descripcion');
        });

        Schema::table('cambios', function (Blueprint $table) {
            $table->unsignedInteger('cantidad')->default(1)->after('producto_reemplazo_id');
            $table->decimal('monto_diferencia', 12, 2)->default(0)->after('cantidad');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['direccion', 'activo']);
        });

        Schema::table('apartados', function (Blueprint $table) {
            $table->dropForeign(['venta_id']);
            $table->dropColumn(['venta_id', 'motivo_cancelacion', 'sena_retenida']);
        });

        Schema::table('encargos', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropColumn(['producto_id', 'total']);
        });

        Schema::table('cambios', function (Blueprint $table) {
            $table->dropColumn(['cantidad', 'monto_diferencia']);
        });
    }
};
