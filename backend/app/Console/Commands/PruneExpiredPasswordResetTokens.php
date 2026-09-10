<?php

namespace App\Console\Commands;

use App\Models\PasswordResetToken;
use Illuminate\Console\Command;

class PruneExpiredPasswordResetTokens extends Command
{
    protected $signature = 'auth:prune-expired-tokens {--days=7 : Delete tokens expired more than this many days ago}';

    protected $description = 'Delete expired password_reset_tokens to prevent table bloat (uses idx_prt_expires_at).';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('Retention must be at least 1 day.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        // Uses idx_prt_expires_at and idx_prt_hash_used_expires — O(log n) not scan
        $pruned = PasswordResetToken::where('expiresAt', '<', $cutoff)->delete();

        $this->info("Pruned {$pruned} expired password_reset_tokens older than {$days} day(s) (cutoff: {$cutoff}).");

        return self::SUCCESS;
    }
}
