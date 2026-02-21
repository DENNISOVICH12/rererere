<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            if (!$this->indexExists('pedidos', 'pedidos_restaurant_estado_created_idx')) {
                $table->index(['restaurant_id', 'estado', 'created_at'], 'pedidos_restaurant_estado_created_idx');
            }

            if (!$this->indexExists('pedidos', 'pedidos_restaurant_updated_idx')) {
                $table->index(['restaurant_id', 'updated_at'], 'pedidos_restaurant_updated_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            if ($this->indexExists('pedidos', 'pedidos_restaurant_estado_created_idx')) {
                $table->dropIndex('pedidos_restaurant_estado_created_idx');
            }

            if ($this->indexExists('pedidos', 'pedidos_restaurant_updated_idx')) {
                $table->dropIndex('pedidos_restaurant_updated_idx');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list('{$table}')");
            return collect($rows)->contains(fn ($row) => ($row->name ?? null) === $index);
        }

        $database = DB::getDatabaseName();
        $rows = DB::select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $index]
        );

        return !empty($rows);
    }
};
