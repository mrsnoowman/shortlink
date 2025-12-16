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
            $table->integer('telegram_interval_minutes')->default(5)->after('telegram_chat_id')
                ->comment('Interval in minutes for sending Telegram notifications');
            $table->timestamp('last_telegram_notified_at')->nullable()->after('telegram_interval_minutes')
                ->comment('Last time Telegram notification was sent to this user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram_interval_minutes', 'last_telegram_notified_at']);
        });
    }
};
