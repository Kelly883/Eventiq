<?php

namespace Tests\Feature;

use App\Features\CheckIn\Models\CheckIn;
use App\Features\Checkout\Models\Ticket;
use App\Features\Checkout\Models\Order;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Tests\TestCase;

class TicketPurgeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureSchema();
    }

    public function test_admin_can_purge_ticket_and_preserve_checkins(): void
    {
        $adminRole = \App\Models\Role::create(['name' => 'admin']);
        $user = \App\Models\User::factory()->create();
        $user->roles()->attach($adminRole);
        
        $event = \App\Models\Event::factory()->create();
        $tier = \App\Models\TicketTier::factory()->create(['event_id' => $event->id]);
        $order = Order::factory()->create(['event_id' => $event->id]);
        
        $ticket = new Ticket([
            'order_id' => $order->id,
            'event_id' => $event->id,
            'user_id' => $user->id,
            'ticket_tier_id' => $tier->id,
            'attendee_name' => 'John Doe',
            'attendee_email' => 'john@example.com',
            'tier' => 'General',
            'status' => 'valid',
            'checked_in' => false,
        ]);
        $ticket->id = (string) Str::uuid();
        $ticket->save();

        $checkIn = new CheckIn([
            'ticket_id' => $ticket->id,
            'user_id' => $ticket->user_id,
            'checked_in_at' => now(),
        ]);
        $checkIn->save();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/admin/tickets/{$ticket->id}/purge", [
                'reason' => 'GDPR request',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'check_ins_preserved' => 1,
            ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'purged',
            'attendee_name' => null,
            'attendee_email' => null,
        ]);

        $this->assertDatabaseHas('check_ins', [
            'ticket_id' => $ticket->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ticket.purged',
            'entity' => 'ticket',
            'entity_id' => $ticket->id,
        ]);
    }

    public function test_purge_requires_admin_role(): void
    {
        $attendeeRole = \App\Models\Role::create(['name' => 'attendee']);
        $user = \App\Models\User::factory()->create();
        $user->roles()->attach($attendeeRole);
        
        $event = \App\Models\Event::factory()->create();
        $tier = \App\Models\TicketTier::factory()->create(['event_id' => $event->id]);
        $order = Order::factory()->create(['event_id' => $event->id]);
        
        $ticket = new Ticket([
            'order_id' => $order->id,
            'event_id' => $event->id,
            'user_id' => $user->id,
            'ticket_tier_id' => $tier->id,
            'status' => 'valid',
        ]);
        $ticket->id = (string) Str::uuid();
        $ticket->save();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/admin/tickets/{$ticket->id}/purge");

        $response->assertStatus(403);
    }

    public function test_command_dry_run_does_not_modify_data(): void
    {
        $event = \App\Models\Event::factory()->create();
        $tier = \App\Models\TicketTier::factory()->create(['event_id' => $event->id]);
        $order = Order::factory()->create(['event_id' => $event->id]);
        
        $ticket = new Ticket([
            'order_id' => $order->id,
            'event_id' => $event->id,
            'user_id' => \App\Models\User::factory()->create()->id,
            'ticket_tier_id' => $tier->id,
            'attendee_name' => 'Jane Doe',
            'status' => 'valid',
        ]);
        $ticket->id = (string) Str::uuid();
        $ticket->save();

        $this->artisan('tickets:purge', [
            '--event_id' => $event->id,
            '--dry-run' => true,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'attendee_name' => 'Jane Doe',
            'status' => 'valid',
        ]);
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

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->json('permissions')->nullable();
            $table->boolean('isSystemRole')->default(false);
            $table->timestamps();
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('organizers', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->string('business_name')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizer_id')->constrained('organizers')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_datetime');
            $table->timestamp('end_datetime');
            $table->string('venue_name')->nullable();
            $table->string('venue_address')->nullable();
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

        Schema::create('ticket_inventory', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('ticket_tier_id')->constrained('ticket_tiers')->cascadeOnDelete();
            $table->integer('total_allocated')->default(0);
            $table->integer('total_sold')->default(0);
            $table->integer('low_stock_threshold')->nullable();
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
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('user_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('ticket_tier_id');
            $table->text('qr_code_data')->nullable();
            $table->string('status')->default('valid');
            $table->timestamp('checked_in_at')->nullable();
            $table->boolean('checked_in')->default(false);
            $table->string('checked_in_by')->nullable();
            $table->string('ticket_id')->nullable();
            $table->string('attendee_name')->nullable();
            $table->string('attendee_email')->nullable();
            $table->string('tier')->nullable();
            $table->string('qr_code_secret')->nullable();
            $table->timestamp('qr_code_generated_at')->nullable();
            $table->timestamp('qr_code_expires_at')->nullable();
            $table->integer('qr_code_scanned_count')->default(0);
            $table->timestamp('last_qr_scan_at')->nullable();
            $table->timestamp('first_scanned_at')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('ticket_tier_id')->references('id')->on('ticket_tiers')->onDelete('cascade');
            $table->foreign('checked_in_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_in_at');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('adminId')->nullable();
            $table->uuid('targetUserId')->nullable();
            $table->string('action');
            $table->string('entity')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->text('changes')->nullable();
            $table->string('request_id')->nullable();
            $table->text('oldValue')->nullable();
            $table->text('newValue')->nullable();
            $table->text('reason')->nullable();
            $table->uuid('event_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->uuid('ticket_id')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();

            $table->foreign('adminId')->references('id')->on('users')->nullOnDelete();
            $table->foreign('targetUserId')->references('id')->on('users')->nullOnDelete();
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('set null');
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
            $table->string('peak_sales_hour')->nullable();
            $table->foreignId('top_ticket_tier_id')->nullable()->constrained('ticket_tiers')->nullOnDelete();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('events_calendar_summary', function (Blueprint $table) {
            $table->date('event_date');
            $table->integer('total_events')->default(0);
            $table->integer('total_capacity')->default(0);
            $table->integer('published_events')->default(0);
            $table->integer('published_capacity')->default(0);
            $table->integer('draft_events')->default(0);
            $table->integer('cancelled_events')->default(0);
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamps();
        });
    }
}
