<?php

namespace Tests\Feature;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\OrderItem;
use App\Features\Checkout\Models\Payment;
use App\Features\Checkout\Models\Ticket;
use App\Features\Payment\Services\PaystackService;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class CheckoutFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;
    use MockeryPHPUnitIntegration;

    private User $user;
    private TicketTier $tier;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        
        $organizer = Organizer::factory()->create([
            'displayName' => 'Test Organizer',
        ]);
        
        $this->event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'status' => 'published',
            'title' => 'Test Event',
        ]);
        
        $this->tier = TicketTier::factory()->create([
            'event_id' => $this->event->id,
            'status' => 'published',
            'quantity' => 100,
            'sold_count' => 0,
            'price' => 15000.00,
            'is_active' => true,
        ]);
        
        $this->user = User::factory()->create([
            'role' => 'attendee',
            'emailVerified' => true,
        ]);
    }

    // ========== 1. POST /api/cart/verify ==========

    public function test_cart_verify_returns_valid_true_for_available_items(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cart/verify', [
                'items' => [
                    ['ticket_tier_id' => $this->tier->id, 'quantity' => 2],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('valid', true)
            ->assertJsonPath('total', 30000.00)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.valid', true)
            ->assertJsonPath('items.0.quantity', 2)
            ->assertJsonPath('items.0.unit_price', 15000.00)
            ->assertJsonPath('items.0.line_total', 30000.00);
    }

    public function test_cart_verify_returns_valid_false_for_unavailable_items(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/cart/verify', [
                'items' => [
                    ['ticket_tier_id' => 99999, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('valid', false)
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.valid', false)
            ->assertJsonPath('items.0.reason', 'Ticket tier not found or no longer available.');
    }
}