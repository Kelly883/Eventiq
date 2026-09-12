<?php

namespace App\Console\Commands;

use App\Models\Organizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneOrphanAvatars extends Command
{
    protected $signature = 'organizer:prune-orphan-avatars {--dry-run : List orphans without deleting} {--days=7 : Only prune files older than this many days}';
    protected $description = 'Delete orphaned avatar files in avatars/* not referenced by any organizer.avatarUrl (S3 + public).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        // Collect all referenced paths from DB
        $urls = Organizer::whereNotNull('avatarUrl')->pluck('avatarUrl');
        $referenced = collect($urls)->map(function ($url) {
            if (!$url) return null;
            if (str_starts_with($url, 'avatars/')) return $url;
            $path = parse_url($url, PHP_URL_PATH);
            if (!$path) return null;
            $path = ltrim($path, '/');
            if (str_starts_with($path, 'storage/')) $path = substr($path, 8);
            $pos = strpos($path, 'avatars/');
            return $pos !== false ? substr($path, $pos) : null;
        })->filter()->unique()->flip(); // flip for O(1) lookup

        $disks = ['public'];
        try {
            $s3Configured = !empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.bucket'));
            if ($s3Configured) $disks[] = 's3';
        } catch (\Throwable $e) {}

        $totalOrphans = 0;
        $totalDeleted = 0;
        foreach ($disks as $diskName) {
            try {
                $disk = Storage::disk($diskName);
                $files = $disk->allFiles('avatars');
            } catch (\Throwable $e) {
                $this->warn("Disk {$diskName} not available: {$e->getMessage()}");
                continue;
            }
            foreach ($files as $file) {
                if (isset($referenced[$file])) continue;
                // Age check
                try {
                    $lastModified = $disk->lastModified($file);
                    if ($lastModified && \Carbon\Carbon::createFromTimestamp($lastModified)->greaterThan($cutoff)) {
                        continue;
                    }
                } catch (\Throwable $e) {}
                $totalOrphans++;
                if ($dryRun) {
                    $this->line("[dry-run] {$diskName}:{$file}");
                } else {
                    try {
                        $disk->delete($file);
                        $totalDeleted++;
                        $this->info("Deleted {$diskName}:{$file}");
                    } catch (\Throwable $e) {
                        $this->error("Failed {$diskName}:{$file} — {$e->getMessage()}");
                    }
                }
            }
        }

        if ($dryRun) {
            $this->info("Found {$totalOrphans} orphan(s) older than {$days}d (dry-run, not deleted).");
        } else {
            $this->info("Pruned {$totalDeleted}/{$totalOrphans} orphan avatar(s) older than {$days}d.");
        }
        return self::SUCCESS;
    }
}
