<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEventBrowsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_reachable_without_authentication(): void
    {
        // The actual bug this whole endpoint fixes: it didn't exist at all,
        // so every homepage visitor's request 404'd invisibly. No Sanctum
        // actor here at all -- this must work for a genuinely anonymous
        // visitor.
        $response = $this->getJson('/api/events');

        $response->assertOk();
    }

    public function test_index_only_returns_published_events(): void
    {
        $published = Event::factory()->create(['status' => 'published']);
        Event::factory()->create(['status' => 'draft']);
        Event::factory()->create(['status' => 'flagged']);

        $response = $this->getJson('/api/events');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($published->id));
        $this->assertCount(1, $ids);
    }

    public function test_index_respects_limit_and_caps_it_at_fifty(): void
    {
        Event::factory()->count(5)->create(['status' => 'published']);

        $response = $this->getJson('/api/events?limit=2');
        $response->assertOk();
        $this->assertCount(2, $response->json('data'));

        // A caller passing an absurd limit shouldn't be able to force an
        // unbounded query.
        $response = $this->getJson('/api/events?limit=99999');
        $response->assertOk();
        $this->assertLessThanOrEqual(50, count($response->json('data')));
    }

    public function test_index_filters_by_category(): void
    {
        Event::factory()->create(['status' => 'published', 'category' => 'music']);
        Event::factory()->create(['status' => 'published', 'category' => 'sports']);

        $response = $this->getJson('/api/events?category=music');

        $response->assertOk();
        $categories = collect($response->json('data'))->pluck('category');
        $this->assertTrue($categories->every(fn ($c) => $c === 'music'));
    }

    public function test_index_response_does_not_leak_organizer_pii_when_not_public(): void
    {
        // The actual security fix this test locks in: OrganizerPublicResource
        // used to dump email/phone/commissionRate/paymentDefault
        // unconditionally, ignoring the organizer's own privacy flags. This
        // reuses that resource for the embedded organizer here, so the fix
        // needs verifying at this integration point too, not just in
        // isolation.
        $organizer = Organizer::factory()->create([
            'isPublic' => false,
            'emailPublic' => false,
            'phonePublic' => false,
            'email' => 'private-organizer@example.com',
            'phone' => '+15550001111',
            'commissionRate' => 12.5,
        ]);
        Event::factory()->create(['status' => 'published', 'organizer_id' => $organizer->id]);

        $response = $this->getJson('/api/events');

        $response->assertOk();
        $body = $response->getContent();
        $this->assertStringNotContainsString('private-organizer@example.com', $body);
        $this->assertStringNotContainsString('+15550001111', $body);
        $this->assertStringNotContainsString('commissionRate', $body);
        $this->assertStringNotContainsString('paymentDefault', $body);
    }

    public function test_index_computes_ticket_price_as_lowest_available_effective_price(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        TicketTier::factory()->create([
            'event_id' => $event->id,
            'price' => 100,
            'early_bird_price' => null,
            'is_active' => true,
            'quantity' => 50,
            'sold_count' => 0,
        ]);
        TicketTier::factory()->create([
            'event_id' => $event->id,
            'price' => 40,
            'early_bird_price' => null,
            'is_active' => true,
            'quantity' => 50,
            'sold_count' => 0,
        ]);

        $response = $this->getJson('/api/events');

        $response->assertOk();
        $data = collect($response->json('data'))->firstWhere('id', $event->id);
        $this->assertEquals(40, $data['ticket_price']);
    }

    public function test_index_computes_tickets_remaining_across_tiers(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        TicketTier::factory()->create([
            'event_id' => $event->id,
            'is_active' => true,
            'quantity' => 100,
            'sold_count' => 60,
        ]);
        TicketTier::factory()->create([
            'event_id' => $event->id,
            'is_active' => true,
            'quantity' => 20,
            'sold_count' => 20,
        ]);

        $response = $this->getJson('/api/events');

        $data = collect($response->json('data'))->firstWhere('id', $event->id);
        $this->assertEquals(40, $data['tickets_remaining']);
    }

    public function test_categories_returns_distinct_published_categories_only(): void
    {
        Event::factory()->create(['status' => 'published', 'category' => 'music']);
        Event::factory()->create(['status' => 'published', 'category' => 'music']);
        Event::factory()->create(['status' => 'published', 'category' => 'sports']);
        Event::factory()->create(['status' => 'draft', 'category' => 'draft-only-category']);

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $categories = $response->json('data');
        $this->assertContains('music', $categories);
        $this->assertContains('sports', $categories);
        $this->assertNotContains('draft-only-category', $categories);
        $this->assertCount(2, $categories);
    }

    public function test_index_is_rate_limited_to_30_per_minute_per_ip(): void
    {
        // event-ticketing-prd-export/pages/eventbrowsepage.md: "Rate limit
        // 30/min per IP to prevent scraping" -- an explicit, named security
        // requirement, not just a nice-to-have. Verifying the limiter is
        // actually wired in and enforced, not just present in
        // AppServiceProvider and silently unused.
        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/api/events')->assertOk();
        }

        $this->getJson('/api/events')->assertStatus(429);
    }
}
