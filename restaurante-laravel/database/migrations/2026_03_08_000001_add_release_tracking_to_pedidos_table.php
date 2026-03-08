<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->timestamp('released_to_kitchen_at')->nullable()->after('hold_expires_at');
            $table->string('release_trigger', 40)->nullable()->after('released_to_kitchen_at');
            $table->index(['release_trigger', 'released_to_kitchen_at'], 'pedidos_release_trigger_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex('pedidos_release_trigger_idx');
            $table->dropColumn(['release_trigger', 'released_to_kitchen_at']);
        });
    }
};
