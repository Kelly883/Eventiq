<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('refund_requests')) {
            // Create the base table first so this migration can be safely run on a fresh SQLite DB.
            Schema::create('refund_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('refund_policy_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status')->default('pending'); // pending, approved, rejected, appealed
                $table->decimal('requested_amount', 10, 2);
                $table->decimal('approved_amount', 10, 2)->nullable();
                $table->text('reason')->nullable();
                $table->text('admin_notes')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();

                // Added payment gateway fields
                $table->string('payment_gateway_refund_id')->nullable();
                $table->text('payment_gateway_response')->nullable();

                $table->timestamps();
            });

            return;
        }


        Schema::table('refund_requests', function (Blueprint $table) {
            $table->string('payment_gateway_refund_id')->nullable()->after('id');
            $table->text('payment_gateway_response')->nullable()->after('payment_gateway_refund_id');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('refund_requests')) {
            return;
        }

        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn(['payment_gateway_response', 'payment_gateway_refund_id']);
        });
    }
};

