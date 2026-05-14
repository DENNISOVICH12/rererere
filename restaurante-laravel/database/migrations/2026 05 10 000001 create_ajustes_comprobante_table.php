<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ajustes_comprobante', function (Blueprint $table) {
            $table->id();

            // Sin constrained() para evitar error si comprobantes no existe aún
            $table->unsignedBigInteger('comprobante_id');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('admin_id');

            $table->string('item_nombre');
            $table->integer('item_cantidad');
            $table->decimal('item_precio_unitario', 10, 2);
            $table->decimal('monto_anulado', 10, 2);

            $table->text('justificacion');
            $table->decimal('total_anterior', 12, 2);
            $table->decimal('total_nuevo', 12, 2);

            $table->timestamps();

            // Agregar FKs solo si las tablas padre existen
            if (Schema::hasTable('comprobantes')) {
                $table->foreign('comprobante_id')
                      ->references('id')->on('comprobantes')
                      ->cascadeOnDelete();
            }

            if (Schema::hasTable('restaurants')) {
                $table->foreign('restaurant_id')
                      ->references('id')->on('restaurants')
                      ->cascadeOnDelete();
            }

            if (Schema::hasTable('usuarios')) {
                $table->foreign('admin_id')
                      ->references('id')->on('usuarios')
                      ->cascadeOnDelete();
            }

            $table->index('comprobante_id');
            $table->index('restaurant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes_comprobante');
    }
};