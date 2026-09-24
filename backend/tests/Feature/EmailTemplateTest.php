<?php

namespace Tests\Feature;

use App\Features\EmailNotifications\Models\EmailTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        RateLimiter::for('email-templates-list', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('email-templates-detail', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('email-templates-write', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
    }

    protected function tearDown(): void
    {
        RateLimiter::for('email-templates-list', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by('127.0.0.1'));
        RateLimiter::for('email-templates-detail', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('127.0.0.1'));
        RateLimiter::for('email-templates-write', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by('127.0.0.1'));
        RateLimiter::for('email-templates-send-test', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by('127.0.0.1'));
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
        }

        return $user;
    }

    // ------------------------------------------------------------------
    // Auth / Authorization
    // ------------------------------------------------------------------

    public function test_list_requires_authentication(): void
    {
        $this->getJson('/api/admin/email-templates')->assertUnauthorized();
    }

    public function test_list_requires_admin_role(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user, 'sanctum')->getJson('/api/admin/email-templates')->assertForbidden();
    }

    public function test_show_requires_authentication(): void
    {
        $this->getJson('/api/admin/email-templates/some-id')->assertUnauthorized();
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/admin/email-templates', [])->assertUnauthorized();
    }

    public function test_update_requires_authentication(): void
    {
        $this->patchJson('/api/admin/email-templates/some-id', [])->assertUnauthorized();
    }

    public function test_destroy_requires_authentication(): void
    {
        $this->deleteJson('/api/admin/email-templates/some-id')->assertUnauthorized();
    }

    public function test_send_test_requires_authentication(): void
    {
        $this->postJson('/api/admin/email-templates/send-test', [])->assertUnauthorized();
    }

    // ------------------------------------------------------------------
    // GET /api/admin/email-templates (list)
    // ------------------------------------------------------------------

    public function test_list_returns_paginated_templates(): void
    {
        $admin = $this->makeUser('admin');

        EmailTemplate::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/email-templates');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 25)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_list_filter_by_type(): void
    {
        $admin = $this->makeUser('admin');

        EmailTemplate::factory()->create(['type' => 'welcome']);
        EmailTemplate::factory()->create(['type' => 'welcome']);
        EmailTemplate::factory()->create(['type' => 'order_confirmation']);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/email-templates?filter[type]=welcome');

        $response->assertOk();
        $items = $response->json('data');
        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertEquals('welcome', $item['type']);
        }
    }

    public function test_list_filter_by_is_active(): void
    {
        $admin = $this->makeUser('admin');

        EmailTemplate::factory()->create(['is_active' => true]);
        EmailTemplate::factory()->create(['is_active' => true]);
        EmailTemplate::factory()->create(['is_active' => false]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/email-templates?filter[is_active]=true');

        $response->assertOk();
        $items = $response->json('data');
        $this->assertCount(2, $items);
        foreach ($items as $item) {
            $this->assertTrue($item['isActive']);
        }
    }

    // ------------------------------------------------------------------
    // GET /api/admin/email-templates/:templateId (show)
    // ------------------------------------------------------------------

    public function test_show_returns_full_details(): void
    {
        $admin = $this->makeUser('admin');
        $template = EmailTemplate::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/email-templates/' . $template->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $template->id)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'type', 'subject', 'htmlBody', 'variables', 'isActive'],
            ]);
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/email-templates/nonexistent-id');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    // ------------------------------------------------------------------
    // POST /api/admin/email-templates (store)
    // ------------------------------------------------------------------

    public function test_store_creates_template(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/email-templates', [
            'name' => 'Welcome Email',
            'type' => 'welcome',
            'subject' => 'Welcome to our platform',
            'html_body' => '<h1>Welcome</h1>',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Welcome Email');

        $this->assertDatabaseHas('email_templates', [
            'name' => 'Welcome Email',
            'type' => 'welcome',
        ]);
    }

    public function test_store_compiles_mjml_to_html(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/email-templates', [
            'name' => 'MJML Template',
            'type' => 'welcome',
            'subject' => 'Welcome',
            'mjml_body' => '<mjml><mj-body><mj-section><mj-column><mj-text>Hello</mj-text></mj-column></mj-section></mj-body></mjml>',
        ]);

        $response->assertStatus(201);

        $template = EmailTemplate::find($response->json('data.id'));
        $this->assertStringContainsString('Hello', $template->html_body);
    }

    // ------------------------------------------------------------------
    // PATCH /api/admin/email-templates/:templateId (update)
    // ------------------------------------------------------------------

    public function test_update_modifies_template(): void
    {
        $admin = $this->makeUser('admin');
        $template = EmailTemplate::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($admin, 'sanctum')->patchJson('/api/admin/email-templates/' . $template->id, [
            'name' => 'New Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertEquals('New Name', $template->fresh()->name);
    }

    public function test_update_recompiles_mjml(): void
    {
        $admin = $this->makeUser('admin');
        $template = EmailTemplate::factory()->create([
            'mjml_body' => '<mjml><body><mj-text>Old</mj-text></body></mjml>',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->patchJson('/api/admin/email-templates/' . $template->id, [
            'mjml_body' => '<mjml><body><mj-text>New</mj-text></body></mjml>',
        ]);

        $response->assertOk();

        $template->refresh();
        $this->assertStringContainsString('New', $template->html_body);
    }

    public function test_update_logs_to_audit_logs(): void
    {
        $admin = $this->makeUser('admin');
        $template = EmailTemplate::factory()->create(['name' => 'Old Name']);

        $this->actingAs($admin, 'sanctum')->patchJson('/api/admin/email-templates/' . $template->id, [
            'name' => 'New Name',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'email_template_updated',
            'target_id' => $template->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_update_returns_404_for_nonexistent(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/email-templates/nonexistent-id', ['name' => 'Test']);

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    // ------------------------------------------------------------------
    // DELETE /api/admin/email-templates/:templateId (destroy)
    // ------------------------------------------------------------------

    public function test_destroy_deletes_template(): void
    {
        $admin = $this->makeUser('admin');
        $template = EmailTemplate::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/admin/email-templates/' . $template->id);

        $response->assertStatus(204);

        $this->assertSoftDeleted('email_templates', ['id' => $template->id]);
    }

    public function test_destroy_logs_to_audit_logs(): void
    {
        $admin = $this->makeUser('admin');
        $template = EmailTemplate::factory()->create();

        $this->actingAs($admin, 'sanctum')->deleteJson('/api/admin/email-templates/' . $template->id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'email_template_deleted',
            'target_id' => $template->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_destroy_returns_404_for_nonexistent(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/admin/email-templates/nonexistent-id');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    // ------------------------------------------------------------------
    // POST /api/admin/email-templates/send-test
    // ------------------------------------------------------------------

    public function test_send_test_sends_email(): void
    {
        $admin = $this->makeUser('admin');
        $template = EmailTemplate::factory()->create([
            'html_body' => '<h1>Test</h1>',
            'subject' => 'Test Subject',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/email-templates/send-test', [
            'template_id' => $template->id,
            'recipient_email' => 'test@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Test email sent');

        Mail::assertSent(\App\Mail\TestEmailMailable::class);
    }

    public function test_send_test_requires_valid_email(): void
    {
        $admin = $this->makeUser('admin');
        $template = EmailTemplate::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/email-templates/send-test', [
            'template_id' => $template->id,
            'recipient_email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
    }

    public function test_send_test_returns_404_for_nonexistent_template(): void
    {
        $admin = $this->makeUser('admin');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/email-templates/send-test', [
            'template_id' => 999999,
            'recipient_email' => 'test@example.com',
        ]);

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    // ------------------------------------------------------------------
    // Rate limiting
    // ------------------------------------------------------------------

    public function test_list_rate_limited(): void
    {
        RateLimiter::for('email-templates-list', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by('127.0.0.1'));

        $admin = $this->makeUser('admin');

        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($admin, 'sanctum')
                ->getJson('/api/admin/email-templates')
                ->assertOk();
        }

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/email-templates')
            ->assertStatus(429);
    }

    public function test_send_test_rate_limited(): void
    {
        RateLimiter::for('email-templates-send-test', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by('127.0.0.1'));

        $admin = $this->makeUser('admin');
        $template = EmailTemplate::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($admin, 'sanctum')
                ->postJson('/api/admin/email-templates/send-test', [
                    'template_id' => $template->id,
                    'recipient_email' => 'test' . $i . '@example.com',
                ])
                ->assertOk();
        }

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/email-templates/send-test', [
                'template_id' => $template->id,
                'recipient_email' => 'over@example.com',
            ]);

        $response->assertStatus(429);
    }
}
