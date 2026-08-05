<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('delivery_preferences')) {
            return;
        }

        if (! Schema::hasColumn('delivery_preferences', 'event_cancellations')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('event_cancellations')->default(true)->after('user_id');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'refund_confirmations')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('refund_confirmations')->default(true)->after('event_cancellations');
            });
        }

        if (! Schema::hasColumn('delivery_preferences', 'promotional_offers')) {
            Schema::table('delivery_preferences', function (Blueprint $table) {
                $table->boolean('promotional_offers')->default(false)->after('refund_confirmations');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('delivery_preferences')) {
            return;
        }

        foreach (['event_cancellations', 'refund_confirmations', 'promotional_offers'] as $column) {
            if (Schema::hasColumn('delivery_preferences', $column)) {
                Schema::table('delivery_preferences', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};