<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('comprobantes', 'ajuste_pendiente_at')) {
            Schema::table('comprobantes', function (Blueprint $table) {
                $table->timestamp('ajuste_pendiente_at')->nullable()->after('pagado_at');
            });
        }

        if (!Schema::hasColumn('pedidos', 'ajuste_pendiente_at')) {
            Schema::table('pedidos', function (Blueprint $table) {
                $table->timestamp('ajuste_pendiente_at')->nullable()->after('change_request_reason');
            });
        }
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