<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('adminId');
            $table->uuid('user_id')->nullable()->after('event_id');
            $table->foreignId('ticket_id')->nullable()->after('user_id');
            $table->json('details')->nullable()->after('newValue');

            $table->index(['event_id', 'created_at'], 'idx_audit_event_created');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['event_id', 'user_id', 'ticket_id', 'details']);
            $table->dropIndex('idx_audit_event_created');
        });
    }
};