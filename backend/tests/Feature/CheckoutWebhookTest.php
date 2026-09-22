<?php

namespace Tests\Feature;

use App\Features\Delivery\Jobs\SendTicketDeliveryJob;
use App\Features\Payment\Services\FlutterwaveService;
use App\Features\Payment\Services\PaystackService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class CheckoutWebhookTest extends TestCase
{
    use DatabaseTransactions;
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureSchema();
    }

    public function test_paystack_webhook_success_marks_order_paid_and_issues_tickets(): void
    {
        Queue::fake();

        $reference = 'ps-ref-success-' . Str::lower(Str::random(8));
        $seed = $this->seedCheckoutGraph($reference, 2);

        $paystack = Mockery::mock(PaystackService::class);
        $paystack->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
        $paystack->shouldReceive('verifyTransaction')->once()->with($reference)->andReturn([
            'status' => 'success',
            'id' => 'trx_success_123',
        ]);
        $this->app->instance(PaystackService::class, $paystack);

        $response = $this->postJson('/api/webhooks/payment-provider', [
            'event' => 'charge.success',
            'data' => [
                'reference' => $reference,
            ],
        ], [
            'x-paystack-signature' => 'valid-signature',
        ]);

        $response->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $seed['order_id'],
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $seed['order_id'],
            'status' => 'success',
        ]);

        $this->assertSame(2, DB::table('tickets')->where('order_id', $seed['order_id'])->count());
        $this->assertSame(2, DB::table('tickets')->where('order_id', $seed['order_id'])->whereNotNull('qr_code_data')->count());
        $this->assertSame(2, (int) DB::table('ticket_tiers')->where('id', $seed['ticket_tier_id'])->value('sold_count'));
        $this->assertSame(2, (int) DB::table('ticket_inventory')->where('ticket_tier_id', $seed['ticket_tier_id'])->value('total_sold'));

        Queue::assertPushed(SendTicketDeliveryJob::class, 1);
    }

    public function test_paystack_webhook_failed_verification_marks_order_failed_without_issuing_tickets(): void
    {
        Queue::fake();

        $reference = 'ps-ref-failed-' . Str::lower(Str::random(8));
        $seed = $this->seedCheckoutGraph($reference, 1);

        $paystack = Mockery::mock(PaystackService::class);
        $paystack->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
        $paystack->shouldReceive('verifyTransaction')->once()->with($reference)->andReturn([
            'status' => 'failed',
            'id' => 'trx_failed_123',
        ]);
        $this->app->instance(PaystackService::class, $paystack);

        $response = $this->postJson('/api/webhooks/payment-provider', [
            'event' => 'charge.failed',
            'data' => [
                'reference' => $reference,
            ],
        ], [
            'x-paystack-signature' => 'valid-signature',
        ]);

        $response->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $seed['order_id'],
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => $seed['order_id'],
            'status' => 'failed',
        ]);

        $this->assertSame(0, DB::table('tickets')->where('order_id', $seed['order_id'])->count());
        Queue::assertNothingPushed();
    }

    public function test_duplicate_webhook_event_is_only_verified_and_processed_once(): void
    {
        Queue::fake();

        $reference = 'ps-ref-dedup-' . Str::lower(Str::random(8));
        $seed = $this->seedCheckoutGraph($reference, 1);

        $paystack = Mockery::mock(PaystackService::class);
        $paystack->shouldReceive('verifyWebhookSignature')->twice()->andReturn(true);
        // Only the first delivery should hit the verify API - the second one
        // must be dropped by the event-id dedup check.
        $paystack->shouldReceive('verifyTransaction')->once()->with($reference)->andReturn([
            'status' => 'abandoned',
            'id' => 'trx_dedup_1',
        ]);
        $this->app->instance(PaystackService::class, $paystack);

        $payload = [
            'event' => 'charge.abandoned',
            'data' => [
                'reference' => $reference,
                'event' => ['id' => 'evt-dedup-777'],
            ],
        ];

        $first = $this->postJson('/api/webhooks/payment-provider', $payload, [
            'x-paystack-signature' => 'valid-signature',
        ]);
        $first->assertOk()->assertJson(['received' => true]);

        $second = $this->postJson('/api/webhooks/payment-provider', $payload, [
            'x-paystack-signature' => 'valid-signature',
        ]);
        $second->assertOk()->assertJson(['received' => true]);

        // First delivery persisted the event id on the payment row.
        $this->assertSame(
            'evt-dedup-777',
            DB::table('payments')->where('order_id', $seed['order_id'])->value('webhook_event_id')
        );

        // State from the first delivery must not be re-applied.
        $this->assertDatabaseHas('orders', ['id' => $seed['order_id'], 'status' => 'abandoned']);
    }

    public function test_refund_event_on_completed_order_voids_tickets_and_marks_refunded(): void
    {
        Queue::fake();

        $reference = 'ps-ref-refund-' . Str::lower(Str::random(8));
        $seed = $this->seedCheckoutGraph($reference, 2);

        // First: a successful charge that completes the order and issues tickets.
        $paystack = Mockery::mock(PaystackService::class);
        $paystack->shouldReceive('verifyWebhookSignature')->twice()->andReturn(true);
        $paystack->shouldReceive('verifyTransaction')->once()->with($reference)->andReturn([
            'status' => 'success',
            'id' => 'trx_refunded_1',
        ]);
        $this->app->instance(PaystackService::class, $paystack);

        $this->postJson('/api/webhooks/payment-provider', [
            'event' => 'charge.success',
            'data' => ['reference' => $reference],
        ], ['x-paystack-signature' => 'valid-signature'])->assertOk();

        $this->assertDatabaseHas('orders', ['id' => $seed['order_id'], 'status' => 'completed']);

        // Then: a refund event for that same (completed) order. It must NOT
        // be swallowed by the completed-order early return, and must not
        // trigger another gateway verification call.
        $response = $this->postJson('/api/webhooks/payment-provider', [
            'event' => 'charge.refund',
            'data' => [
                'reference' => $reference,
                'amount' => 3000000, // kobo = 30000.00 NGN = full amount
                'reason' => 'customer_request',
            ],
        ], ['x-paystack-signature' => 'valid-signature']);

        $response->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseHas('orders', ['id' => $seed['order_id'], 'status' => 'refunded']);
        $this->assertDatabaseHas('payments', [
            'order_id' => $seed['order_id'],
            'status' => 'refunded',
            'is_fully_refunded' => 1,
        ]);

        $refundedAmount = (float) DB::table('payments')->where('order_id', $seed['order_id'])->value('refunded_amount');
        $this->assertSame(30000.0, $refundedAmount);
    }

    public function test_flutterwave_webhook_success_marks_order_paid_and_issues_tickets(): void
    {
        Queue::fake();

        $reference = 'flw-ref-success-' . Str::lower(Str::random(8));
        $transactionId = 'flw_trx_' . rand(10000, 99999);
        $seed = $this->seedCheckoutGraph($reference, 2, 'flutterwave');

        $flutterwave = Mockery::mock(FlutterwaveService::class);
        $flutterwave->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
        $flutterwave->shouldReceive('verifyTransaction')->once()->with($transactionId)->andReturn([
            'status' => 'successful',
            'id' => $transactionId,
        ]);
        $this->app->instance(FlutterwaveService::class, $flutterwave);

        $response = $this->postJson('/api/webhooks/payment-provider', [
            'event' => 'charge.completed',
            'data' => [
                'tx_ref' => $reference,
                'id' => $transactionId,
            ],
        ], [
            'verif-hash' => 'valid-hash',
        ]);

        $response->assertOk()->assertJson(['received' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $seed['order_id'],
            'status' => 'completed',
        ]);

        $this->assertSame(2, DB::table('tickets')->where('order_id', $seed['order_id'])->count());
        Queue::assertPushed(SendTicketDeliveryJob::class, 1);
    }

    public function test_paystack_webhook_for_expired_order_with_sold_out_inventory_refunds(): void
    {
        Queue::fake();

        $reference = 'ps-ref-expired-' . Str::lower(Str::random(8));
        $seed = $this->seedCheckoutGraph($reference, 1);

        // Mark order as expired (simulating ExpirePendingOrders command)
        DB::table('orders')->where('id', $seed['order_id'])->update(['status' => 'expired']);
        // Exhaust inventory so the expired order cannot be fulfilled
        DB::table('ticket_tiers')->where('id', $seed['ticket_tier_id'])->update(['sold_count' => 50]);

        $paystack = Mockery::mock(PaystackService::class);
        $paystack->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
        $paystack->shouldReceive('verifyTransaction')->once()->with($reference)->andReturn([
            'status' => 'success',
            'id' => 'trx_expired_123',
            'amount' => 1500000, // kobo = 15000.00 NGN
        ]);
        // Mock a successful refund (since inventory is sold out)
        $paystack->shouldReceive('refund')->once()->andReturn([
            'id' => 'refund_123',
            'status' => 'success',
        ]);
        $this->app->instance(PaystackService::class, $paystack);

        $response = $this->postJson('/api/webhooks/payment-provider', [
            'event' => 'charge.success',
            'data' => [
                'reference' => $reference,
            ],
        ], [
            'x-paystack-signature' => 'valid-signature',
        ]);

        $response->assertOk();

        // Order should be refunded, not completed
        $orderStatus = DB::table('orders')->where('id', $seed['order_id'])->value('status');
        $this->assertEquals('refunded', $orderStatus);

        // No tickets should be issued
        $this->assertSame(0, DB::table('tickets')->where('order_id', $seed['order_id'])->count());
    }

    public function test_paystack_webhook_amount_mismatch_returns_422(): void
    {
        Queue::fake();

        $reference = 'ps-ref-amount-' . Str::lower(Str::random(8));
        $seed = $this->seedCheckoutGraph($reference, 1);

        $paystack = Mockery::mock(PaystackService::class);
        $paystack->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
        $paystack->shouldReceive('verifyTransaction')->once()->with($reference)->andReturn([
            'status' => 'success',
            'id' => 'trx_amount_123',
            'amount' => 9999900, // kobo = 99999.00 NGN — way more than 15000
        ]);
        $this->app->instance(PaystackService::class, $paystack);

        $response = $this->postJson('/api/webhooks/payment-provider', [
            'event' => 'charge.success',
            'data' => [
                'reference' => $reference,
            ],
        ], [
            'x-paystack-signature' => 'valid-signature',
        ]);

        $response->assertStatus(422);

        // Order should NOT be completed
        $this->assertDatabaseHas('orders', [
            'id' => $seed['order_id'],
            'status' => 'pending',
        ]);

        // No tickets issued
        $this->assertSame(0, DB::table('tickets')->where('order_id', $seed['order_id'])->count());
    }

    /**
     * Creates a minimal valid checkout graph used by webhook tests.
     *
     * @param string $paymentIntentId Unique reference for the payment intent
     * @param int $quantity Number of tickets to purchase
     * @param string $gateway Payment gateway ('paystack' or 'flutterwave')
     */
    private function seedCheckoutGraph(string $paymentIntentId, int $quantity, string $gateway = 'paystack'): array
    {
        $now = now();
        $userId = (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $userId,
            'name' => 'Webhook Test User',
            'email' => 'webhook-' . Str::lower(Str::random(8)) . '@example.com',
            'passwordHash' => bcrypt('password'),
            'role' => 'attendee',
            'emailVerified' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $organizerId = DB::table('organizers')->insertGetId([
            'user_id' => $userId,
            'displayName' => 'Webhook Org',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $eventId = DB::table('events')->insertGetId([
            'organizer_id' => $organizerId,
            'title' => 'Webhook Event',
            'description' => 'Integration test event',
            'start_datetime' => $now->copy()->addDays(10),
            'end_datetime' => $now->copy()->addDays(10)->addHours(2),
            'venue_name' => 'Webhook Venue',
            'capacity' => 100,
            'status' => 'published',
            'currency' => 'NGN',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $unitPrice = 15000.00;
        $ticketTierId = DB::table('ticket_tiers')->insertGetId([
            'event_id' => $eventId,
            'name' => 'VIP',
            'price' => $unitPrice,
            'min_purchase' => 1,
            'quantity' => 50,
            'status' => 'published',
            'currency' => 'NGN',
            'is_active' => 1,
            'sold_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('ticket_inventory')->insert([
            'id' => (string) Str::uuid(),
            'event_id' => $eventId,
            'ticket_tier_id' => $ticketTierId,
            'total_allocated' => 50,
            'total_sold' => 0,
            'low_stock_threshold' => 5,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $orderId = (string) Str::uuid();
        DB::table('orders')->insert([
            'id' => $orderId,
            'user_id' => $userId,
            'event_id' => $eventId,
            'status' => 'pending',
            'total_amount' => $unitPrice * $quantity,
            'currency' => 'NGN',
            'payment_gateway' => 'paystack',
            'payment_intent_id' => $paymentIntentId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('order_items')->insert([
            'id' => (string) Str::uuid(),
            'order_id' => $orderId,
            'ticket_tier_id' => $ticketTierId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('payments')->insert([
            'id' => (string) Str::uuid(),
            'order_id' => $orderId,
            'payment_intent_id' => $paymentIntentId,
            'gateway_transaction_id' => 'gw_' . Str::lower(Str::random(12)),
            'amount' => $unitPrice * $quantity,
            'currency' => 'NGN',
            'status' => 'pending',
            'gateway' => $gateway,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'user_id' => $userId,
            'event_id' => $eventId,
            'ticket_tier_id' => $ticketTierId,
            'order_id' => $orderId,
        ];
    }

    private function ensureSchema(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('passwordHash');
            $table->string('role')->default('attendee');
            $table->boolean('emailVerified')->default(false);
            $table->timestamps();
        });

        Schema::create('organizers', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->uuid('userId')->nullable()->unique();
            $table->string('displayName')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatarUrl')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->json('socialLinks')->nullable();
            $table->json('brandingColors')->nullable();
            $table->string('timezone')->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('verificationStatus')->nullable();
            $table->string('paymentDefault')->nullable();
            $table->decimal('commissionRate', 5, 2)->nullable();
            $table->boolean('isPublic')->default(true);
            $table->boolean('emailPublic')->default(false);
            $table->boolean('phonePublic')->default(false);
            $table->boolean('hideSocialLinks')->default(false);
            $table->boolean('hideBrandingColors')->default(false);
            $table->json('notificationPreferences')->nullable();
            $table->integer('totalEventsCreated')->default(0);
            $table->integer('totalTicketsSold')->default(0);
            $table->timestamps();
            $table->timestamp('deletedAt')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('userId');
            $table->index(['userId', 'isPublic']);
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
            $table->unsignedInteger('version')->default(1);
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

        Schema::create('ticket_inventory', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('ticket_tier_id')->constrained('ticket_tiers')->cascadeOnDelete();
            $table->integer('total_allocated')->default(0);
            $table->integer('total_sold')->default(0);
            $table->integer('low_stock_threshold')->nullable();
            $table->timestamps();
        });

        Schema::create('analytics_events_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->integer('total_tickets_sold')->default(0);
            $table->integer('total_page_views')->default(0);
            $table->integer('total_ticket_page_views')->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('average_ticket_price', 10, 2)->default(0);
            $table->integer('peak_sales_hour')->nullable();
            $table->foreignId('top_ticket_tier_id')->nullable()->constrained('ticket_tiers')->nullOnDelete();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->string('payment_gateway')->nullable();
            $table->string('payment_intent_id')->nullable();
            $table->string('gateway_transaction_id')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->unique('idempotency_key', 'orders_idempotency_key_unique');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->foreignId('ticket_tier_id')->constrained('ticket_tiers');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->string('payment_intent_id');
            $table->string('gateway_transaction_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('status')->default('pending');
            $table->string('gateway');
            $table->json('gateway_response')->nullable();
            // Mirror of the production schema additions: webhook dedup key
            // (unique) + refund bookkeeping written by the webhook controller.
            $table->string('webhook_event_id')->nullable();
            $table->decimal('refunded_amount', 10, 2)->default(0);
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->boolean('is_fully_refunded')->default(false);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->unique('webhook_event_id', 'payments_webhook_event_id_unique');
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->nullable();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->uuid('user_id')->nullable();
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
            $table->boolean('checked_in')->default(false);
            $table->string('checked_in_by')->nullable();
            $table->integer('qr_code_scanned_count')->default(0);
            $table->timestamp('last_qr_scan_at')->nullable();
            $table->timestamp('first_scanned_at')->nullable();
            $table->string('refund_status')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }
}
