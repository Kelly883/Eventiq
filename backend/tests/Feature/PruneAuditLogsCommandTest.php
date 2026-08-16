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
        $oldLog = new AuditLog([
            'action' => 'old.event',
            'target_type' => 'test',
        ]);
        $oldLog->retention_date = now()->subDays(400);
        $oldLog->save();

        $recent = new AuditLog([
            'action' => 'recent.event',
            'target_type' => 'test',
        ]);
        $recent->retention_date = now()->subDays(10);
        $recent->save();

        $this->artisan('audit:prune', ['--days' => 365])->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['action' => 'old.event']);
        $this->assertDatabaseHas('audit_logs', ['id' => $recent->id, 'action' => 'recent.event']);
    }
}
