<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NOTE: no code in this repo actually creates Order rows yet - the entire
 * checkout flow (CheckoutController, WebhookController, CartController)
 * is unbuilt empty stubs. This schema is inferred from the only real
 * consumer that queries it (FraudDetectionService::checkVelocity() /
 * checkDeviceFingerprint() / checkIpReputation(), which need user_id,
 * device_id, ip_address, created_at) plus the minimal obvious fields
 * for this to be a coherent, usable orders table. Revisit once the real
 * checkout flow is designed - this is a starting point, not a final
 * business schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending'); // pending|paid|cancelled|refunded
            $table->unsignedBigInteger('total_amount')->default(0); // smallest currency unit
            $table->string('currency', 3)->default('NGN');
            $table->string('payment_gateway')->nullable(); // paystack|flutterwave
            $table->string('payment_reference')->nullable();
            $table->string('device_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
