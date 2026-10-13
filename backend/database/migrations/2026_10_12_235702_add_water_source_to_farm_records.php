<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_records', function (Blueprint $table) {
            $table->enum('water_source', ['Rainfed', 'Irrigated', 'Both'])
                ->nullable()
                ->after('seeding_method');
        });
    }

    public function down(): void
    {
        Schema::table('farm_records', function (Blueprint $table) {
            $table->dropColumn('water_source');
        });
    }
};