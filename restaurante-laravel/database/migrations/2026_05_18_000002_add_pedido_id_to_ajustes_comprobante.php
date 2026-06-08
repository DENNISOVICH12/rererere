<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ajustes_comprobante', function (Blueprint $table) {
            // Hacer comprobante_id nullable para soportar ajustes pre-pago
            $table->unsignedBigInteger('comprobante_id')->nullable()->change();

            // Referencia opcional al pedido (para ajustes antes del pago)
            if (!Schema::hasColumn('ajustes_comprobante', 'pedido_id')) {
                $table->unsignedBigInteger('pedido_id')->nullable()->after('comprobante_id');
            }

            // Tipo de ajuste: 'anulacion_item' (post-pago) o 'edicion_pedido' (pre-pago)
            if (!Schema::hasColumn('ajustes_comprobante', 'tipo')) {
                $table->string('tipo', 30)->default('anulacion_item')->after('justificacion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ajustes_comprobante', function (Blueprint $table) {
            $table->unsignedBigInteger('comprobante_id')->nullable(false)->change();
            $table->dropColumn(['pedido_id', 'tipo']);
        });
    }
};