<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->decimal('total', 10, 2)->default(0);
            $table->integer('mesa')->nullable();
            $table->string('estado')->default('pendiente');
            $table->timestamps();

            // Multitenencia: relación con restaurante
            $table->foreignId('restaurant_id')
                  ->constrained('restaurants')
                  ->cascadeOnDelete();

            // Relación con cliente (opcional)
            $table->foreignId('cliente_id')
                  ->nullable()
                  ->constrained('usuarios')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
