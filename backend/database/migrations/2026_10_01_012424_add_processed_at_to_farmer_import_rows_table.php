<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('farmer_import_rows', function (Blueprint $table) {
            $table->timestamp('processed_at')->nullable()->after('errors');
            $table->index(['batch_id', 'status', 'processed_at'], 'idx_batch_status_processed');
        });
    }

    public function down(): void
    {
        Schema::table('farmer_import_rows', function (Blueprint $table) {
            $table->dropIndex('idx_batch_status_processed');
            $table->dropColumn('processed_at');
        });
    }
};