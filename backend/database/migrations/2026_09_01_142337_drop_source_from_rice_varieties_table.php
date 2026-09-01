<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rice_varieties', function (Blueprint $table) {
            if (Schema::hasColumn('rice_varieties', 'source')) {
                $table->dropColumn('source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rice_varieties', function (Blueprint $table) {
            $table->enum('source', ['NSIC', 'Local'])->default('NSIC')->after('name');
        });
    }
};