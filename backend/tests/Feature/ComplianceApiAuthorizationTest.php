<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator', 'isSystemRole' => true]);
        $user = User::factory()->create();
        $user->roles()->attach($adminRole);
        return $user;
    }

    private function makeRegularUser(): User
    {
        $role = Role::create(['name' => 'organizer', 'description' => 'Event Organizer']);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        return $user;
    }

    public function test_admin_can_access_audit_logs_index(): void
    {
        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_non_admin_cannot_access_audit_logs(): void
    {
        $response = $this->actingAs($this->makeRegularUser(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs');

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_audit_logs(): void
    {
        $response = $this->getJson('/api/admin/compliance/audit-logs');

        $response->assertUnauthorized();
    }

    public function test_admin_can_access_compliance_reports(): void
    {
        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/reports');

        $response->assertOk()
            ->assertJsonStructure(['reports']);
    }

    public function test_non_admin_cannot_access_compliance_reports(): void
    {
        $response = $this->actingAs($this->makeRegularUser(), 'sanctum')
            ->getJson('/api/admin/compliance/reports');

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_compliance_reports(): void
    {
        $response = $this->getJson('/api/admin/compliance/reports');

        $response->assertUnauthorized();
    }

    public function test_admin_can_show_single_audit_log(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/00000000-0000-0000-0000-000000000000');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_admin_can_access_audit_log_export(): void
    {
        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/export');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_non_admin_cannot_access_audit_log_export(): void
    {
        $response = $this->actingAs($this->makeRegularUser(), 'sanctum')
            ->getJson('/api/admin/compliance/audit-logs/export');

        $response->assertForbidden();
    }

    public function test_admin_can_generate_compliance_report(): void
    {
        $response = $this->actingAs($this->makeAdmin(), 'sanctum')
            ->postJson('/api/admin/compliance/reports/generate', [
                'reportCode' => 'access_audit',
                'filters' => [],
            ]);

        $response->assertOk()
            ->assertJsonStructure(['id', 'status']);
    }

    public function test_non_admin_cannot_generate_compliance_report(): void
    {
        $response = $this->actingAs($this->makeRegularUser(), 'sanctum')
            ->postJson('/api/admin/compliance/reports/generate', [
                'reportCode' => 'access_audit',
                'filters' => [],
            ]);

        $response->assertForbidden();
    }
}
