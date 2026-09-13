<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rice_varieties', function (Blueprint $table) {
            // Add only if they don't already exist
            if (!Schema::hasColumn('rice_varieties', 'avg_yield_direct')) {
                $table->decimal('avg_yield_direct', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('rice_varieties', 'max_yield_direct')) {
                $table->decimal('max_yield_direct', 5, 2)->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('rice_varieties', function (Blueprint $table) {
            $table->dropColumn(['avg_yield_direct', 'max_yield_direct']);
        });
    }
};