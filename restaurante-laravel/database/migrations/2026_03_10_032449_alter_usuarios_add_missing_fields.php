<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (!Schema::hasColumn('usuarios', 'usuario')) {
                $table->string('usuario')->unique()->nullable()->after('id');
            }

            if (!Schema::hasColumn('usuarios', 'apellido')) {
                $table->string('apellido')->nullable()->after('nombre');
            }

            if (!Schema::hasColumn('usuarios', 'restaurant_id')) {
                $table->unsignedBigInteger('restaurant_id')->nullable()->after('rol');
            }

            if (!Schema::hasColumn('usuarios', 'created_at') && !Schema::hasColumn('usuarios', 'updated_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'restaurant_id')) {
                $table->dropColumn('restaurant_id');
            }

            if (Schema::hasColumn('usuarios', 'apellido')) {
                $table->dropColumn('apellido');
            }

            if (Schema::hasColumn('usuarios', 'usuario')) {
                $table->dropUnique(['usuario']);
                $table->dropColumn('usuario');
            }

            if (Schema::hasColumn('usuarios', 'created_at') && Schema::hasColumn('usuarios', 'updated_at')) {
                $table->dropTimestamps();
            }
        });
    }
};