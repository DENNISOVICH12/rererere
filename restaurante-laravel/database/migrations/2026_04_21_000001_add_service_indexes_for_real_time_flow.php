<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->index(['restaurant_id', 'mesa_id', 'estado', 'created_at'], 'pedidos_rest_mesa_estado_created_idx');
            $table->index(['restaurant_id', 'cliente_mesa_id', 'estado'], 'pedidos_rest_cliente_mesa_estado_idx');
        });

        Schema::table('pedido_detalles', function (Blueprint $table) {
            $table->index(['pedido_id', 'grupo_servicio', 'estado_servicio'], 'pedido_detalles_pedido_grupo_estado_idx');
            $table->index(['pedido_id', 'menu_item_id'], 'pedido_detalles_pedido_menu_item_idx');
        });
    }

    public function down(): void
    {
        Schema::table('pedido_detalles', function (Blueprint $table) {
            $table->dropIndex('pedido_detalles_pedido_grupo_estado_idx');
            $table->dropIndex('pedido_detalles_pedido_menu_item_idx');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex('pedidos_rest_mesa_estado_created_idx');
            $table->dropIndex('pedidos_rest_cliente_mesa_estado_idx');
        });
    }
};
