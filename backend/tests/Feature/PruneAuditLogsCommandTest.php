<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneAuditLogsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_only_audit_logs_older_than_retention_window(): void
    {
        AuditLog::create([
            'action' => 'old.event',
            'entity' => 'test',
            'created_at' => now()->subDays(400),
            'updated_at' => now()->subDays(400),
        ]);

        $recent = AuditLog::create([
            'action' => 'recent.event',
            'entity' => 'test',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $this->artisan('audit:prune', ['--days' => 365])->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['action' => 'old.event']);
        $this->assertDatabaseHas('audit_logs', ['id' => $recent->id, 'action' => 'recent.event']);
    }
}
