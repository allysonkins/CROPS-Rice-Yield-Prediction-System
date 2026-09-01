<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_records', function (Blueprint $table) {
            $table->decimal('actual_yield_tons_ha', 8, 2)->nullable()->after('historical_yield_tons_ha');
        });
    }

    public function down(): void
    {
        Schema::table('farm_records', function (Blueprint $table) {
            $table->dropColumn('actual_yield_tons_ha');
        });
    }
};