<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify the enum to include 'staff'
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'staff', 'farmer') DEFAULT 'farmer'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin', 'farmer') DEFAULT 'farmer'");
    }
};