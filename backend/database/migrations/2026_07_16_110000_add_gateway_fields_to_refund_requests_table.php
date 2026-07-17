<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->string('payment_gateway_refund_id')->nullable()->after('approved_amount');
            $table->json('payment_gateway_response')->nullable()->after('payment_gateway_refund_id');
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn(['payment_gateway_refund_id', 'payment_gateway_response']);
        });
    }
};
