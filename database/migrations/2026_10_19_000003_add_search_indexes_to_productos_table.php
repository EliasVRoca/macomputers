<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Índices de apoyo para la búsqueda por nombre, marca y modelo.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->index('nombre');
            $table->index('marca');
            $table->index('modelo');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropIndex(['nombre']);
            $table->dropIndex(['marca']);
            $table->dropIndex(['modelo']);
        });
    }
};
