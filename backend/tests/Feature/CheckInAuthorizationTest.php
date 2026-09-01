<?php

namespace Tests\Feature;

use App\Features\CheckIn\Policies\CheckInPolicy;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckInAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_venue_staff_can_only_access_assigned_events(): void
    {
        $venueStaffRole = Role::factory()->create(['name' => 'venue_staff']);
        $venueStaff = User::factory()->create(['role_id' => $venueStaffRole->id]);
        $assignedEvent = Event::factory()->create();
        $unassignedEvent = Event::factory()->create();
        $assignedEvent->venueStaff()->attach($venueStaff);

        $policy = app(CheckInPolicy::class);

        $this->assertTrue($policy->isVenueStaff($venueStaff));
        $this->assertTrue($policy->canAccessEvent($venueStaff, $assignedEvent));
        $this->assertFalse($policy->canAccessEvent($venueStaff, $unassignedEvent));

    }

    public function test_organizers_remain_scoped_to_their_events_and_admins_can_access_all_events(): void
    {
        $organizerRole = Role::factory()->create(['name' => 'organizer']);
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $organizerUser = User::factory()->create(['role_id' => $organizerRole->id]);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $organizer = Organizer::factory()->for($organizerUser)->create();
        $ownedEvent = Event::factory()->for($organizer)->create();
        $otherEvent = Event::factory()->create();

        $policy = app(CheckInPolicy::class);

        $this->assertTrue($policy->canAccessEvent($organizerUser, $ownedEvent));
        $this->assertFalse($policy->canAccessEvent($organizerUser, $otherEvent));
        $this->assertTrue($policy->canAccessEvent($admin, $otherEvent));
    }
}
