<?php

namespace App\Console\Commands;

use App\Features\PushNotifications\Models\PushNotificationDevice;
use Illuminate\Console\Command;

class PruneInactivePushDevices extends Command
{
    protected $signature = 'push:prune-inactive {--days=90 : Delete devices not used within this many days}';

    protected $description = 'Delete push notification devices that have not been used recently.';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('Days must be at least 1.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $deleted = PushNotificationDevice::where('last_used_at', '<', $cutoff)
            ->orWhere(function ($query) use ($cutoff) {
                $query->whereNull('last_used_at')
                    ->where('created_at', '<', $cutoff);
            })
            ->delete();

        $this->info("Pruned {$deleted} inactive push device(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
