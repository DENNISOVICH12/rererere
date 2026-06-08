<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Comprobante: marca si tiene un ajuste pendiente de reconfirmación por el cliente
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->timestamp('ajuste_pendiente_at')->nullable()->after('pagado_at');
        });

        // Pedido: indica si hay un ajuste del comprobante pendiente de confirmar
        // (se usa para bloquear "Marcar como pagado" en el panel del mesero)
        Schema::table('pedidos', function (Blueprint $table) {
            $table->timestamp('ajuste_pendiente_at')->nullable()->after('change_request_reason');
        });
    }

    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropColumn('ajuste_pendiente_at');
        });
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('ajuste_pendiente_at');
        });
    }
};