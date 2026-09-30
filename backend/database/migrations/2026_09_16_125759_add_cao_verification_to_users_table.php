<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('verified_by_cao_at')->nullable()->after('email_verified_at');
            $table->foreignId('verified_by_cao_id')->nullable()->after('verified_by_cao_at')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['verified_by_cao_id']);
            $table->dropColumn(['verified_by_cao_at', 'verified_by_cao_id']);
        });
    }
};