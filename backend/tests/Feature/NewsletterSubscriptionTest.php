<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_records_an_email(): void
    {
        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'user@example.com']);
    }

    public function test_subscribe_is_case_insensitive_and_idempotent(): void
    {
        $this->postJson('/api/newsletter/subscribe', ['email' => 'User@Example.com'])
            ->assertStatus(201);

        // Re-submitting the same address (different case) is not an error and
        // must not create a duplicate row because of the unique constraint.
        $this->postJson('/api/newsletter/subscribe', ['email' => 'user@example.com'])
            ->assertStatus(201);

        $this->assertEquals(1, NewsletterSubscriber::where('email', 'user@example.com')->count());
    }

    public function test_subscribe_rejects_invalid_email(): void
    {
        $response = $this->postJson('/api/newsletter/subscribe', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('newsletter_subscribers', 0);
    }
}