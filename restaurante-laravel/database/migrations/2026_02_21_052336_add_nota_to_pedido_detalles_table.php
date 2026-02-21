<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
{
    Schema::table('pedido_detalles', function (Blueprint $table) {
        if (!Schema::hasColumn('pedido_detalles', 'nota')) {
            $table->text('nota')->nullable();
        }
    });
}
public function down(): void
{
    if (Schema::hasColumn('pedido_detalles', 'nota')) {
        Schema::table('pedido_detalles', function (Blueprint $table) {
            $table->dropColumn('nota');
        });
    }
}
};
