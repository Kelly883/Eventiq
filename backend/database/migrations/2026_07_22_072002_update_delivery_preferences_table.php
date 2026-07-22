<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_preferences', function (Blueprint $table) {
            $table->boolean('event_cancellations')->default(true)->after('user_id');
            $table->boolean('refund_confirmations')->default(true)->after('event_cancellations');
            $table->boolean('promotional_offers')->default(false)->after('refund_confirmations');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_preferences', function (Blueprint $table) {
            $table->dropColumn(['event_cancellations', 'refund_confirmations', 'promotional_offers']);
        });
    }
};