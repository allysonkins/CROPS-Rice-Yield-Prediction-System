<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing role column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        // Re-add it with the full list of allowed values
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'staff', 'farmer'])->default('farmer');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->enum('role', ['admin', 'farmer'])->default('farmer');
        });
    }
};