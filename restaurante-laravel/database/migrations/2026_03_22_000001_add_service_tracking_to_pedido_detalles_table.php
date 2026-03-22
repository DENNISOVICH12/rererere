<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_detalles', function (Blueprint $table) {
            if (!Schema::hasColumn('pedido_detalles', 'grupo_servicio')) {
                $table->string('grupo_servicio')->default('plato')->after('nota');
            }

            if (!Schema::hasColumn('pedido_detalles', 'estado_servicio')) {
                $table->string('estado_servicio')->default('pendiente')->after('grupo_servicio');
            }
        });

        if (Schema::hasColumn('pedido_detalles', 'grupo_servicio')) {
            DB::table('pedido_detalles')
                ->whereNull('grupo_servicio')
                ->update(['grupo_servicio' => 'plato']);
        }

        if (Schema::hasColumn('pedido_detalles', 'estado_servicio')) {
            DB::table('pedido_detalles')
                ->whereNull('estado_servicio')
                ->update(['estado_servicio' => 'pendiente']);
        }
    }

    public function down(): void
    {
        Schema::table('pedido_detalles', function (Blueprint $table) {
            if (Schema::hasColumn('pedido_detalles', 'estado_servicio')) {
                $table->dropColumn('estado_servicio');
            }

            if (Schema::hasColumn('pedido_detalles', 'grupo_servicio')) {
                $table->dropColumn('grupo_servicio');
            }
        });
    }
};
