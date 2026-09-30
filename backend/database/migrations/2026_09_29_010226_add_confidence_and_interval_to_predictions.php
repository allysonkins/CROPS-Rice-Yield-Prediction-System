<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            if (!Schema::hasColumn('predictions', 'confidence')) {
                $table->float('confidence')->nullable()->after('predicted_yield_tons_ha');
            }
            if (!Schema::hasColumn('predictions', 'yield_lower')) {
                $table->float('yield_lower')->nullable()->after('confidence');
            }
            if (!Schema::hasColumn('predictions', 'yield_upper')) {
                $table->float('yield_upper')->nullable()->after('yield_lower');
            }
        });

        // Drop the classification column if it still exists
        if (Schema::hasColumn('predictions', 'predicted_class')) {
            Schema::table('predictions', function (Blueprint $table) {
                $table->dropColumn('predicted_class');
            });
        }

        // Rename any legacy RandomForest rows so existing filters still find them
        DB::table('predictions')
            ->where('model_type', 'RandomForest')
            ->update(['model_type' => 'XGBoost']);
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->dropColumn(['confidence', 'yield_lower', 'yield_upper']);
            $table->string('predicted_class')->nullable();
        });

        DB::table('predictions')
            ->where('model_type', 'XGBoost')
            ->update(['model_type' => 'RandomForest']);
    }
};