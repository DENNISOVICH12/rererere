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
    Schema::table('mesas', function (Blueprint $table) {

    if (!Schema::hasColumn('mesas', 'estado')) {
        $table->string('estado')->default('libre');
    }

    if (!Schema::hasColumn('mesas', 'restaurant_id')) {
        $table->unsignedBigInteger('restaurant_id')->default(1);
    }

});
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
