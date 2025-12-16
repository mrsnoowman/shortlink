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
        Schema::create('domain_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shortlink_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('target_url_id')->nullable()->constrained('target_urls')->onDelete('cascade');
            $table->foreignId('domain_check_id')->nullable()->constrained('domain_checks')->onDelete('cascade');
            $table->string('domain')->nullable();
            $table->text('url')->nullable();
            $table->boolean('old_status')->comment('Previous is_blocked status');
            $table->boolean('new_status')->comment('New is_blocked status');
            $table->string('change_type')->comment('target_url or domain_check');
            $table->boolean('notified')->default(false)->comment('Whether notification has been sent');
            $table->timestamps();
            
            $table->index(['user_id', 'notified', 'created_at']);
            $table->index(['notified', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_status_changes');
    }
};
