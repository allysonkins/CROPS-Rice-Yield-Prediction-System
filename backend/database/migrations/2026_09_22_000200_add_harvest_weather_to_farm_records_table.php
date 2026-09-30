<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_records', function (Blueprint $table) {
            // Harvest-day weather snapshot. Kept separate from the
            // planting-time snapshot so both features are available
            // to the model (e.g. "yield after a dry harvest month").
            if (!Schema::hasColumn('farm_records', 'harvest_weather_temperature')) {
                $table->decimal('harvest_weather_temperature', 5, 2)->nullable()->after('weather_captured_at');
            }
            if (!Schema::hasColumn('farm_records', 'harvest_weather_rainfall')) {
                $table->decimal('harvest_weather_rainfall', 7, 2)->nullable()->after('harvest_weather_temperature');
            }
            if (!Schema::hasColumn('farm_records', 'harvest_weather_humidity')) {
                $table->decimal('harvest_weather_humidity', 5, 2)->nullable()->after('harvest_weather_rainfall');
            }
            if (!Schema::hasColumn('farm_records', 'harvest_weather_captured_at')) {
                $table->timestamp('harvest_weather_captured_at')->nullable()->after('harvest_weather_humidity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('farm_records', function (Blueprint $table) {
            $table->dropColumn([
                'harvest_weather_temperature',
                'harvest_weather_rainfall',
                'harvest_weather_humidity',
                'harvest_weather_captured_at',
            ]);
        });
    }
};