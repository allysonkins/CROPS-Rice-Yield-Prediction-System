<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farm_records', function (Blueprint $table) {
            $table->index('year', 'farm_records_year_idx');
            $table->index(['year', 'season'], 'farm_records_year_season_idx');
            $table->index(['rice_variety_id', 'year'], 'farm_records_variety_year_idx');
            $table->index(['status', 'year'], 'farm_records_status_year_idx');
        });

        Schema::table('predictions', function (Blueprint $table) {
            $table->index(['model_type', 'created_at'], 'predictions_type_created_idx');
            $table->index('farm_record_id', 'predictions_record_idx');
        });

        Schema::table('farms', function (Blueprint $table) {
            $table->index('barangay', 'farms_barangay_idx');
            $table->index('user_id', 'farms_user_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'name'], 'users_role_name_idx');
        });
    }

    public function down(): void
    {
        Schema::table('farm_records', function (Blueprint $table) {
            $table->dropIndex('farm_records_year_idx');
            $table->dropIndex('farm_records_year_season_idx');
            $table->dropIndex('farm_records_variety_year_idx');
            $table->dropIndex('farm_records_status_year_idx');
        });

        Schema::table('predictions', function (Blueprint $table) {
            $table->dropIndex('predictions_type_created_idx');
            $table->dropIndex('predictions_record_idx');
        });

        Schema::table('farms', function (Blueprint $table) {
            $table->dropIndex('farms_barangay_idx');
            $table->dropIndex('farms_user_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_name_idx');
        });
    }
};