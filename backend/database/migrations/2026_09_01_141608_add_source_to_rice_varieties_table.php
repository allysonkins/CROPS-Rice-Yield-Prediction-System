<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rice_varieties', function (Blueprint $table) {
            if (!Schema::hasColumn('rice_varieties', 'source')) {
                $table->enum('source', ['NSIC', 'Local'])->default('NSIC')->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rice_varieties', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};