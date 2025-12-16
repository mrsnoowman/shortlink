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
        Schema::table('users', function (Blueprint $table) {
            /**
             * Ubah kolom `role` dari ENUM menjadi STRING biasa supaya
             * kita bisa menggunakan nilai baru seperti `toko` / `owner`
             * tanpa dibatasi oleh daftar enum lama (`user`, `admin`, `master`).
             */
            $table->string('role', 50)
                ->default('user')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kembalikan ke enum awal jika diperlukan.
            $table->enum('role', ['user', 'admin', 'master'])
                ->default('user')
                ->change();
        });
    }
};


