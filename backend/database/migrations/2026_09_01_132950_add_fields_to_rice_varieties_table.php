<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rice_varieties', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->decimal('avg_yield', 5, 2)->nullable()->after('growth_period');
            $table->decimal('max_yield', 5, 2)->nullable()->after('avg_yield');
            $table->text('grain_quality')->nullable()->after('max_yield');
        });
    }

    public function down()
    {
        Schema::table('rice_varieties', function (Blueprint $table) {
            $table->dropColumn(['description', 'avg_yield', 'max_yield', 'grain_quality']);
        });
    }
};