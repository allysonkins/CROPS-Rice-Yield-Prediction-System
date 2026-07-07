<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farm_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('rice_variety_id')->constrained('rice_varieties');
            $table->string('season');
            $table->decimal('fertilizer_kg_ha', 8, 2);
            $table->decimal('historical_yield_tons_ha', 8, 2)->nullable();
            $table->string('seeding_method')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farm_records');
    }
};