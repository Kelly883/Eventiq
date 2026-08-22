<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('webhook_delivery_logs')) {
            if (! Schema::hasIndex('webhook_delivery_logs', 'idx_webhook_delivery_stats')) {
                $table = Schema::table('webhook_delivery_logs', function ($table) {
                    $table->index(['webhook_id', 'event', 'status', 'created_at'], 'idx_webhook_delivery_stats');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('webhook_delivery_logs') && Schema::hasIndex('webhook_delivery_logs', 'idx_webhook_delivery_stats')) {
            Schema::table('webhook_delivery_logs', function ($table) {
                $table->dropIndex('idx_webhook_delivery_stats');
            });
        }
    }
};
