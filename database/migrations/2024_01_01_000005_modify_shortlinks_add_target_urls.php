<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shortlinks', function (Blueprint $table) {
            // Add new column for multiple target URLs (JSON)
            $table->json('target_urls')->nullable()->after('target_url');
        });

        // Migrate existing target_url to target_urls as array
        DB::table('shortlinks')->get()->each(function ($shortlink) {
            if ($shortlink->target_url) {
                DB::table('shortlinks')
                    ->where('id', $shortlink->id)
                    ->update([
                        'target_urls' => json_encode([$shortlink->target_url])
                    ]);
            }
        });

        // Keep target_url for backward compatibility, but make it nullable
        Schema::table('shortlinks', function (Blueprint $table) {
            $table->text('target_url')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shortlinks', function (Blueprint $table) {
            $table->dropColumn('target_urls');
            $table->text('target_url')->nullable(false)->change();
        });
    }
};

