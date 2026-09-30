<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            if (Schema::hasColumn('predictions', 'predicted_class')) {
                $table->dropColumn('predicted_class');
            }
            if (Schema::hasColumn('predictions', 'confidence')) {
                $table->dropColumn('confidence');
            }
        });

        // Rename existing model_type rows so they pass the new filters
        \DB::table('predictions')
            ->where('model_type', 'RandomForest')
            ->update(['model_type' => 'XGBoost']);
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            $table->string('predicted_class')->nullable();
            $table->float('confidence')->nullable();
        });

        \DB::table('predictions')
            ->where('model_type', 'XGBoost')
            ->update(['model_type', 'RandomForest']);
    }
};