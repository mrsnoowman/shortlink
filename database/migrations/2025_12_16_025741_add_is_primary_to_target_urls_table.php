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
        Schema::table('target_urls', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('is_blocked');
        });

        // Set first target URL (by ID) as primary for each shortlink
        \DB::statement('
            UPDATE target_urls t1
            SET is_primary = 1
            WHERE t1.id = (
                SELECT MIN(t2.id)
                FROM target_urls t2
                WHERE t2.shortlink_id = t1.shortlink_id
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('target_urls', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};
