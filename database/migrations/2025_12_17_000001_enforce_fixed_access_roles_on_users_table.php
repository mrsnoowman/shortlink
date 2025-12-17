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
        // Normalize any unexpected values back to 'user' before enforcing the constraint.
        DB::table('users')
            ->whereNotIn('role', ['user', 'admin', 'master'])
            ->update(['role' => 'user']);

        // Enforce fixed access levels at the DB layer.
        // NOTE: We intentionally avoid Schema::change() here because Doctrine DBAL
        // may not have enum mappings enabled in some environments.
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('user','admin','master') NOT NULL DEFAULT 'user'");
        } elseif ($driver === 'pgsql') {
            // Best-effort: keep it strict at app layer, use varchar at DB layer.
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(50)");
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'user'");
            DB::statement("UPDATE users SET role = 'user' WHERE role NOT IN ('user','admin','master')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to string (less strict).
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `role` VARCHAR(50) NOT NULL DEFAULT 'user'");
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(50)");
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'user'");
        }
    }
};


