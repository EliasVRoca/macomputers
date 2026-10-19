<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Soporte del buscador inteligente: imágenes, ficha técnica y log de búsquedas.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('ruta')->nullable();
            $table->string('url', 500)->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('es_principal')->default(false);
            $table->timestamps();

            $table->index(['producto_id', 'es_principal']);
        });

        Schema::create('producto_atributos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('atributo', 50);
            $table->string('valor', 100);
            $table->timestamps();

            $table->unique(['producto_id', 'atributo']);
            $table->index(['atributo', 'valor']);
        });

        Schema::create('busquedas_producto', function (Blueprint $table) {
            $table->id();
            $table->string('termino', 100);
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resultado', 20);
            $table->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $table->timestamps();

            $table->index(['termino', 'resultado']);
            $table->index('resultado');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('busquedas_producto');
        Schema::dropIfExists('producto_atributos');
        Schema::dropIfExists('producto_imagenes');
    }
};
