<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Normalize old \"toko\" role values to \"admin\" so we don't show \"Toko\" anywhere.
        DB::table('users')
            ->where('role', 'toko')
            ->update(['role' => 'admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: revert admin without organization back to toko (best-effort only).
        DB::table('users')
            ->where('role', 'admin')
            ->whereNull('role_id')
            ->update(['role' => 'toko']);
    }
};


