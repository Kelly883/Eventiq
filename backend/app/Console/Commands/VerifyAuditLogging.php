<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyAuditLogging extends Command
{
    protected $signature = 'audit:verify-logging {--write-test : Write a test entry to the audit channel}';

    protected $description = 'Verify audit logging configuration and storage/logs writeability.';

    public function handle(): int
    {
        $logPath = storage_path('logs');

        if (! is_dir($logPath)) {
            $this->error("{$logPath} does not exist.");

            return self::FAILURE;
        }

        if (! is_writable($logPath)) {
            $this->error("{$logPath} is not writable by the current process.");

            return self::FAILURE;
        }

        $this->info("{$logPath} is writable.");
        $this->line('LOG_CHANNEL=' . config('logging.default'));
        $this->line('LOG_STACK=' . implode(',', config('logging.channels.stack.channels', [])));
        $this->line('AUDIT_EXTERNAL_LOG_SERVICE=' . config('audit.external_log_service', 'none'));

        if ($this->option('write-test')) {
            Log::channel('audit')->info('audit.logging_verification', [
                'request_id' => 'audit-verify-' . now()->timestamp,
            ]);
            $this->info('Wrote audit.logging_verification to the audit channel.');
        }

        return self::SUCCESS;
    }
}
