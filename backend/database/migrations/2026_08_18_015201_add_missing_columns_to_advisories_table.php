<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advisories', function (Blueprint $table) {
            // ✅ Add expiry_date column
            if (!Schema::hasColumn('advisories', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('target_audience');
            }

            // ✅ Add user_id column (if missing)
            if (!Schema::hasColumn('advisories', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null')->after('published_at');
            }

            // ✅ Add published_at column (if missing)
            if (!Schema::hasColumn('advisories', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('is_active');
            }

            // ✅ Add image column (if missing)
            if (!Schema::hasColumn('advisories', 'image')) {
                $table->string('image')->nullable()->after('published_at');
            }

            // ✅ Fix target_audience ENUM to include 'staff' and 'admin'
            DB::statement("ALTER TABLE advisories MODIFY target_audience ENUM('all', 'farmers', 'staff', 'admin') NOT NULL DEFAULT 'all'");
        });
    }

    public function down(): void
    {
        Schema::table('advisories', function (Blueprint $table) {
            $table->dropColumn(['expiry_date', 'user_id', 'published_at', 'image']);
        });
    }
};