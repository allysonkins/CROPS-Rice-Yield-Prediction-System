<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Check if the foreign key exists
        $constraint = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'farm_records' 
              AND COLUMN_NAME = 'rice_variety_id' 
              AND CONSTRAINT_SCHEMA = DATABASE()
        ");

        if (!empty($constraint)) {
            $foreignKeyName = $constraint[0]->CONSTRAINT_NAME;
            // Drop the existing constraint
            Schema::table('farm_records', function (Blueprint $table) use ($foreignKeyName) {
                $table->dropForeign($foreignKeyName);
            });
        }

        // 2. Add the new foreign key with ON DELETE CASCADE
        Schema::table('farm_records', function (Blueprint $table) {
            $table->foreign('rice_variety_id')
                  ->references('id')
                  ->on('rice_varieties')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // Drop the cascade constraint and revert to default (RESTRICT)
        Schema::table('farm_records', function (Blueprint $table) {
            $table->dropForeign(['rice_variety_id']);
            // Re-add without cascade (the default is RESTRICT)
            $table->foreign('rice_variety_id')
                  ->references('id')
                  ->on('rice_varieties');
        });
    }
};