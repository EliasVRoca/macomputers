<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->string('proveedor', 20)->nullable()->after('url');
            $table->string('origen_url', 1000)->nullable()->after('proveedor');
            $table->string('titulo', 200)->nullable()->after('origen_url');
            $table->string('autor', 150)->nullable()->after('titulo');
            $table->string('licencia', 100)->nullable()->after('autor');
            $table->string('licencia_url', 1000)->nullable()->after('licencia');
        });
    }

    public function down(): void
    {
        Schema::table('producto_imagenes', function (Blueprint $table) {
            $table->dropColumn([
                'proveedor',
                'origen_url',
                'titulo',
                'autor',
                'licencia',
                'licencia_url',
            ]);
        });
    }
};
