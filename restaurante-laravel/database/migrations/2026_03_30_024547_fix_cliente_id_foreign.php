<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Eliminar la foreign key incorrecta
            $table->dropForeign(['cliente_id']);

            // Crear la foreign key correcta hacia clientes
            $table->foreign('cliente_id')
                ->references('id')
                ->on('clientes')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Eliminar la foreign key actual
            $table->dropForeign(['cliente_id']);

            // Restaurar la anterior (como estaba antes)
            $table->foreign('cliente_id')
                ->references('id')
                ->on('usuarios')
                ->nullOnDelete();
        });
    }
};