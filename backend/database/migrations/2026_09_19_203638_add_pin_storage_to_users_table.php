<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('pin_encrypted')->nullable()->after('password');
            $table->timestamp('pin_generated_at')->nullable()->after('pin_encrypted');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pin_encrypted', 'pin_generated_at']);
        });
    }
};