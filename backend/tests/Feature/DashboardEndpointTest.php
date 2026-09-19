<?php

namespace Tests\Feature;

use App\Features\Dashboard\Models\UserDashboardPreference;
use App\Features\Dashboard\Models\OrganizerDashboardPreferences;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $organizerUser;
    private User $adminUser;
    private User $attendeeUser;
    private Organizer $organizer;
    private Event $event;
    private string $token;
    private string $adminToken;
    private string $attendeeToken;

    protected function setUp(): void
    {
        parent::setUp();

        $organizerRole = Role::factory()->create(['name' => 'organizer']);
        $adminRole = Role::factory()->create(['name' => 'admin']);

        $this->organizerUser = User::factory()->create(['role_id' => $organizerRole->id]);
        $this->organizer = Organizer::factory()->create(['user_id' => $this->organizerUser->id]);
        $this->token = $this->organizerUser->createToken('organizer-token')->plainTextToken;

        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->adminToken = $this->adminUser->createToken('admin-token')->plainTextToken;

        $this->attendeeUser = User::factory()->create(['role' => 'attendee']);
        $this->attendeeToken = $this->attendeeUser->createToken('attendee-token')->plainTextToken;

        $this->event = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(10)->addHours(4),
        ]);
    }

    // -------------------------------------------------------------------------
    // METRICS: GET /api/organizer/dashboard/metrics
    // -------------------------------------------------------------------------

    public function test_dashboard_metrics_returns_aggregate_data(): void
    {
        Event::factory()->count(3)->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/dashboard/metrics");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'metrics' => [
                    'totalEvents',
                    'totalTicketsSold',
                    'totalRevenue',
                    'eventsPublished',
                ],
            ]);

        $this->assertGreaterThanOrEqual(4, $response->json('metrics.totalEvents'));
    }

    public function test_dashboard_metrics_filters_by_event(): void
    {
        $otherEvent = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/dashboard/metrics?eventId={$this->event->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Should only count the selected event
        $this->assertEquals(1, $response->json('metrics.totalEvents'));
    }

    public function test_dashboard_metrics_returns_401_without_token(): void
    {
        $response = $this->getJson("/api/organizer/dashboard/metrics");
        $response->assertStatus(401);
    }

    public function test_dashboard_metrics_admin_sees_all_events(): void
    {
        $otherOrganizer = Organizer::factory()->create();
        Event::factory()->create([
            'organizer_id' => $otherOrganizer->id,
            'status' => 'published',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson("/api/organizer/dashboard/metrics");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertGreaterThanOrEqual(2, $response->json('metrics.totalEvents'));
    }

    public function test_dashboard_metrics_organizer_only_sees_own_events(): void
    {
        $otherOrganizer = Organizer::factory()->create();
        Event::factory()->count(2)->create([
            'organizer_id' => $otherOrganizer->id,
            'status' => 'published',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/dashboard/metrics");

        $response->assertStatus(200);

        // Only organizer's own event should be counted
        $this->assertEquals(1, $response->json('metrics.totalEvents'));
    }

    // -------------------------------------------------------------------------
    // PREFERENCES: GET /api/organizer/dashboard/preferences
    // -------------------------------------------------------------------------

    public function test_get_preferences_returns_defaults_when_none_exist(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/dashboard/preferences");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('preferences.defaultEventFilter', 'all')
            ->assertJsonPath('preferences.defaultDateRange', 'last_30_days')
            ->assertJsonPath('preferences.showActivityFeed', true)
            ->assertJsonPath('preferences.autoRefreshEnabled', false);
    }

    public function test_get_preferences_returns_stored_preferences(): void
    {
        OrganizerDashboardPreferences::create([
            'organizer_id' => $this->organizer->id,
            'default_event_filter' => 'upcoming',
            'default_date_range' => 'last_7_days',
            'show_activity_feed' => false,
            'auto_refresh_enabled' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/dashboard/preferences");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('preferences.defaultEventFilter', 'upcoming')
            ->assertJsonPath('preferences.defaultDateRange', 'last_7_days')
            ->assertJsonPath('preferences.showActivityFeed', false)
            ->assertJsonPath('preferences.autoRefreshEnabled', true);
    }

    public function test_get_preferences_returns_401_without_token(): void
    {
        $response = $this->getJson("/api/organizer/dashboard/preferences");
        $response->assertStatus(401);
    }

    public function test_get_preferences_returns_403_for_non_owner(): void
    {
        $otherToken = User::factory()->create(['role' => 'organizer'])->createToken('other-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $otherToken)
            ->getJson("/api/organizer/dashboard/preferences");

        $response->assertStatus(403);
    }

    public function test_get_preferences_admin_access(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson("/api/organizer/dashboard/preferences");

        // Admin can access organizer dashboard preferences if any exist,
        // or receive default preferences
        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // -------------------------------------------------------------------------
    // PREFERENCES: PUT /api/organizer/dashboard/preferences
    // -------------------------------------------------------------------------

    public function test_update_preferences_creates_new_record(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/organizer/dashboard/preferences", [
                'default_event_filter' => 'past',
                'default_date_range' => 'last_90_days',
                'show_activity_feed' => false,
                'auto_refresh_enabled' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('preferences.defaultEventFilter', 'past')
            ->assertJsonPath('preferences.defaultDateRange', 'last_90_days')
            ->assertJsonPath('preferences.showActivityFeed', false)
            ->assertJsonPath('preferences.autoRefreshEnabled', true);

        $this->assertDatabaseHas('organizer_dashboard_preferences', [
            'organizer_id' => $this->organizer->id,
            'default_event_filter' => 'past',
        ]);
    }

    public function test_update_preferences_updates_existing_record(): void
    {
        OrganizerDashboardPreferences::create([
            'organizer_id' => $this->organizer->id,
            'default_event_filter' => 'all',
            'default_date_range' => 'last_30_days',
            'show_activity_feed' => true,
            'auto_refresh_enabled' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/organizer/dashboard/preferences", [
                'default_event_filter' => 'upcoming',
                'default_date_range' => 'last_7_days',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('preferences.defaultEventFilter', 'upcoming')
            ->assertJsonPath('preferences.defaultDateRange', 'last_7_days')
            ->assertJsonPath('preferences.showActivityFeed', true)
            ->assertJsonPath('preferences.autoRefreshEnabled', false);

        $this->assertDatabaseHas('organizer_dashboard_preferences', [
            'organizer_id' => $this->organizer->id,
            'default_event_filter' => 'upcoming',
        ]);
    }

    public function test_update_preferences_partial_update_preserves_other_fields(): void
    {
        OrganizerDashboardPreferences::create([
            'organizer_id' => $this->organizer->id,
            'default_event_filter' => 'all',
            'default_date_range' => 'last_30_days',
            'show_activity_feed' => false,
            'auto_refresh_enabled' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/organizer/dashboard/preferences", [
                'default_event_filter' => 'upcoming',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('preferences.defaultEventFilter', 'upcoming')
            ->assertJsonPath('preferences.showActivityFeed', false)
            ->assertJsonPath('preferences.autoRefreshEnabled', true);
    }

    public function test_update_preferences_returns_401_without_token(): void
    {
        $response = $this->putJson("/api/organizer/dashboard/preferences", [
            'default_event_filter' => 'all',
        ]);

        $response->assertStatus(401);
    }

    public function test_update_preferences_returns_403_for_non_owner(): void
    {
        $otherToken = User::factory()->create(['role' => 'organizer'])->createToken('other-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $otherToken)
            ->putJson("/api/organizer/dashboard/preferences", [
                'default_event_filter' => 'all',
            ]);

        $response->assertStatus(403);
    }

    public function test_update_preferences_rejects_invalid_event_filter(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/organizer/dashboard/preferences", [
                'default_event_filter' => 'invalid_filter_type',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['default_event_filter']);
    }

    public function test_update_preferences_rejects_invalid_date_range(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/organizer/dashboard/preferences", [
                'default_date_range' => 'invalid_range',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['default_date_range']);
    }

    // -------------------------------------------------------------------------
    // ACTIVITY FEED: GET /api/organizer/dashboard/activity-feed
    // -------------------------------------------------------------------------

    public function test_activity_feed_returns_empty_array_when_disabled(): void
    {
        OrganizerDashboardPreferences::create([
            'organizer_id' => $this->organizer->id,
            'show_activity_feed' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/dashboard/activity-feed");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('enabled', false)
            ->assertJsonCount(0, 'activities');
    }

    public function test_activity_feed_returns_activities_when_enabled(): void
    {
        OrganizerDashboardPreferences::create([
            'organizer_id' => $this->organizer->id,
            'show_activity_feed' => true,
        ]);

        // Just assert it runs without error when no events exist
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/dashboard/activity-feed");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('enabled', true)
            ->assertJsonStructure([
                'activities' => [],
            ]);
    }

    public function test_activity_feed_returns_401_without_token(): void
    {
        $response = $this->getJson("/api/organizer/dashboard/activity-feed");
        $response->assertStatus(401);
    }

    public function test_activity_feed_returns_403_for_non_owner(): void
    {
        $otherToken = User::factory()->create(['role' => 'organizer'])->createToken('other-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $otherToken)
            ->getJson("/api/organizer/dashboard/activity-feed");

        $response->assertStatus(403);
    }
}
