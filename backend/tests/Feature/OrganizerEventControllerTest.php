<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketTier;
use App\Models\User;
use App\Services\VirusScanning\ScanResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\Mockery;
use Tests\TestCase;

class OrganizerEventControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $organizerUser;
    private User $otherOrganizerUser;
    private User $attendeeUser;
    private Organizer $organizer;
    private Organizer $otherOrganizer;
    private string $token;
    private string $otherToken;
    private string $attendeeToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organizerUser = User::factory()->create(['role' => 'organizer']);
        $this->organizer = Organizer::factory()->create(['user_id' => $this->organizerUser->id]);
        $this->token = $this->organizerUser->createToken('test-token')->plainTextToken;
        $this->otherOrganizerUser = User::factory()->create(['role' => 'organizer']);
        $this->otherOrganizer = Organizer::factory()->create(['user_id' => $this->otherOrganizerUser->id]);
        $this->otherToken = $this->otherOrganizerUser->createToken('other-token')->plainTextToken;
        $this->attendeeUser = User::factory()->create(['role' => 'attendee']);
        $this->attendeeToken = $this->attendeeUser->createToken('attendee-token')->plainTextToken;
    }

    // CREATE: POST /api/organizer/events
    public function test_create_event_returns_201_with_ticket_tiers(): void
    {
        $payload = [
            'title' => 'Test Concert', 'description' => 'A great event',
            'start_datetime' => now()->addDays(10)->toDateTimeString(),
            'end_datetime' => now()->addDays(10)->addHours(4)->toDateTimeString(),
            'venue_name' => 'Main Arena', 'venue_address' => '123 Street',
            'capacity' => 500, 'status' => 'draft',
            'ticket_tiers' => [
                ['name' => 'VIP', 'price' => 5000, 'quantity' => 50],
                ['name' => 'Regular', 'price' => 1000, 'quantity' => 200],
            ],
        ];
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/organizer/events', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test Concert')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonCount(2, 'data.ticket_tiers');
        $this->assertDatabaseHas('events', ['title' => 'Test Concert', 'organizer_id' => $this->organizer->id]);
        $this->assertDatabaseCount('ticket_tiers', 2);
    }

    public function test_create_event_links_to_authenticated_organizer(): void
    {
        $payload = ['title' => 'Linked Event', 'start_datetime' => now()->addDays(5)->toDateTimeString(),
            'end_datetime' => now()->addDays(5)->addHours(2)->toDateTimeString(),
            'capacity' => 100, 'status' => 'published'];
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/organizer/events', $payload);
        $response->assertStatus(201);
        $event = Event::where('title', 'Linked Event')->first();
        $this->assertEquals($this->organizer->id, $event->organizer_id);
    }

    public function test_create_event_returns_422_for_missing_required_fields(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/organizer/events', ['description' => 'Missing title']);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'start_datetime', 'end_datetime', 'capacity', 'status']);
    }

    public function test_create_event_returns_422_for_invalid_status(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/organizer/events', ['title' => 'Bad Status', 'start_datetime' => now()->addDays(5)->toDateTimeString(),
                'end_datetime' => now()->addDays(5)->addHours(2)->toDateTimeString(),
                'capacity' => 100, 'status' => 'invalid_status']);
        $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_create_event_returns_422_for_negative_capacity(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/organizer/events', ['title' => 'Neg Cap', 'start_datetime' => now()->addDays(5)->toDateTimeString(),
                'end_datetime' => now()->addDays(5)->addHours(2)->toDateTimeString(),
                'capacity' => -10, 'status' => 'draft']);
        $response->assertStatus(422)->assertJsonValidationErrors(['capacity']);
    }

    public function test_create_event_returns_422_when_end_before_start(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/organizer/events', ['title' => 'Time Travel', 'start_datetime' => now()->addDays(5)->toDateTimeString(),
                'end_datetime' => now()->addDays(3)->toDateTimeString(),
                'capacity' => 100, 'status' => 'draft']);
        $response->assertStatus(422)->assertJsonValidationErrors(['end_datetime']);
    }

    public function test_create_event_returns_422_for_more_than_10_tiers(): void
    {
        $tiers = [];
        for ($i = 0; $i < 12; $i++) {
            $tiers[] = ['name' => "Tier $i", 'price' => 100, 'quantity' => 10];
        }
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/organizer/events', ['title' => 'Too Many Tiers', 'start_datetime' => now()->addDays(5)->toDateTimeString(),
                'end_datetime' => now()->addDays(5)->addHours(2)->toDateTimeString(),
                'capacity' => 100, 'status' => 'draft', 'ticket_tiers' => $tiers]);
        $response->assertStatus(422)->assertJsonValidationErrors(['ticket_tiers']);
    }

    // LIST: GET /api/organizer/events
    public function test_list_events_returns_paginated_results(): void
    {
        Event::factory()->count(5)->create(['organizer_id' => $this->organizer->id]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/organizer/events');
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [], 'links', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_list_events_filters_by_status(): void
    {
        Event::factory()->count(3)->create(['organizer_id' => $this->organizer->id, 'status' => 'published']);
        Event::factory()->count(2)->create(['organizer_id' => $this->organizer->id, 'status' => 'draft']);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/organizer/events?status=published');
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
        foreach ($response->json('data') as $event) {
            $this->assertEquals('live', $event['status']);
        }
    }

    public function test_list_events_returns_422_for_invalid_status_filter(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/organizer/events?status=invalid');
        $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_list_events_only_returns_authenticated_organizer_events(): void
    {
        Event::factory()->count(3)->create(['organizer_id' => $this->organizer->id]);
        Event::factory()->count(2)->create(['organizer_id' => $this->otherOrganizer->id]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/organizer/events');
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_list_events_includes_ticket_tiers(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        TicketTier::factory()->count(3)->create(['event_id' => $event->id]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/organizer/events');
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.0.ticket_tiers'));
    }

    // SHOW: GET /api/organizer/events/{event}
    public function test_show_event_returns_full_details_with_tiers(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id, 'title' => 'Detailed Event', 'capacity' => 250]);
        TicketTier::factory()->count(2)->create(['event_id' => $event->id]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$event->id}");
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Detailed Event')
            ->assertJsonPath('data.capacity', 250)
            ->assertJsonCount(2, 'data.ticket_tiers');
    }

    public function test_show_event_returns_404_for_nonexistent_event(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/organizer/events/99999');
        $response->assertStatus(404);
    }

    // UPDATE: PATCH /api/organizer/events/{event}
    public function test_update_event_changes_title_and_capacity(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id, 'title' => 'Old Title', 'capacity' => 100]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$event->id}", ['title' => 'New Title', 'capacity' => 500]);
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'New Title')
            ->assertJsonPath('data.capacity', 500);
        $this->assertDatabaseHas('events', ['id' => $event->id, 'title' => 'New Title', 'capacity' => 500]);
    }

    public function test_update_event_modifies_ticket_tiers(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $existingTier = TicketTier::factory()->create(['event_id' => $event->id, 'name' => 'Old Tier', 'price' => 100]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$event->id}", [
                'ticket_tiers' => [
                    ['id' => $existingTier->id, 'name' => 'Updated Tier', 'price' => 500, 'quantity' => 50],
                    ['name' => 'Brand New Tier', 'price' => 200, 'quantity' => 100],
                ],
            ]);
        $response->assertStatus(200)->assertJsonCount(2, 'data.ticket_tiers');
        $this->assertDatabaseHas('ticket_tiers', ['id' => $existingTier->id, 'name' => 'Updated Tier', 'price' => 500]);
    }

    public function test_update_event_returns_422_for_invalid_data(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$event->id}", ['status' => 'not_a_real_status']);
        $response->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    // DELETE: DELETE /api/organizer/events/{event}
    public function test_delete_event_soft_deletes_and_returns_204(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/organizer/events/{$event->id}");
        $response->assertStatus(204);
        $this->assertSoftDeleted('events', ['id' => $event->id]);
    }

    public function test_delete_event_stays_in_database_after_soft_delete(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/organizer/events/{$event->id}");
        $this->assertDatabaseHas('events', ['id' => $event->id]);
        $this->assertNotNull(Event::withTrashed()->find($event->id)->deleted_at);
    }

    public function test_delete_event_returns_404_for_nonexistent(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/organizer/events/99999');
        $response->assertStatus(404);
    }

    // UPLOAD BANNER: POST /api/organizer/events/{event}/upload-banner
    public function test_upload_banner_stores_file_and_updates_url(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $file = UploadedFile::fake()->image('banner.jpg', 800, 600)->size(500);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->post("/api/organizer/events/{$event->id}/upload-banner", ['banner' => $file]);
        $response->assertStatus(200);
        $event->refresh();
        $this->assertNotNull($event->banner_image_url);
        $this->assertNotEmpty(Storage::disk('public')->allFiles('events'));
    }

    public function test_upload_banner_rejects_non_image_file(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->post("/api/organizer/events/{$event->id}/upload-banner", ['banner' => $file]);
        $response->assertStatus(422)->assertJsonValidationErrors(['banner']);
    }

    public function test_upload_banner_rejects_oversized_file(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $file = UploadedFile::fake()->image('huge.jpg')->size(6000);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->post("/api/organizer/events/{$event->id}/upload-banner", ['banner' => $file]);
        // Oversized files return 413 (Payload Too Large), distinct from 422 validation errors
        $response->assertStatus(413)->assertJsonValidationErrors(['banner']);
    }

    public function test_upload_banner_returns_404_for_nonexistent_event(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('banner.jpg');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->post('/api/organizer/events/99999/upload-banner', ['banner' => $file]);
        $response->assertStatus(404);
    }

    // AUTHENTICATION: All endpoints reject unauthenticated requests
    public function test_create_returns_401_without_token(): void
    {
        $response = $this->postJson('/api/organizer/events', ['title' => 'No Auth', 'start_datetime' => now()->addDays(5)->toDateTimeString(),
            'end_datetime' => now()->addDays(5)->addHours(2)->toDateTimeString(),
            'capacity' => 100, 'status' => 'draft']);
        $response->assertStatus(401);
    }

    public function test_list_returns_401_without_token(): void
    {
        $response = $this->getJson('/api/organizer/events');
        $response->assertStatus(401);
    }

    public function test_show_returns_401_without_token(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $response = $this->getJson("/api/organizer/events/{$event->id}");
        $response->assertStatus(401);
    }

    public function test_update_returns_401_without_token(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $response = $this->patchJson("/api/organizer/events/{$event->id}", ['title' => 'Hacked']);
        $response->assertStatus(401);
    }

    public function test_delete_returns_401_without_token(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $response = $this->deleteJson("/api/organizer/events/{$event->id}");
        $response->assertStatus(401);
    }

    public function test_upload_banner_returns_401_without_token(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $response = $this->post("/api/organizer/events/{$event->id}/upload-banner");
        $response->assertStatus(401);
    }

    // AUTHORIZATION: Users can only access their own events
    public function test_show_returns_403_for_other_users_event(): void
    {
        $otherEvent = Event::factory()->create(['organizer_id' => $this->otherOrganizer->id]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$otherEvent->id}");
        $response->assertStatus(403);
    }

    public function test_update_returns_403_for_other_users_event(): void
    {
        $otherEvent = Event::factory()->create(['organizer_id' => $this->otherOrganizer->id]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$otherEvent->id}", ['title' => 'I stole this event']);
        $response->assertStatus(403);
        $this->assertDatabaseMissing('events', ['id' => $otherEvent->id, 'title' => 'I stole this event']);
    }

    public function test_delete_returns_403_for_other_users_event(): void
    {
        $otherEvent = Event::factory()->create(['organizer_id' => $this->otherOrganizer->id]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/organizer/events/{$otherEvent->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('events', ['id' => $otherEvent->id, 'deleted_at' => null]);
    }

    public function test_upload_banner_returns_403_for_other_users_event(): void
    {
        Storage::fake('public');
        $otherEvent = Event::factory()->create(['organizer_id' => $this->otherOrganizer->id]);
        $file = UploadedFile::fake()->image('banner.jpg');
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->post("/api/organizer/events/{$otherEvent->id}/upload-banner", ['banner' => $file]);
        $response->assertStatus(403);
    }

    public function test_list_excludes_other_users_events(): void
    {
        Event::factory()->count(2)->create(['organizer_id' => $this->organizer->id]);
        Event::factory()->count(3)->create(['organizer_id' => $this->otherOrganizer->id]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/organizer/events');
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    // ATTENDEE (no organizer profile) - should be forbidden
    public function test_create_returns_403_for_attendee_without_organizer_profile(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->attendeeToken)
            ->postJson('/api/organizer/events', ['title' => 'Attendee Event', 'start_datetime' => now()->addDays(5)->toDateTimeString(),
                'end_datetime' => now()->addDays(5)->addHours(2)->toDateTimeString(),
                'capacity' => 100, 'status' => 'draft']);
        $response->assertStatus(403);
    }

    public function test_list_returns_403_for_attendee_without_organizer_profile(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->attendeeToken)
            ->getJson('/api/organizer/events');
        $response->assertStatus(403);
    }

    // EDGE CASES
    public function test_create_event_with_zero_capacity_succeeds(): void
    {
        $payload = ['title' => 'Unlimited Event', 'start_datetime' => now()->addDays(5)->toDateTimeString(),
            'end_datetime' => now()->addDays(5)->addHours(2)->toDateTimeString(),
            'capacity' => 0, 'status' => 'draft'];
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/organizer/events', $payload);
        $response->assertStatus(201)->assertJsonPath('data.capacity', 0);
    }

    public function test_update_event_to_published_status(): void
    {
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id, 'status' => 'draft']);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson("/api/organizer/events/{$event->id}", ['status' => 'published']);
        $response->assertStatus(200)->assertJsonPath('data.status', 'live');
    }

    public function test_deleted_event_does_not_appear_in_list(): void
    {
        $active = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $deleted = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $deleted->delete();
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/organizer/events');
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($active->id, $ids);
        $this->assertNotContains($deleted->id, $ids);
    }

    public function test_per_page_is_capped_at_100(): void
    {
        Event::factory()->count(5)->create(['organizer_id' => $this->organizer->id]);
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/organizer/events?per_page=500');
        $response->assertStatus(200);
        $this->assertLessThanOrEqual(100, $response->json('meta.per_page'));
    }

    // MIGRATION / DATA INTEGRITY: audit_logs table must exist after migrations
    public function test_audit_logs_table_exists_after_migrations(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasTable('audit_logs'),
            'audit_logs table should exist after running migrations'
        );
    }

    // AUDIT TRAIL: event creation writes audit log inside the same transaction
    public function test_create_event_writes_audit_log(): void
    {
        $payload = [
            'title' => 'Audited Event',
            'start_datetime' => now()->addDays(5)->toDateTimeString(),
            'end_datetime' => now()->addDays(5)->addHours(2)->toDateTimeString(),
            'capacity' => 100,
            'status' => 'draft',
        ];
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/organizer/events', $payload);
        $response->assertStatus(201);
        $eventId = $response->json('data.id');
        $this->assertDatabaseHas('audit_logs', [
            'target_type' => 'event',
            'target_id' => (string) $eventId,
            'action' => \App\Features\Compliance\Enums\AuditLogAction::EventCreated,
            'user_id' => $this->organizerUser->id,
        ]);
    }

    // VIRUS SCANNER: when scanner is unavailable, upload must be rejected (fail closed)
    public function test_upload_banner_rejects_when_virus_scanner_unavailable(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $file = UploadedFile::fake()->image('banner.jpg', 800, 600)->size(500);

        // Mock VirusScanner to simulate scanner unavailable
        $mock = \Mockery::mock(\App\Services\VirusScanning\VirusScanner::class);
        $mock->shouldReceive('scan')->andReturn(
            \App\Services\VirusScanning\ScanResult::unavailable('No scanner configured')
        );
        $this->app->instance(\App\Services\VirusScanning\VirusScanner::class, $mock);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->post("/api/organizer/events/{$event->id}/upload-banner", ['banner' => $file]);
        $response->assertStatus(503);
        $response->assertJsonPath('message', 'Security scan unavailable. Upload rejected.');
    }

    // BANNER URL ALLOWLIST: if storage returns an external host, reject the upload
    public function test_upload_banner_rejects_external_storage_url(): void
    {
        Storage::fake('public');
        $event = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $file = UploadedFile::fake()->image('banner.jpg', 800, 600)->size(500);

        // Mock disk to return an external URL
        $disk = Storage::disk('public');
        $reflection = new \ReflectionClass($disk);
        $property = $reflection->getProperty('driver');
        $property->setAccessible(true);
        $mockDriver = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $mockDriver->shouldReceive('put')->andReturn(true);
        $mockDriver->shouldReceive('url')->andReturn('https://evil.example.com/malicious.jpg');
        $property->setValue($disk, $mockDriver);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->post("/api/organizer/events/{$event->id}/upload-banner", ['banner' => $file]);
        $response->assertStatus(500);
        $response->assertJsonPath('message', 'Failed to upload banner');
    }
}
