<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->boolean('fraud_hold')->default(false)->after('verificationStatus');
            $table->text('fraud_hold_reason')->nullable()->after('fraud_hold');
            $table->timestamp('fraud_hold_at')->nullable()->after('fraud_hold_reason');
            $table->uuid('fraud_hold_by')->nullable()->after('fraud_hold_at');
        });

        Schema::table('organizer_payouts', function (Blueprint $table) {
            $table->string('payout_idempotency_key')->nullable()->unique()->after('reference');
            $table->string('gateway_transfer_id')->nullable()->after('reference');
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropColumn([
                'fraud_hold',
                'fraud_hold_reason',
                'fraud_hold_at',
                'fraud_hold_by',
            ]);
        });

        Schema::table('organizer_payouts', function (Blueprint $table) {
            $table->dropColumn(['payout_idempotency_key', 'gateway_transfer_id']);
        });
    }
};
