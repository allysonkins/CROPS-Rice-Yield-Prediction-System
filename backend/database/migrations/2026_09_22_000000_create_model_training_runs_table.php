<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_training_runs', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('pending'); // pending|running|completed|failed
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('model_version')->nullable();
            $table->string('model_path')->nullable();
            $table->unsignedInteger('training_samples')->default(0);
            $table->unsignedInteger('test_samples')->default(0);
            $table->unsignedInteger('real_samples')->default(0);
            $table->unsignedInteger('synthetic_samples')->default(0);
            $table->decimal('overall_accuracy', 6, 4)->nullable();
            $table->json('metrics')->nullable();
            $table->json('previous_metrics')->nullable();
            $table->longText('log_output')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_training_runs');
    }
};