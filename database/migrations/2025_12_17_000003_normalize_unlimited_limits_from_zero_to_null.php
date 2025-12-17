<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Previously, the UI treated 0 as "Unlimited".
        // We now treat NULL as "Unlimited" and allow 0 as a valid limit value.
        DB::table('users')->where('limit_short', 0)->update(['limit_short' => null]);
        DB::table('users')->where('limit_domain', 0)->update(['limit_domain' => null]);
        DB::table('users')->where('limit_domain_check', 0)->update(['limit_domain_check' => null]);
    }

    public function down(): void
    {
        // Best-effort revert: convert NULL back to 0.
        DB::table('users')->whereNull('limit_short')->update(['limit_short' => 0]);
        DB::table('users')->whereNull('limit_domain')->update(['limit_domain' => 0]);
        DB::table('users')->whereNull('limit_domain_check')->update(['limit_domain_check' => 0]);
    }
};


