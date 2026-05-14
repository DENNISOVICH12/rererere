<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedInteger('mesa_numero')->nullable();
            $table->json('pedidos_ids');
            $table->json('detalle');       // snapshot de ítems al momento del cobro
            $table->decimal('total', 12, 2);
            $table->string('mesero_nombre')->nullable();
            $table->timestamp('pagado_at')->nullable();
            $table->timestamps();

            $table->foreign('cliente_id')->references('id')->on('clientes')->cascadeOnDelete();
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};