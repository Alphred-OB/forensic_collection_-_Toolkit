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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['target_type', 'target_id']);
            $table->index('action_type');
            $table->index('created_at');
        });

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->index(['user_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['target_type', 'target_id']);
            $table->dropIndex(['action_type']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'read_at']);
        });
    }
};
