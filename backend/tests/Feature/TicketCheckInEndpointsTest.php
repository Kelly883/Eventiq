<?php

namespace Tests\Feature;

use App\Features\Checkout\Models\Ticket;
use App\Models\User;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;

class TicketCheckInEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a venue staff user
        $this->venueStaff = User::factory()->create();
        $this->venueStaff->assignRole('venue_staff');
        // Create an organizer user
        $this->organizer = User::factory()->create();
        $this->organizer->assignRole('organizer');
        // Create an event owned by organizer
        $this->event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        // Create a valid ticket for the event
        $this->ticket = Ticket::factory()->create([
            'event_id' => $this->event->id,
            'status' => 'valid',
            'checked_in' => false,
        ]);
    }

    /** @test */
    public function venue_staff_can_check_in_a_ticket()
    {
        $payload = [
            'qr_code_data' => $this->createEncryptedPayload($this->ticket),
        ];
        $response = $this->actingAs($this->venueStaff, 'api')
            ->postJson('/api/venue/check-in/qr', $payload);
        $response->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('tickets', [
            'id' => $this->ticket->id,
            'status' => 'checked_in',
            'checked_in' => true,
        ]);
    }

    /** @test */
    public function venue_staff_cannot_check_in_already_checked_in_ticket()
    {
        $this->ticket->update(['status' => 'checked_in', 'checked_in' => true, 'checked_in_at' => now()]);
        $payload = [
            'qr_code_data' => $this->createEncryptedPayload($this->ticket),
        ];
        $response = $this->actingAs($this->venueStaff, 'api')
            ->postJson('/api/venue/check-in/qr', $payload);
        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Ticket already checked in.');
    }

    /** @test */
    public function organizer_can_void_a_ticket()
    {
        $data = [
            'reason' => 'Test void',
        ];
        $response = $this->actingAs($this->organizer, 'api')
            ->postJson("/api/tickets/{$this->ticket->id}/void", $data);
        $response->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('tickets', [
            'id' => $this->ticket->id,
            'status' => 'void',
        ]);
    }

    /** @test */
    public function sync_check_ins_returns_incremental_results()
    {
        // Perform a check‑in first
        $this->venueStaff->assignRole('venue_staff');
        $payload = [
            'qr_code_data' => $this->createEncryptedPayload($this->ticket),
        ];
        $this->actingAs($this->venueStaff, 'api')->postJson('/api/venue/check-in/qr', $payload);

        $response = $this->actingAs($this->venueStaff, 'api')
            ->getJson('/api/venue/check-in/sync?event_id=' . $this->event->id);
        $response->assertOk()
            ->assertJsonStructure(['data' => ['check_ins']]);
    }

    /** @test */
    public function analytics_endpoint_returns_metrics()
    {
        // Ensure there is at least one checked‑in ticket
        $this->ticket->update(['status' => 'checked_in', 'checked_in' => true, 'checked_in_at' => now()]);
        $response = $this->actingAs($this->organizer, 'api')
            ->getJson("/api/organizer/events/{$this->event->id}/check-in-analytics");
        $response->assertOk()
            ->assertJsonStructure(['data' => ['total_checked_in', 'check_in_rate', 'peak_check_in_hour']]);
    }

    private function createEncryptedPayload(Ticket $ticket): string
    {
        // Use the same service the app uses to generate the QR payload
        $service = app('\App\Features\QRCodeTicketing\Services\QRCodeEncryptionService');
        $payload = [
            'ticket_id' => $ticket->id,
            'event_id' => $ticket->event_id,
            'nonce' => Str::random(16),
            'generated_at' => now()->toIso8601String(),
        ];
        return $service->encrypt($payload);
    }
}

?>