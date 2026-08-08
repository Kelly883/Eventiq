<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            if (!Schema::hasColumn('fraud_events', 'payment_intent_id')) {
                $table->string('payment_intent_id', 100)->nullable()->after('gateway_response_code')
                      ->comment('Payment gateway intent/transaction ID for chargeback reconciliation');
            }

            if (!Schema::hasColumn('fraud_events', 'chargeback_flag')) {
                $table->boolean('chargeback_flag')->default(false)->after('payment_intent_id')
                      ->comment('Whether this order resulted in a chargeback');
            }

            if (!Schema::hasColumn('fraud_events', 'authentication_method')) {
                $table->string('authentication_method', 50)->nullable()->after('chargeback_flag')
                      ->comment('3DS, password, biometric — critical for liability shift');
            }

            $table->index('payment_intent_id', 'idx_fraud_payment_intent_id');
            $table->index('chargeback_flag', 'idx_fraud_chargeback_flag');
            $table->index('authentication_method', 'idx_fraud_authentication_method');
        });
    }

    public function down(): void
    {
        Schema::table('fraud_events', function (Blueprint $table) {
            $table->dropIndex('idx_fraud_payment_intent_id');
            $table->dropIndex('idx_fraud_chargeback_flag');
            $table->dropIndex('idx_fraud_authentication_method');

            $table->dropColumn([
                'payment_intent_id',
                'chargeback_flag',
                'authentication_method',
            ]);
        });
    }
};
