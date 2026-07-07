<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_record_id')->constrained('farm_records')->onDelete('cascade');
            $table->enum('model_type', ['RandomForest', 'XGBoost', 'Ensemble']);
            $table->decimal('predicted_yield_tons_ha', 8, 2);
            $table->json('input_features')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};