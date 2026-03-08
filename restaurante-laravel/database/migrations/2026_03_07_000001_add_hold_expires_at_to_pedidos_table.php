<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->timestamp('hold_expires_at')->nullable()->after('estado');
            $table->index(['estado', 'hold_expires_at'], 'pedidos_estado_hold_expires_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex('pedidos_estado_hold_expires_at_idx');
            $table->dropColumn('hold_expires_at');
        });
    }
};
