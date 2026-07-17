<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log of payment gateway attempts/outcomes - separate from `orders`,
 * which is the order record itself. An order can have multiple payment
 * attempts (e.g. a failed one followed by a successful retry).
 *
 * PRD's schema calls the reference column "payment_intent_id" (Stripe
 * terminology), but this app uses Paystack/Flutterwave - renamed to
 * gateway_reference to hold Paystack's `reference` or Flutterwave's
 * `tx_ref`/transaction ID instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway_reference');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('status')->default('pending'); // pending|success|failed
            $table->string('gateway'); // paystack|flutterwave
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index('gateway_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
