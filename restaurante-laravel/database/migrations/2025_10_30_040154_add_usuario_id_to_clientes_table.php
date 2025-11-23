<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   public function up()
{
    Schema::table('clientes', function (Blueprint $table) {
        $table->unsignedBigInteger('usuario_id')->after('id');

        $table->foreign('usuario_id')
            ->references('id')->on('usuarios')
            ->onDelete('cascade');
    });
}

public function down()
{
    Schema::table('clientes', function (Blueprint $table) {
        $table->dropForeign(['usuario_id']);
        $table->dropColumn('usuario_id');
    });
}
};
