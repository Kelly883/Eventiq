<?php

namespace Tests\Feature;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Ticket;
use App\Features\PushNotifications\Models\PushNotificationDevice;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class QRCodeCheckInTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        RateLimiter::for('qr-generate', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('qr-verify', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('qr-check-in', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('qr-void', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('qr-sync', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('qr-bulk-check-in', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('qr-detect-duplicate', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('qr-offline-sync', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('qr-analytics', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
    }

    protected function tearDown(): void
    {
        RateLimiter::for('qr-generate', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by('127.0.0.1'));
        RateLimiter::for('qr-verify', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by('127.0.0.1'));
        RateLimiter::for('qr-check-in', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by('127.0.0.1'));
        RateLimiter::for('qr-void', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by('127.0.0.1'));
        RateLimiter::for('qr-sync', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by('127.0.0.1'));
        RateLimiter::for('qr-bulk-check-in', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by('127.0.0.1'));
        RateLimiter::for('qr-detect-duplicate', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by('127.0.0.1'));
        RateLimiter::for('qr-offline-sync', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by('127.0.0.1'));
        RateLimiter::for('qr-analytics', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('127.0.0.1'));
        parent::tearDown();
    }

    private function makeUser(string $role = 'attendee'): User
    {
        $user = User::factory()->create(['emailVerified' => true]);
        $user->organizer()->firstOrCreate([], ['displayName' => $user->name ?? 'Test User']);

        if ($role === 'admin') {
            $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator', 'isSystemRole' => true]);
            if (!$user->roles()->where('name', 'admin')->exists()) {
                $user->roles()->attach($adminRole);
            }
        } elseif ($role === 'venue_staff') {
            $staffRole = Role::firstOrCreate(['name' => 'venue_staff'], ['description' => 'Venue Staff', 'isSystemRole' => true]);
            if (!$user->roles()->where('name', 'venue_staff')->exists()) {
                $user->roles()->attach($staffRole);
            }
        } elseif ($role === 'organizer') {
            $organizerRole = Role::firstOrCreate(['name' => 'organizer'], ['description' => 'Event Organizer', 'isSystemRole' => false]);
            if (!$user->roles()->where('name', 'organizer')->exists()) {
                $user->roles()->attach($organizerRole);
            }
        }

        return $user;
    }

    private function seedTicket(User $user, ?string $eventStartDate = null, string $ticketStatus = 'valid'): Ticket
    {
        $organizer = $user->organizer ?? $user->organizer()->create(['displayName' => $user->name ?? 'Test User']);

        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'status' => 'published',
            'is_public' => true,
            'start_datetime' => $eventStartDate ?? now()->addDays(7)->toDateTimeString(),
            'end_datetime' => now()->addDays(8)->toDateTimeString(),
        ]);

        $tier = TicketTier::factory()->create([
            'event_id' => $event->id,
            'status' => 'published',
            'quantity' => 100,
            'sold_count' => 0,
            'price' => 15000.00,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'total_amount' => 15000.00,
            'currency' => 'NGN',
            'status' => 'completed',
            'payment_gateway' => 'paystack',
            'payment_intent_id' => 'pi_' . (string) Str::uuid(),
        ]);

        return Ticket::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'ticket_tier_id' => $tier->id,
            'order_id' => $order->id,
            'status' => $ticketStatus,
            'attendee_name' => $user->name,
            'attendee_email' => $user->email,
            'qr_code_generated_at' => null,
            'qr_code_expires_at' => null,
        ]);
    }

    private function generateEncryptedPayload(Ticket $ticket): string
    {
        $nonce = bin2hex(random_bytes(32));
        $payload = [
            'ticket_id' => $ticket->id,
            'event_id' => $ticket->event_id,
            'nonce' => $nonce,
            'scanned_count' => 0,
            'generated_at' => now()->toIso8601String(),
        ];
        $payload['signature'] = \App\Features\QRCodeTicketing\Services\QRCodeEncryptionService::sign($payload);

        return \App\Features\QRCodeTicketing\Services\QRCodeEncryptionService::encrypt($payload);
    }

    // ------------------------------------------------------------------
    // POST /api/tickets/generate-qr
    // ------------------------------------------------------------------

    public function test_generate_qr_requires_authentication(): void
    {
        $this->postJson('/api/tickets/generate-qr', ['ticket_id' => 'some-id'])
            ->assertUnauthorized();
    }

    public function test_generate_qr_creates_qr_code_and_stores_encrypted_data(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/generate-qr', ['ticket_id' => $ticket->id]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data' => ['ticket_id', 'qr_code_data', 'expires_at']]);

        $this->assertNotNull($ticket->fresh()->qr_code_data);
        $this->assertNotNull($ticket->fresh()->qr_code_generated_at);
        $this->assertNotNull($ticket->fresh()->qr_code_expires_at);
    }

    public function test_generate_qr_forbidden_for_non_owner(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $ticket = $this->seedTicket($owner);

        $this->actingAs($other, 'sanctum')
            ->postJson('/api/tickets/generate-qr', ['ticket_id' => $ticket->id])
            ->assertForbidden();
    }

    public function test_generate_qr_returns_422_for_nonexistent_ticket(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/generate-qr', ['ticket_id' => 'nonexistent']);

        // exists validation returns 422 for non-existent ticket
        $response->assertStatus(422);
    }

    public function test_generate_qr_rejects_void_ticket(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user, null, 'void');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/generate-qr', ['ticket_id' => $ticket->id])
            ->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // POST /api/tickets/verify-qr (user-facing)
    // ------------------------------------------------------------------

    public function test_verify_qr_requires_authentication(): void
    {
        $this->postJson('/api/tickets/verify-qr', ['qr_code_data' => 'some-data'])
            ->assertUnauthorized();
    }

    public function test_verify_qr_decrypts_payload_and_returns_ticket_details(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);
        $encryptedPayload = $this->generateEncryptedPayload($ticket);

        // Generate QR first
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/generate-qr', ['ticket_id' => $ticket->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/verify-qr', ['qr_code_data' => $encryptedPayload]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data' => ['ticket_id', 'ticket_reference', 'status']]);
    }

    public function test_verify_qr_rejects_expired_qr_code(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        // Generate QR then manually expire it
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/generate-qr', ['ticket_id' => $ticket->id]);

        $ticket->update(['qr_code_expires_at' => now()->subDay()]);

        $encryptedPayload = $this->generateEncryptedPayload($ticket);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/verify-qr', ['qr_code_data' => $encryptedPayload]);

        $response->assertStatus(410)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'QR code expired');
    }

    public function test_verify_qr_rejects_already_checked_in_ticket(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/generate-qr', ['ticket_id' => $ticket->id]);

        // Check the ticket in first
        $staff = $this->makeUser('venue_staff');
        $ticket->event->venueStaff()->attach($staff->id);

        $this->actingAs($staff, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/check-in', []);

        $encryptedPayload = $this->generateEncryptedPayload($ticket);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/verify-qr', ['qr_code_data' => $encryptedPayload]);

        $response->assertOk()
            ->assertJsonPath('data.checked_in', true)
            ->assertJsonPath('data.status', 'checked_in');
    }

    public function test_verify_qr_rejects_void_ticket(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user, null, 'void');

        $encryptedPayload = $this->generateEncryptedPayload($ticket);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/verify-qr', ['qr_code_data' => $encryptedPayload]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_verify_qr_rejects_fraud_flagged_ticket(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        \App\Features\Fraud\Models\FraudEvent::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'fraud_type' => 'duplicate_checkin',
            'status' => 'auto_blocked',
        ]);

        $encryptedPayload = $this->generateEncryptedPayload($ticket);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/verify-qr', ['qr_code_data' => $encryptedPayload]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_verify_qr_forbidden_for_non_owner(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $ticket = $this->seedTicket($owner);

        $encryptedPayload = $this->generateEncryptedPayload($ticket);

        $this->actingAs($other, 'sanctum')
            ->postJson('/api/tickets/verify-qr', ['qr_code_data' => $encryptedPayload])
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // POST /api/tickets/:ticketId/check-in
    // ------------------------------------------------------------------

    public function test_check_in_requires_authentication(): void
    {
        $this->postJson('/api/tickets/some-id/check-in', [])
            ->assertUnauthorized();
    }

    public function test_check_in_requires_venue_staff_role(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/check-in', [])
            ->assertForbidden();
    }

    public function test_check_in_marks_ticket_as_checked_in(): void
    {
        $user = $this->makeUser();
        $staff = $this->makeUser('venue_staff');
        $ticket = $this->seedTicket($user);
        $ticket->event->venueStaff()->attach($staff->id);

        $response = $this->actingAs($staff, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/check-in', []);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'checked_in');

        $this->assertEquals('checked_in', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->checked_in_at);
        $this->assertEquals($staff->id, $ticket->fresh()->checked_in_by);
    }

    public function test_check_in_is_idempotent(): void
    {
        $user = $this->makeUser();
        $staff = $this->makeUser('venue_staff');
        $ticket = $this->seedTicket($user);
        $ticket->event->venueStaff()->attach($staff->id);

        // First check-in
        $this->actingAs($staff, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/check-in', [])
            ->assertOk();

        $firstCheckInAt = $ticket->fresh()->checked_in_at;
        $firstScanCount = $ticket->fresh()->qr_code_scanned_count;

        // Second check-in (should succeed but not double-count)
        $response = $this->actingAs($staff, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/check-in', []);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('is_duplicate', true);

        $this->assertEquals($firstCheckInAt->toDateTimeString(), $ticket->fresh()->checked_in_at->toDateTimeString());
    }

    public function test_check_in_returns_404_for_nonexistent_ticket(): void
    {
        $staff = $this->makeUser('venue_staff');

        $this->actingAs($staff, 'sanctum')
            ->postJson('/api/tickets/nonexistent-id/check-in', [])
            ->assertNotFound();
    }

    public function test_check_in_creates_audit_log(): void
    {
        $user = $this->makeUser();
        $staff = $this->makeUser('venue_staff');
        $ticket = $this->seedTicket($user);
        $ticket->event->venueStaff()->attach($staff->id);

        $this->actingAs($staff, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/check-in', []);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'check_in',
            'target_id' => $ticket->id,
            'user_id' => $staff->id,
        ]);
    }

    // ------------------------------------------------------------------
    // POST /api/tickets/:ticketId/void
    // ------------------------------------------------------------------

    public function test_void_requires_authentication(): void
    {
        $this->postJson('/api/tickets/some-id/void', ['reason' => 'test'])
            ->assertUnauthorized();
    }

    public function test_void_requires_organizer_or_admin_role(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/void', ['reason' => 'test'])
            ->assertForbidden();
    }

    public function test_void_marks_ticket_as_void(): void
    {
        $user = $this->makeUser('admin');
        $ticket = $this->seedTicket($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/void', ['reason' => 'Fraudulent purchase']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'void');

        $this->assertEquals('void', $ticket->fresh()->status);
    }

    public function test_void_creates_audit_log(): void
    {
        $user = $this->makeUser('organizer');
        $ticket = $this->seedTicket($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/void', ['reason' => 'Test void']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ticket_voided',
            'target_id' => $ticket->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_void_creates_blocked_delivery_event(): void
    {
        $user = $this->makeUser('organizer');
        $ticket = $this->seedTicket($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/void', ['reason' => 'Test void']);

        $this->assertDatabaseHas('delivery_events', [
            'ticket_id' => $ticket->id,
            'status' => 'blocked',
        ]);
    }

    // ------------------------------------------------------------------
    // GET /api/venue/check-in/sync
    // ------------------------------------------------------------------

    public function test_sync_requires_authentication(): void
    {
        $this->getJson('/api/venue/check-in/sync')
            ->assertUnauthorized();
    }

    public function test_sync_requires_venue_staff_role(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/venue/check-in/sync')
            ->assertForbidden();
    }

    public function test_sync_returns_check_ins_with_counters(): void
    {
        $user = $this->makeUser();
        $staff = $this->makeUser('venue_staff');
        $ticket1 = $this->seedTicket($user);
        $ticket2 = $this->seedTicket($user);
        $ticket2->update(['event_id' => $ticket1->event_id]);
        $ticket1->event->venueStaff()->attach($staff->id);

        // Check in one ticket
        $this->actingAs($staff, 'sanctum')
            ->postJson('/api/tickets/' . $ticket1->id . '/check-in', []);

        $response = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/venue/check-in/sync?event_id=' . $ticket1->event_id);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'check_ins',
                    'counters' => ['total_capacity', 'total_checked_in', 'remaining_capacity'],
                ],
                'synced_at',
            ]);

        $this->assertEquals(1, $response->json('data.counters.total_checked_in'));
        $this->assertEquals(2, $response->json('data.counters.total_capacity'));
        $this->assertEquals(1, $response->json('data.counters.remaining_capacity'));
    }

    public function test_sync_incremental_with_last_sync_at(): void
    {
        $user = $this->makeUser();
        $staff = $this->makeUser('venue_staff');
        $ticket1 = $this->seedTicket($user);
        $ticket2 = $this->seedTicket($user);
        $ticket2->update(['event_id' => $ticket1->event_id]);
        $ticket1->event->venueStaff()->attach($staff->id);

        // Check in first ticket
        $this->actingAs($staff, 'sanctum')
            ->postJson('/api/tickets/' . $ticket1->id . '/check-in', []);

        $lastSync = now()->toDateTimeString();

        // Wait a moment, then check in second ticket
        sleep(1);
        $this->actingAs($staff, 'sanctum')
            ->postJson('/api/tickets/' . $ticket2->id . '/check-in', []);

        $response = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/venue/check-in/sync?event_id=' . $ticket1->event_id . '&last_sync_at=' . $lastSync);

        $response->assertOk();
        // Should only return the second check-in
        $this->assertCount(1, $response->json('data.check_ins'));
        $this->assertEquals($ticket2->id, $response->json('data.check_ins.0.id'));
    }

    // ------------------------------------------------------------------
    // GET /api/organizer/events/:eventId/check-in-analytics
    // ------------------------------------------------------------------

    public function test_analytics_requires_authentication(): void
    {
        $this->getJson('/api/organizer/events/some-id/check-in-analytics')
            ->assertUnauthorized();
    }

    public function test_analytics_returns_correct_metrics(): void
    {
        $user = $this->makeUser('organizer');
        $staff = $this->makeUser('venue_staff');
        $ticket1 = $this->seedTicket($user);
        $ticket2 = $this->seedTicket($user);
        $ticket3 = $this->seedTicket($user);
        // Make all tickets part of the same event
        $ticket2->update(['event_id' => $ticket1->event_id]);
        $ticket3->update(['event_id' => $ticket1->event_id]);
        $ticket1->event->venueStaff()->attach($staff->id);

        // Check in 2 of 3 tickets
        $this->actingAs($staff, 'sanctum')
            ->postJson('/api/tickets/' . $ticket1->id . '/check-in', []);
        $this->actingAs($staff, 'sanctum')
            ->postJson('/api/tickets/' . $ticket2->id . '/check-in', []);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/organizer/events/' . $ticket1->event_id . '/check-in-analytics');

        $response->assertOk()
            ->assertJsonPath('data.total_checked_in', 2)
            ->assertJsonPath('data.total_tickets', 3)
            ->assertJsonPath('data.check_in_rate', 66.7)
            ->assertJsonStructure([
                'data' => [
                    'event_id', 'event_title', 'total_checked_in', 'total_tickets',
                    'check_in_rate', 'peak_check_in_hour', 'average_check_in_time_minutes',
                    'check_ins_by_hour', 'check_ins_by_tier', 'generated_at',
                ],
            ]);
    }

    public function test_analytics_returns_404_for_nonexistent_event(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/organizer/events/nonexistent-id/check-in-analytics')
            ->assertNotFound();
    }

    public function test_analytics_forbidden_for_non_owner(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $ticket = $this->seedTicket($owner);

        $this->actingAs($other, 'sanctum')
            ->getJson('/api/organizer/events/' . $ticket->event_id . '/check-in-analytics')
            ->assertForbidden();
    }

    public function test_analytics_empty_when_no_check_ins(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/organizer/events/' . $ticket->event_id . '/check-in-analytics');

        $response->assertOk()
            ->assertJsonPath('data.total_checked_in', 0)
            ->assertJsonPath('data.check_in_rate', 0)
            ->assertJsonPath('data.peak_check_in_hour', 0);
    }

    // ------------------------------------------------------------------
    // Full flow test
    // ------------------------------------------------------------------

    public function test_full_qr_check_in_flow(): void
    {
        $user = $this->makeUser('admin');
        $staff = $this->makeUser('venue_staff');
        $ticket = $this->seedTicket($user);
        $ticket->event->venueStaff()->attach($staff->id);

        // 1. Generate QR
        $genResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/generate-qr', ['ticket_id' => $ticket->id]);
        $genResponse->assertOk();
        $encryptedPayload = $genResponse->json('data.qr_code_data');

        // 2. Verify QR (user-facing)
        $verifyResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/verify-qr', ['qr_code_data' => $encryptedPayload]);
        $verifyResponse->assertOk()->assertJsonPath('success', true);

        // 3. Check in
        $checkInResponse = $this->actingAs($staff, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/check-in', []);
        $checkInResponse->assertOk()->assertJsonPath('data.status', 'checked_in');

        // 4. Try checking in again (idempotent)
        $duplicateResponse = $this->actingAs($staff, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/check-in', []);
        $duplicateResponse->assertOk()->assertJsonPath('is_duplicate', true);

        // 5. Sync check-ins
        $syncResponse = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/venue/check-in/sync?event_id=' . $ticket->event_id);
        $syncResponse->assertOk();
        $this->assertEquals(1, $syncResponse->json('data.counters.total_checked_in'));

        // 6. View analytics
        $analyticsResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/organizer/events/' . $ticket->event_id . '/check-in-analytics');
        $analyticsResponse->assertOk()
            ->assertJsonPath('data.total_checked_in', 1)
            ->assertJsonPath('data.check_in_rate', 100);
    }
}
