<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('details_queries') && ! Schema::hasColumn('details_queries', 'source')) {
            Schema::table('details_queries', function (Blueprint $table) {
                $table->string('source')->nullable()->after('requested_query_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('details_queries') && Schema::hasColumn('details_queries', 'source')) {
            Schema::table('details_queries', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }
    }
};
