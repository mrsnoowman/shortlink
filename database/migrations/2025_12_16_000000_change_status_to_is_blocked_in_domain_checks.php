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
        Schema::table('domain_checks', function (Blueprint $table) {
            // Add new is_blocked column
            $table->boolean('is_blocked')->default(false)->after('domain');
        });

        // Migrate existing status data to is_blocked
        DB::table('domain_checks')->get()->each(function ($domainCheck) {
            $isBlocked = ($domainCheck->status === 'blocked') ? 1 : 0;
            DB::table('domain_checks')
                ->where('id', $domainCheck->id)
                ->update(['is_blocked' => $isBlocked]);
        });

        // Drop old status column
        Schema::table('domain_checks', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domain_checks', function (Blueprint $table) {
            // Add back status column
            $table->string('status')->default('active')->after('domain');
        });

        // Migrate is_blocked back to status
        DB::table('domain_checks')->get()->each(function ($domainCheck) {
            $status = $domainCheck->is_blocked ? 'blocked' : 'active';
            DB::table('domain_checks')
                ->where('id', $domainCheck->id)
                ->update(['status' => $status]);
        });

        // Drop is_blocked column
        Schema::table('domain_checks', function (Blueprint $table) {
            $table->dropColumn('is_blocked');
        });
    }
};

