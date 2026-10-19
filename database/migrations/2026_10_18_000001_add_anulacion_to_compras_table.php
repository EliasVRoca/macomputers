<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Anulación de compras: la compra no se borra (no hay borrado físico); se marca
// como anulada y se revierte su stock. Ver docs/modulos/11-compras.md.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->timestamp('anulada_at')->nullable()->after('total');
            $table->foreignId('anulada_por_id')->nullable()->after('anulada_at')
                ->constrained('users')->nullOnDelete();
            $table->string('motivo_anulacion')->nullable()->after('anulada_por_id');
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropForeign(['anulada_por_id']);
            $table->dropColumn(['anulada_at', 'anulada_por_id', 'motivo_anulacion']);
        });
    }
};
