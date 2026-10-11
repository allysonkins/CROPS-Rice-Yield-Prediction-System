<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            if (! Schema::hasColumn('predictions', 'predicted_class')) {
                $table->string('predicted_class')->nullable()->after('predicted_yield_tons_ha');
            }

            if (! Schema::hasColumn('predictions', 'confidence')) {
                $table->decimal('confidence', 5, 4)->nullable()->after('predicted_class');
            }
        });
    }

    public function down(): void
    {
        Schema::table('predictions', function (Blueprint $table) {
            if (Schema::hasColumn('predictions', 'predicted_class')) {
                $table->dropColumn('predicted_class');
            }

            // Do not drop confidence here because it belongs to
            // the earlier confidence migration.
        });
    }
};