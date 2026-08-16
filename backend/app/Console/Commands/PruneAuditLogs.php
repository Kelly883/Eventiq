<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune {--days= : Override AUDIT_LOG_RETENTION_DAYS for this run}';

    protected $description = 'Delete audit log database rows past their retention date.';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('audit.retention_days', 365));

        if ($days < 1) {
            $this->error('Audit retention must be at least 1 day.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $pruned = AuditLog::where('retention_date', '<', $cutoff)
            ->forceDelete();

        $this->info("Pruned {$pruned} audit log row(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
