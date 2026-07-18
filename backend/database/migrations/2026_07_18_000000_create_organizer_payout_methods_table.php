<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores organizer bank account details for payouts via
 * PaymentGatewayService::initiatePayout(). Nigerian bank-transfer model
 * (account_number + bank_code), matching what Paystack's transfer-
 * recipient step and Flutterwave's transfer call both actually need -
 * not the PRD's original US-centric payoutMethod enum ('ach',
 * 'wire_transfer', 'check'), which doesn't apply here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizer_payout_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained()->cascadeOnDelete();
            $table->string('bank_code');
            $table->string('bank_name')->nullable(); // human-readable, for display only
            $table->string('account_number');
            $table->string('account_name')->nullable(); // resolved/verified holder name
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizer_payout_methods');
    }
};
