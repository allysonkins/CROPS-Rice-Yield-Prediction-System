<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_records', function (Blueprint $table) {
            if (!Schema::hasColumn('farm_records', 'weather_temperature')) {
                $table->decimal('weather_temperature', 5, 2)->nullable()->after('status');
            }
            if (!Schema::hasColumn('farm_records', 'weather_rainfall')) {
                $table->decimal('weather_rainfall', 7, 2)->nullable()->after('weather_temperature');
            }
            if (!Schema::hasColumn('farm_records', 'weather_humidity')) {
                $table->decimal('weather_humidity', 5, 2)->nullable()->after('weather_rainfall');
            }
            if (!Schema::hasColumn('farm_records', 'weather_captured_at')) {
                $table->timestamp('weather_captured_at')->nullable()->after('weather_humidity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farm_records', function (Blueprint $table) {
            $table->dropColumn([
                'weather_temperature',
                'weather_rainfall',
                'weather_humidity',
                'weather_captured_at',
            ]);
        });
    }
};