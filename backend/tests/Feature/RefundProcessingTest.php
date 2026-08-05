<?php

namespace Tests\Feature;

use App\Features\Refunds\Services\RefundService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefundProcessingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureSchema();
    }

    public function test_refund_service_approve_transitions_to_refunded_and_updates_payment_and_ticket(): void
    {
        $seed = $this->seedRefundGraph();

        $service = app(RefundService::class);
        $result = $service->approve(
            $seed['refund_request_id'],
            $seed['admin_user_id'],
            5000.00,
            'Approved in integration test'
        );

        $this->assertSame('refunded', $result->status);

        $this->assertDatabaseHas('refund_requests', [
            'id' => $seed['refund_request_id'],
            'status' => 'refunded',
            'payment_gateway_refund_id' => 'rf_12345',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $seed['order_id'],
            'status' => 'refunded',
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $seed['ticket_id'],
            'status' => 'refunded',
        ]);

        $gatewayResponse = DB::table('refund_requests')
            ->where('id', $seed['refund_request_id'])
            ->value('payment_gateway_response');
        $this->assertIsString($gatewayResponse);
        $this->assertStringContainsString('rf_12345', $gatewayResponse);
    }

    private function seedRefundGraph(): array
    {
        $now = now();

        config()->set('payment.gateways.paystack.secret_key', 'test-paystack-secret');
        config()->set('payment.gateways.paystack.public_key', 'test-paystack-public');
        config()->set('payment.gateways.paystack.payment_url', 'https://api.paystack.co');

        $userId = DB::table('users')->insertGetId([
            'name' => 'Refund User',
            'email' => 'refund-user-' . Str::lower(Str::random(8)) . '@example.com',
            'passwordHash' => bcrypt('password'),
            'role' => 'attendee',
            'emailVerified' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $adminUserId = DB::table('users')->insertGetId([
            'name' => 'Refund Admin',
            'email' => 'refund-admin-' . Str::lower(Str::random(8)) . '@example.com',
            'passwordHash' => bcrypt('password'),
            'role' => 'admin',
            'emailVerified' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $organizerId = DB::table('organizers')->insertGetId([
            'user_id' => $userId,
            'business_name' => 'Refund Org',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $eventId = DB::table('events')->insertGetId([
            'organizer_id' => $organizerId,
            'title' => 'Refund Event',
            'description' => 'Refund integration event',
            'start_datetime' => $now->copy()->addDays(5),
            'end_datetime' => $now->copy()->addDays(5)->addHours(3),
            'venue_name' => 'Refund Venue',
            'capacity' => 100,
            'status' => 'published',
            'currency' => 'NGN',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $ticketTierId = DB::table('ticket_tiers')->insertGetId([
            'event_id' => $eventId,
            'name' => 'Regular',
            'price' => 5000.00,
            'min_purchase' => 1,
            'quantity' => 30,
            'status' => 'published',
            'currency' => 'NGN',
            'is_active' => 1,
            'sold_count' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderId = DB::table('orders')->insertGetId([
            'user_id' => $userId,
            'event_id' => $eventId,
            'status' => 'completed',
            'total_amount' => 5000.00,
            'currency' => 'NGN',
            'payment_gateway' => 'paystack',
            'payment_intent_id' => 'pi_refund_' . Str::lower(Str::random(8)),
            'gateway_transaction_id' => 'trx_refund_' . Str::lower(Str::random(8)),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $ticketId = DB::table('tickets')->insertGetId([
            'order_id' => $orderId,
            'event_id' => $eventId,
            'user_id' => $userId,
            'ticket_tier_id' => $ticketTierId,
            'ticket_id' => 'TCK-' . Str::upper(Str::random(12)),
            'attendee_name' => 'Refund User',
            'attendee_email' => 'refund-ticket-' . Str::lower(Str::random(8)) . '@example.com',
            'tier' => 'Regular',
            'status' => 'valid',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('payments')->insert([
            'order_id' => $orderId,
            'payment_intent_id' => 'pi_refund_' . Str::lower(Str::random(8)),
            'gateway_transaction_id' => 'trx_refund_' . Str::lower(Str::random(8)),
            'amount' => 5000.00,
            'currency' => 'NGN',
            'status' => 'success',
            'gateway' => 'paystack',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $refundRequestId = DB::table('refund_requests')->insertGetId([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'status' => 'pending',
            'requested_amount' => 5000.00,
            'approved_amount' => null,
            'original_amount' => 5000.00,
            'refund_amount' => 5000.00,
            'refund_percentage' => 100.00,
            'reason' => 'Unable to attend',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'https://api.paystack.co/refund' => \Illuminate\Support\Facades\Http::response([
                'status' => true,
                'data' => [
                    'id' => 'rf_12345',
                    'status' => 'processed',
                ],
            ], 200),
        ]);

        return [
            'refund_request_id' => $refundRequestId,
            'order_id' => $orderId,
            'ticket_id' => $ticketId,
            'admin_user_id' => $adminUserId,
        ];
    }

    private function ensureSchema(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('passwordHash');
            $table->string('role')->default('attendee');
            $table->boolean('emailVerified')->default(false);
            $table->timestamps();
        });

        Schema::create('organizers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_datetime');
            $table->timestamp('end_datetime');
            $table->string('venue_name')->nullable();
            $table->integer('capacity')->default(100);
            $table->string('status')->default('draft');
            $table->string('currency', 3)->default('NGN');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ticket_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('min_purchase')->default(1);
            $table->integer('quantity')->default(0);
            $table->string('status')->default('draft');
            $table->string('currency', 3)->default('NGN');
            $table->boolean('is_active')->default(true);
            $table->integer('sold_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->string('payment_gateway')->nullable();
            $table->string('payment_intent_id')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ticket_tier_id')->constrained('ticket_tiers')->cascadeOnDelete();
            $table->string('ticket_id');
            $table->string('attendee_name');
            $table->string('attendee_email');
            $table->string('tier');
            $table->string('status')->default('valid');
            $table->text('qr_code_data')->nullable();
            $table->string('qr_code_secret')->nullable();
            $table->timestamp('qr_code_generated_at')->nullable();
            $table->timestamp('qr_code_expires_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->unsignedBigInteger('checked_in_by_uuid')->nullable();
            $table->integer('qr_code_scanned_count')->default(0);
            $table->timestamp('last_qr_scan_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('payment_intent_id');
            $table->string('gateway_transaction_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('status')->default('pending');
            $table->string('gateway');
            $table->json('gateway_response')->nullable();
            $table->timestamps();
        });

        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->decimal('requested_amount', 10, 2);
            $table->decimal('approved_amount', 10, 2)->nullable();
            $table->decimal('original_amount', 10, 2)->default(0);
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->decimal('refund_percentage', 5, 2)->default(0);
            $table->text('reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('payment_gateway_refund_id')->nullable();
            $table->json('payment_gateway_response')->nullable();
            $table->timestamps();
        });
    }
}
