<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_windows', function (Blueprint $table) {
            if (!Schema::hasIndex('pricing_windows', 'idx_pricing_windows_event_tier_active')) {
                $table->index(['event_id', 'ticket_category_id', 'is_active', 'deleted_at'], 'idx_pricing_windows_event_tier_active');
            }
            if (!Schema::hasIndex('pricing_windows', 'idx_pricing_windows_prioritized')) {
                $table->index(['event_id', 'priority', 'start_date_time'], 'idx_pricing_windows_prioritized');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pricing_windows', function (Blueprint $table) {
            $table->dropIndex('idx_pricing_windows_event_tier_active');
            $table->dropIndex('idx_pricing_windows_prioritized');
        });
    }
};
