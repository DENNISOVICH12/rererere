<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->timestamp('change_requested_at')->nullable()->after('release_trigger');
            $table->unsignedBigInteger('change_requested_by')->nullable()->after('change_requested_at');
            $table->string('change_request_reason', 255)->nullable()->after('change_requested_by');
            $table->unsignedSmallInteger('change_request_count')->default(0)->after('change_request_reason');

            $table->foreign('change_requested_by', 'pedidos_change_requested_by_fk')
                ->references('id')
                ->on('usuarios')
                ->nullOnDelete();

            $table->index(['estado', 'change_requested_at'], 'pedidos_estado_change_requested_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex('pedidos_estado_change_requested_at_idx');
            $table->dropForeign('pedidos_change_requested_by_fk');
            $table->dropColumn([
                'change_requested_at',
                'change_requested_by',
                'change_request_reason',
                'change_request_count',
            ]);
        });
    }
};
