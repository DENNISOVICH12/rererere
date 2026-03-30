<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('waiter_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->cascadeOnDelete();
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->string('type', 80);
            $table->string('title', 190);
            $table->json('payload')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waiter_notifications');
    }
};
