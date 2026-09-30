<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_records', function (Blueprint $table) {
            $table->year('year')->nullable()->after('season');
            $table->index('year');
        });

        // Backfill existing records using the creation year
        DB::table('farm_records')->update([
            'year' => DB::raw('YEAR(created_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('farm_records', function (Blueprint $table) {
            $table->dropIndex(['year']);
            $table->dropColumn('year');
        });
    }
};