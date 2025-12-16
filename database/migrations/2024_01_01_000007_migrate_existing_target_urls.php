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
        // Migrate existing target_urls (JSON) to target_urls table
        $shortlinks = DB::table('shortlinks')->whereNotNull('target_urls')->get();
        
        foreach ($shortlinks as $shortlink) {
            $urls = json_decode($shortlink->target_urls, true);
            
            if (is_array($urls) && !empty($urls)) {
                foreach ($urls as $url) {
                    if (!empty($url)) {
                        DB::table('target_urls')->insert([
                            'shortlink_id' => $shortlink->id,
                            'url' => $url,
                            'is_blocked' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
        
        // Also migrate single target_url if no target_urls exist
        $shortlinksWithoutUrls = DB::table('shortlinks')
            ->whereNull('target_urls')
            ->whereNotNull('target_url')
            ->get();
            
        foreach ($shortlinksWithoutUrls as $shortlink) {
            if (!empty($shortlink->target_url)) {
                DB::table('target_urls')->insert([
                    'shortlink_id' => $shortlink->id,
                    'url' => $shortlink->target_url,
                    'is_blocked' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is one-way, data migration only
    }
};

