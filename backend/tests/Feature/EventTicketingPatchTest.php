<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTicketingPatchTest extends TestCase
{
    use RefreshDatabase;

    private User $organizerUser;
    private Organizer $organizer;
    private User $otherOrganizerUser;
    private Organizer $otherOrganizer;
    private Event $event;
    private TicketTier $existingTier1;
    private TicketTier $existingTier2;
    private string $token;
    private string $otherToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizerUser = User::factory()->create(['role' => 'organizer']);
        $this->organizer = Organizer::factory()->create([
            'user_id' => $this->organizerUser->id,
        ]);
        $this->token = $this->organizerUser->createToken('test-token')->plainTextToken;

        $this->otherOrganizerUser = User::factory()->create(['role' => 'organizer']);
        $this->otherOrganizer = Organizer::factory()->create([
            'user_id' => $this->otherOrganizerUser->id,
        ]);
        $this->otherToken = $this->otherOrganizerUser->createToken('other-token')->plainTextToken;

        $this->event = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'title' => 'Jazz Night',
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(10)->addHours(4),
        ]);

        $this->existingTier1 = TicketTier::factory()->create([
            'event_id' => $this->event->id,
            'name' => 'VIP',
            'price' => 5000,
            'quantity' => 50,
            'tier_order' => 1,
            'early_bird_price' => 3000,
            'early_bird_end_date' => now()->addDays(5),
            'currency' => 'NGN',
            'status' => 'published',
            'is_active' => true,
        ]);
        $this->existingTier2 = TicketTier::factory()->create([
            'event_id' => $this->event->id,
            'name' => 'Regular',
            'price' => 1000,
            'quantity' => 200,
            'tier_order' => 2,
            'currency' => 'NGN',
            'status' => 'published',
            'is_active' => true,
        ]);
    }

    private function patchWithAuthToken(string $url, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson($url, $data);
    }

    private function patchAsOtherOrganizer(string $url, array $data = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->otherToken)
            ->patchJson($url, $data);
    }

    // ── 1. AUTHENTICATION ──────────────────────────────

    public function test_requires_authentication_without_token(): void
    {
        $response = $this->patchJson("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [['name' => 'Test', 'price' => 100, 'quantity' => 10]],
        ]);
        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthorized']);
    }

    public function test_requires_authentication_with_invalid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-12345')
            ->patchJson("/api/organizer/events/{$this->event->id}/ticketing", [
                'ticketTiers' => [['name' => 'Test', 'price' => 100, 'quantity' => 10]],
            ]);
        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthorized']);
    }

    // ── 2. AUTHORIZATION ──────────────────────────────

    public function test_forbidden_for_other_organizer(): void
    {
        $response = $this->patchAsOtherOrganizer("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [['name' => 'Test', 'price' => 100, 'quantity' => 10]],
        ]);
        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden — you are not the event organizer']);
    }

    public function test_forbidden_for_attendee(): void
    {
        $attendee = User::factory()->create(['role' => 'attendee']);
        $attendeeToken = $attendee->createToken('attendee-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $attendeeToken)
            ->patchJson("/api/organizer/events/{$this->event->id}/ticketing", [
                'ticketTiers' => [['name' => 'Test', 'price' => 100, 'quantity' => 10]],
            ]);
        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden — you are not the event organizer']);
    }

    public function test_forbidden_for_non_existent_event(): void
    {
        $response = $this->patchWithAuthToken('/api/organizer/events/99999/ticketing', [
            'ticketTiers' => [['name' => 'Test', 'price' => 100, 'quantity' => 10]],
        ]);
        $response->assertStatus(404)
            ->assertJson(['message' => 'Event not found']);
    }

    // ── 3. VALIDATION: REJECT INVALID DATA ────────────

    public function test_validation_rejects_missing_ticket_tiers(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", []);
        $response->assertStatus(422)
            ->assertJsonPath('message', 'The ticket tiers field is required.')
            ->assertJsonStructure(['message', 'errors' => ['ticketTiers']]);
    }

    public function test_validation_rejects_empty_ticket_tiers_array(): void
    {
        // Empty array now rejected by Fix #3 guard (cannot delete all)
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [],
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete all ticket tiers. At least one tier must remain.');
        $this->assertDatabaseCount('ticket_tiers', 2);
    }

    public function test_validation_rejects_tier_without_name(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [['price' => 100, 'quantity' => 10]],
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers.0.name', ['The name field is required.']);
    }

    public function test_validation_rejects_tier_without_price(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [['name' => 'Test', 'quantity' => 10]],
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers.0.price', ['The price field is required.']);
    }

    public function test_validation_rejects_tier_without_quantity(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [['name' => 'Test', 'price' => 100]],
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers.0.quantity', ['The quantity field is required.']);
    }

    public function test_validation_rejects_negative_price(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [['name' => 'Test', 'price' => -100, 'quantity' => 10]],
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers.0.price', ['Price must be greater than 0.']);
    }

    public function test_validation_rejects_zero_price(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [['name' => 'Test', 'price' => 0, 'quantity' => 10]],
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers.0.price', ['Price must be greater than 0.']);
    }

    public function test_validation_rejects_zero_quantity(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [['name' => 'Test', 'price' => 100, 'quantity' => 0]],
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers.0.quantity', ['Quantity must be greater than 0.']);
    }

    public function test_validation_rejects_early_bird_equal_to_regular(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'EB', 'price' => 1000, 'quantity' => 50, 'early_bird_price' => 1000, 'early_bird_end_date' => now()->addDays(5)],
            ],
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers.0.early_bird_price', ['Early bird price must be less than the regular price.']);
    }

    public function test_validation_rejects_early_bird_greater_than_regular(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'EB', 'price' => 1000, 'quantity' => 50, 'early_bird_price' => 2000, 'early_bird_end_date' => now()->addDays(5)],
            ],
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers.0.early_bird_price', ['Early bird price must be less than the regular price.']);
    }

    public function test_validation_requires_eb_end_date_with_eb_price(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'EB', 'price' => 1000, 'quantity' => 50, 'early_bird_price' => 500],
            ],
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers.0.early_bird_end_date', ['The early bird end date field is required when early bird price is present.']);
    }

    public function test_validation_rejects_sales_end_before_start(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'Test', 'price' => 1000, 'quantity' => 50,
                    'sales_start_date' => now()->addDays(10)->toDateString(),
                    'sales_end_date' => now()->addDays(5)->toDateString()],
            ],
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers.0.sales_end_date', ['Sales end date must be after sales start date.']);
    }

    public function test_validation_rejects_eb_end_after_sales_end(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'Test', 'price' => 1000, 'quantity' => 50,
                    'sales_start_date' => now()->addDays(1)->toDateString(),
                    'sales_end_date' => now()->addDays(20)->toDateString(),
                    'early_bird_price' => 500,
                    'early_bird_end_date' => now()->addDays(25)->toDateString()],
            ],
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers.0.early_bird_end_date', ['Early bird end date must be before sales end date.']);
    }

    public function test_validation_rejects_more_than_max_tiers(): void
    {
        $tiers = [];
        for ($i = 0; $i < 11; $i++) {
            $tiers[] = ['name' => "Tier {$i}", 'price' => 100, 'quantity' => 10];
        }
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => $tiers,
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers', ['The ticket tiers may not have more than 10 items.']);
    }

    public function test_validation_rejects_invalid_existing_tier_id(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['id' => 99999, 'name' => 'Test', 'price' => 100, 'quantity' => 10],
            ],
        ]);
        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers.0.id', ['The selected ticketTiers.0.id is invalid.']);
    }

    // ── 4. CREATE NEW TIER ────────────────────────────

    public function test_can_create_new_tier(): void
    {
        $initialCount = TicketTier::where('event_id', $this->event->id)->count();

        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'Student', 'price' => 500, 'quantity' => 100],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Event and ticket tiers updated successfully')
            ->assertJsonPath('data.event.id', $this->event->id)
            ->assertJsonPath('data.event.title', 'Jazz Night')
            ->assertJsonCount($initialCount - 1, 'ticketTiers');

        $this->assertDatabaseHas('ticket_tiers', [
            'event_id' => $this->event->id,
            'name' => 'Student',
            'price' => 500,
            'quantity' => 100,
        ]);
    }

    public function test_created_tier_has_correct_defaults(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'Addon', 'price' => 200, 'quantity' => 30],
            ],
        ]);

        $tier = $response->json('ticketTiers.0');
        $this->assertEquals('Addon', $tier['name']);
        $this->assertEquals(200, $tier['price']);
        $this->assertEquals(30, $tier['quantity']);
        $this->assertEquals('NGN', $tier['currency']);
        $this->assertTrue($tier['is_active']);
        $this->assertEquals('published', $tier['status']);
        $this->assertNotNull($tier['id']);
        $this->assertNotNull($tier['created_at']);
        $this->assertNotNull($tier['updated_at']);
        $this->assertEquals(30, $tier['available_count']);
        $this->assertEquals(0, $tier['sold_count']);
    }

    // ── 5. UPDATE EXISTING TIER ───────────────────────

    public function test_can_update_existing_tier(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['id' => $this->existingTier1->id, 'name' => 'VIP Plus', 'price' => 6000, 'quantity' => 75],
            ],
        ]);

        $response->assertStatus(200);

        $tier = $response->json('ticketTiers.0');
        $this->assertEquals('VIP Plus', $tier['name']);
        $this->assertEquals(6000, $tier['price']);
        $this->assertEquals(75, $tier['quantity']);

        $this->assertDatabaseHas('ticket_tiers', [
            'id' => $this->existingTier1->id,
            'name' => 'VIP Plus',
            'price' => 6000,
            'quantity' => 75,
        ]);
    }

    public function test_can_update_only_some_fields(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['id' => $this->existingTier1->id, 'name' => 'VIP Updated'],
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ticket_tiers', [
            'id' => $this->existingTier1->id,
            'name' => 'VIP Updated',
            'price' => 5000,
            'quantity' => 50,
        ]);
    }

    public function test_cannot_update_tier_from_different_event(): void
    {
        $otherEvent = Event::factory()->create(['organizer_id' => $this->organizer->id]);
        $otherTier = TicketTier::factory()->create([
            'event_id' => $otherEvent->id,
            'name' => 'Other Tier',
            'price' => 100,
            'quantity' => 10,
        ]);

        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['id' => $otherTier->id, 'name' => 'Hacked', 'price' => 999, 'quantity' => 999],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers.0.id', ['The selected ticketTiers.0.id is invalid.']);
    }

    // ── 6. DELETE TIERS ───────────────────────────────

    public function test_can_delete_tier_by_excluding_from_request(): void
    {
        $this->assertDatabaseHas('ticket_tiers', ['id' => $this->existingTier1->id]);
        $this->assertDatabaseHas('ticket_tiers', ['id' => $this->existingTier2->id]);

        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['id' => $this->existingTier2->id, 'name' => 'Regular', 'price' => 1000, 'quantity' => 200],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'ticketTiers');

        $this->assertSoftDeleted('ticket_tiers', ['id' => $this->existingTier1->id]);
        $this->assertDatabaseHas('ticket_tiers', ['id' => $this->existingTier2->id, 'name' => 'Regular']);
    }

    public function test_can_delete_multiple_tiers(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'New Tier', 'price' => 300, 'quantity' => 50],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'ticketTiers');

        $this->assertSoftDeleted('ticket_tiers', ['id' => $this->existingTier1->id]);
        $this->assertSoftDeleted('ticket_tiers', ['id' => $this->existingTier2->id]);
        $this->assertDatabaseHas('ticket_tiers', ['name' => 'New Tier', 'price' => 300]);
    }

    // ── 7. MIXED OPERATIONS ───────────────────────────

    public function test_can_mix_create_update_and_delete_in_one_request(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['id' => $this->existingTier1->id, 'name' => 'VIP Premium', 'price' => 7500, 'quantity' => 30],
                ['name' => 'Backend', 'price' => 800, 'quantity' => 150],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'ticketTiers');

        $names = array_column($response->json('ticketTiers'), 'name');
        $this->assertContains('VIP Premium', $names);
        $this->assertContains('Backend', $names);

        $this->assertDatabaseHas('ticket_tiers', [
            'id' => $this->existingTier1->id,
            'name' => 'VIP Premium',
            'price' => 7500,
            'quantity' => 30,
        ]);
        $this->assertSoftDeleted('ticket_tiers', ['id' => $this->existingTier2->id]);
        $this->assertDatabaseHas('ticket_tiers', [
            'event_id' => $this->event->id,
            'name' => 'Backend',
            'price' => 800,
            'quantity' => 150,
        ]);
    }

    // ── 8. RESPONSE FORMAT: ALL TIER FIELDS ───────────

    public function test_response_includes_all_tier_fields(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'General', 'price' => 1500, 'quantity' => 100],
            ],
        ]);

        $response->assertStatus(200);

        $tier = $response->json('ticketTiers.0');
        $required = [
            'id', 'event_id', 'name', 'price', 'quantity',
            'sales_start_date', 'sales_end_date', 'benefits_description',
            'tier_image_url', 'early_bird_price', 'early_bird_end_date',
            'max_per_customer', 'tier_order', 'is_active',
            'currency', 'status', 'created_at', 'updated_at',
            'available_count', 'sold_count',
        ];
        foreach ($required as $key) {
            $this->assertArrayHasKey($key, $tier, "Missing key: {$key}");
        }
    }

    public function test_response_includes_calculated_early_bird_fields(): void
    {
        $now = now();
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'EB Tier', 'price' => 2000, 'quantity' => 50,
                    'early_bird_price' => 1000,
                    'early_bird_end_date' => $now->addDays(30)->toDateString(),
                    'sales_start_date' => $now->subDays(5)->toDateString(),
                    'sales_end_date' => $now->addDays(60)->toDateString(),
                ],
            ],
        ]);

        $response->assertStatus(200);

        $tier = $response->json('ticketTiers.0');
        $this->assertArrayHasKey('isEarlyBirdActive', $tier);
        $this->assertArrayHasKey('is_early_bird_active', $tier);
        $this->assertArrayHasKey('effectivePrice', $tier);
        $this->assertArrayHasKey('effective_price', $tier);

        $this->assertTrue($tier['isEarlyBirdActive']);
        $this->assertEquals(1000.0, $tier['effectivePrice']);
        $this->assertEquals(50, $tier['available_count']);
        $this->assertEquals(0, $tier['sold_count']);
    }

    public function test_calculated_fields_show_regular_price_when_eb_expired(): void
    {
        $now = now();
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'Expired EB', 'price' => 2000, 'quantity' => 50,
                    'early_bird_price' => 1000,
                    'early_bird_end_date' => $now->subDays(1)->toDateString(),
                    'sales_start_date' => $now->subDays(10)->toDateString(),
                    'sales_end_date' => $now->addDays(30)->toDateString(),
                ],
            ],
        ]);

        $response->assertStatus(200);

        $tier = $response->json('ticketTiers.0');
        $this->assertFalse($tier['isEarlyBirdActive']);
        $this->assertEquals(2000.0, $tier['effectivePrice']);
    }

    // ── 9. TRANSACTIONAL INTEGRITY ────────────────────

    public function test_transaction_rolls_back_on_validation_failure(): void
    {
        $this->assertDatabaseCount('ticket_tiers', 2);

        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'Valid Tier', 'price' => 1000, 'quantity' => 50],
                ['name' => 'Invalid Tier', 'price' => 1000, 'quantity' => 50, 'early_bird_price' => 1000, 'early_bird_end_date' => now()->addDays(5)],
            ],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('ticket_tiers', 2);
        $this->assertDatabaseHas('ticket_tiers', ['id' => $this->existingTier1->id, 'name' => 'VIP']);
        $this->assertDatabaseHas('ticket_tiers', ['id' => $this->existingTier2->id, 'name' => 'Regular']);
    }

    public function test_partial_update_does_not_leave_partial_data(): void
    {
        $this->assertDatabaseCount('ticket_tiers', 2);

        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['id' => $this->existingTier1->id, 'name' => 'Would Be Updated'],
                ['name' => 'New Invalid', 'price' => -500, 'quantity' => 10],
            ],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('ticket_tiers', [
            'id' => $this->existingTier1->id,
            'name' => 'VIP',
            'price' => 5000,
        ]);
        $this->assertDatabaseCount('ticket_tiers', 2);
    }

    // ── 10. EVENT TOUCHED ─────────────────────────────

    public function test_event_updated_at_changes_after_tier_modification(): void
    {
        $originalUpdatedAt = $this->event->updated_at->copy();
        sleep(1);

        $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'Updated Tier', 'price' => 500, 'quantity' => 25],
            ],
        ]);

        $this->event->refresh();
        $this->assertGreaterThan($originalUpdatedAt, $this->event->updated_at);
    }

    // ── 11. DATA INTEGRITY ────────────────────────────

    public function test_tiers_always_belong_to_correct_event(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'New Tier', 'price' => 500, 'quantity' => 20],
            ],
        ]);

        $tier = $response->json('ticketTiers.0');
        $this->assertEquals($this->event->id, $tier['event_id']);

        $this->assertDatabaseHas('ticket_tiers', [
            'id' => $tier['id'],
            'event_id' => $this->event->id,
        ]);
    }

    public function test_tier_order_persists_from_request(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'Third', 'price' => 300, 'quantity' => 30, 'tier_order' => 3],
                ['name' => 'First', 'price' => 100, 'quantity' => 100, 'tier_order' => 1],
                ['name' => 'Second', 'price' => 200, 'quantity' => 50, 'tier_order' => 2],
            ],
        ]);

        $tiers = $response->json('ticketTiers');
        $this->assertEquals('Third', $tiers[0]['name']);
        $this->assertEquals('First', $tiers[1]['name']);
        $this->assertEquals('Second', $tiers[2]['name']);
    }

    // ── 12. EDGE CASES ────────────────────────────────

    public function test_can_handle_many_tiers_up_to_limit(): void
    {
        $tiers = [];
        for ($i = 0; $i < 10; $i++) {
            $tiers[] = ['name' => "Tier {$i}", 'price' => 100 + $i, 'quantity' => 10 + $i];
        }

        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => $tiers,
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(10, 'ticketTiers');

        $this->assertEquals(10, \App\Models\TicketTier::where('event_id', $this->event->id)->count());
    }

    public function test_empty_tiers_array_rejected_when_tiers_exist(): void
    {
        // Fix #3: Empty tiers array should be rejected when event has existing tiers
        $this->assertDatabaseCount('ticket_tiers', 2);

        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete all ticket tiers. At least one tier must remain.');

        $this->assertDatabaseCount('ticket_tiers', 2);
    }

    public function test_can_reactivate_soft_deleted_tier(): void
    {
        // Delete individual tier (not empty array - Fix #3 guard prevents empty)
        $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['id' => $this->existingTier1->id, 'name' => 'VIP', 'price' => 5000, 'quantity' => 50],
            ],
        ]);
        $this->assertSoftDeleted('ticket_tiers', ['id' => $this->existingTier2->id]);

        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['id' => $this->existingTier1->id, 'name' => 'VIP Restored', 'price' => 5000, 'quantity' => 50],
                ['id' => $this->existingTier2->id, 'name' => 'Regular Restored', 'price' => 1000, 'quantity' => 200],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('ticket_tiers', [
            'id' => $this->existingTier1->id,
            'name' => 'VIP Restored',
        ]);
        $this->assertNull(TicketTier::find($this->existingTier1->id)->deleted_at);
    }

    public function test_currency_defaults_to_ngn(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'Default Currency', 'price' => 1000, 'quantity' => 50],
            ],
        ]);

        $tier = $response->json('ticketTiers.0');
        $this->assertEquals('NGN', $tier['currency']);
    }

    public function test_status_defaults_to_published(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'Default Status', 'price' => 1000, 'quantity' => 50],
            ],
        ]);

        $tier = $response->json('ticketTiers.0');
        $this->assertEquals('published', $tier['status']);
    }

    public function test_can_set_custom_currency(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'USD Tier', 'price' => 50, 'quantity' => 20, 'currency' => 'USD'],
            ],
        ]);

        $tier = $response->json('ticketTiers.0');
        $this->assertEquals('USD', $tier['currency']);
    }

    public function test_can_set_max_per_customer(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'Limited', 'price' => 1000, 'quantity' => 100, 'max_per_customer' => 2],
            ],
        ]);

        $tier = $response->json('ticketTiers.0');
        $this->assertEquals(2, $tier['max_per_customer']);
    }

    public function test_can_set_benefits_description(): void
    {
        $benefits = 'VIP lounge access, complimentary drinks, meet and greet';
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'VIP', 'price' => 5000, 'quantity' => 10, 'benefits_description' => $benefits],
            ],
        ]);

        $tier = $response->json('ticketTiers.0');
        $this->assertEquals($benefits, $tier['benefits_description']);
    }

    public function test_rejects_external_tier_image_url(): void
    {
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'Bad Image', 'price' => 1000, 'quantity' => 50, 'tier_image_url' => 'https://evil.com/image.jpg'],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.ticketTiers.0.tier_image_url', ['Tier image URL must be from our storage.']);
    }

    public function test_accepts_data_uri_tier_image_url(): void
    {
        $dataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $response = $this->patchWithAuthToken("/api/organizer/events/{$this->event->id}/ticketing", [
            'ticketTiers' => [
                ['name' => 'Data URI Tier', 'price' => 1000, 'quantity' => 50, 'tier_image_url' => $dataUri],
            ],
        ]);

        $response->assertStatus(200);
        $tier = $response->json('ticketTiers.0');
        $this->assertEquals($dataUri, $tier['tier_image_url']);
    }
}
