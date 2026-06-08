<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (!Schema::hasColumn('restaurants', 'wifi_ssid')) {
                $table->string('wifi_ssid')->nullable()->after('telefono');
            }
            if (!Schema::hasColumn('restaurants', 'wifi_password')) {
                $table->string('wifi_password')->nullable()->after('wifi_ssid');
            }
            if (!Schema::hasColumn('restaurants', 'wifi_security')) {
                $table->string('wifi_security', 10)->nullable()->default('WPA')->after('wifi_password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['wifi_ssid', 'wifi_password', 'wifi_security']);
        });
    }
};