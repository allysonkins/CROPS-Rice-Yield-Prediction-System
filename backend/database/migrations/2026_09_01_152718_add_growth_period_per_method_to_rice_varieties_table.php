<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rice_varieties', function (Blueprint $table) {
            $table->integer('growth_period_transplanted')->nullable()->after('growth_period');
            $table->integer('growth_period_direct')->nullable()->after('growth_period_transplanted');
        });
    }

    public function down(): void
    {
        Schema::table('rice_varieties', function (Blueprint $table) {
            $table->dropColumn(['growth_period_transplanted', 'growth_period_direct']);
        });
    }
};