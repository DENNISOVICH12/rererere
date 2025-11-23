<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('dni')->unique();
            $table->integer('edad');
            $table->timestamps();

            // Relación opcional con restaurante
            $table->foreignId('restaurant_id')
                  ->nullable()
                  ->constrained('restaurants')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
