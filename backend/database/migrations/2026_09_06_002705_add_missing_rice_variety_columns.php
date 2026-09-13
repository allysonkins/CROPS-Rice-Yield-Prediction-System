<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rice_varieties', function (Blueprint $table) {
            // Growth periods
            if (!Schema::hasColumn('rice_varieties', 'growth_period_transplanted')) {
                $table->integer('growth_period_transplanted')->nullable();
            }
            if (!Schema::hasColumn('rice_varieties', 'growth_period_direct')) {
                $table->integer('growth_period_direct')->nullable();
            }

            // Yields – transplanted
            if (!Schema::hasColumn('rice_varieties', 'avg_yield_transplanted')) {
                $table->decimal('avg_yield_transplanted', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('rice_varieties', 'max_yield_transplanted')) {
                $table->decimal('max_yield_transplanted', 5, 2)->nullable();
            }

            // Yields – direct
            if (!Schema::hasColumn('rice_varieties', 'avg_yield_direct')) {
                $table->decimal('avg_yield_direct', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('rice_varieties', 'max_yield_direct')) {
                $table->decimal('max_yield_direct', 5, 2)->nullable();
            }

            // Other fields
            if (!Schema::hasColumn('rice_varieties', 'grain_quality')) {
                $table->text('grain_quality')->nullable();
            }
            if (!Schema::hasColumn('rice_varieties', 'disease_susceptibility')) {
                $table->string('disease_susceptibility')->nullable();
            }
            if (!Schema::hasColumn('rice_varieties', 'resilience')) {
                $table->json('resilience')->nullable();
            }
            if (!Schema::hasColumn('rice_varieties', 'optimal_temp_min')) {
                $table->decimal('optimal_temp_min', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('rice_varieties', 'optimal_temp_max')) {
                $table->decimal('optimal_temp_max', 5, 2)->nullable();
            }
            if (!Schema::hasColumn('rice_varieties', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('rice_varieties', function (Blueprint $table) {
            $columns = [
                'growth_period_transplanted',
                'growth_period_direct',
                'avg_yield_transplanted',
                'max_yield_transplanted',
                'avg_yield_direct',
                'max_yield_direct',
                'grain_quality',
                'disease_susceptibility',
                'resilience',
                'optimal_temp_min',
                'optimal_temp_max',
                'deleted_at'
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('rice_varieties', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};