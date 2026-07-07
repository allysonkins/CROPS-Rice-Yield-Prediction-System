<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('advisories', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('content');
        $table->enum('target_audience', ['all', 'farmers', 'staff', 'admin'])->default('all');
        $table->string('image')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamp('published_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisories');
    }
};
