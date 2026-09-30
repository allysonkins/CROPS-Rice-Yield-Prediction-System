<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_import_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('new_count')->default(0);
            $table->unsignedInteger('duplicate_count')->default(0);
            $table->unsignedInteger('invalid_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->string('status')->default('pending'); // pending|previewed|committed|failed
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('farmer_import_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_id');
            $table->unsignedInteger('line');
            $table->string('status', 20);      // new|duplicate|invalid
            $table->boolean('is_rice')->default(true);
            $table->string('rsbsa_number')->nullable();
            $table->string('name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('sex')->nullable();
            $table->string('phone')->nullable();
            $table->string('barangay')->nullable();
            $table->string('parcel_no')->nullable();
            $table->string('parcel_barangay')->nullable();
            $table->decimal('land_area_ha', 8, 2)->nullable();
            $table->string('commodity')->nullable();
            $table->text('errors')->nullable();
            $table->timestamps();

            $table->foreign('batch_id')->references('id')->on('farmer_import_batches')->onDelete('cascade');
            $table->index(['batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_import_rows');
        Schema::dropIfExists('farmer_import_batches');
    }
};