<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clientes_mesa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->string('mesa', 50);
            $table->string('nombre', 120)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['restaurant_id', 'mesa']);
        });

        Schema::table('pedidos', function (Blueprint $table) {
            if (!Schema::hasColumn('pedidos', 'cliente_mesa_id')) {
                $table->foreignId('cliente_mesa_id')
                    ->nullable()
                    ->after('cliente_id')
                    ->constrained('clientes_mesa')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            if (Schema::hasColumn('pedidos', 'cliente_mesa_id')) {
                $table->dropConstrainedForeignId('cliente_mesa_id');
            }
        });

        Schema::dropIfExists('clientes_mesa');
    }
};
