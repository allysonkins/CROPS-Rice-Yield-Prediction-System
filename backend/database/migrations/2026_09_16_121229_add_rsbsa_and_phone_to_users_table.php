<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_rsbsa_and_phone_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rsbsa_number')->nullable()->unique()->after('email');
            $table->string('phone', 20)->nullable()->unique()->after('rsbsa_number');
            $table->timestamp('password_changed_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rsbsa_number', 'phone', 'password_changed_at']);
        });
    }
};