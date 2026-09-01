<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rice_varieties', function (Blueprint $table) {
            $table->json('resilience')->nullable()->after('disease_susceptibility');
        });
    }

    public function down()
    {
        Schema::table('rice_varieties', function (Blueprint $table) {
            $table->dropColumn('resilience');
        });
    }
};