<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Servicio técnico como orden de trabajo: datos ampliados, historial de estados y repuestos.
// Los repuestos consumen inventario real de la sucursal del taller (RF16 / CU14).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicios', function (Blueprint $table) {
            $table->string('numero')->nullable()->after('id');
            $table->date('fecha_recepcion')->nullable();
            $table->date('fecha_prometida')->nullable();
            $table->date('fecha_entrega')->nullable();
            $table->string('prioridad', 20)->default('normal');
            $table->text('accesorios_recibidos')->nullable();
            $table->decimal('mano_de_obra', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->unsignedInteger('garantia_dias')->default(0);
            $table->text('observaciones')->nullable();
            $table->unique('numero');
        });

        Schema::create('servicio_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado_anterior', 20)->nullable();
            $table->string('estado_nuevo', 20);
            $table->text('comentario')->nullable();
            $table->timestamps();
        });

        Schema::create('servicio_repuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->constrained('servicios')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_repuestos');
        Schema::dropIfExists('servicio_historial');

        Schema::table('servicios', function (Blueprint $table) {
            $table->dropUnique(['numero']);
            $table->dropColumn([
                'numero', 'fecha_recepcion', 'fecha_prometida', 'fecha_entrega',
                'prioridad', 'accesorios_recibidos', 'mano_de_obra', 'descuento',
                'garantia_dias', 'observaciones',
            ]);
        });
    }
};
