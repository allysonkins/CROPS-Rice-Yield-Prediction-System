<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('advisories', function (Blueprint $table) {
            if (Schema::hasColumn('advisories', 'barangay')) {
                $table->dropColumn('barangay');
            }
        });
    }

    public function down()
    {
        Schema::table('advisories', function (Blueprint $table) {
            $table->string('barangay')->nullable();
        });
    }
};