<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RotateQRKey extends Command
{
    protected $signature = 'qr:rotate-key
                            {--new-key= : The new base64-encoded 32-byte key. If omitted, one will be generated.}
                            {--rotation-at= : ISO-8601 timestamp when rotation window closes. Defaults to +1 hour.}
                            {--force : Skip confirmation prompts}';

    protected $description = 'Rotate QR_ENCRYPTION_KEY with zero-downtime window';

    public function handle(): int
    {
        $currentKey = env('QR_ENCRYPTION_KEY');

        if (!$currentKey) {
            $this->error('QR_ENCRYPTION_KEY is not set. Set it before rotating.');
            return 1;
        }

        if ($currentKey === config('app.key')) {
            $this->error('QR_ENCRYPTION_KEY is identical to APP_KEY. They must be different.');
            return 1;
        }

        $pendingNewKey = env('QR_ENCRYPTION_KEY_NEW');
        $rotationAt = env('QR_ENCRYPTION_KEY_ROTATION_AT');

        if ($pendingNewKey && $rotationAt) {
            if (now()->greaterThanOrEqualTo($rotationAt)) {
                $this->completeRotation($currentKey, $pendingNewKey);
                return 0;
            }

            $this->warn('A rotation is already in progress.');
            $this->line("QR_ENCRYPTION_KEY_ROTATION_AT: {$rotationAt}");
            $this->line('Time remaining: ' . now()->diffForHumans($rotationAt, ['short' => true, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]));

            if (!$this->option('force') && !$this->confirm('Do you want to cancel this rotation and start a new one?', false)) {
                return 0;
            }

            $this->cancelRotation();
            $this->startRotation();
            return 0;
        }

        if ($pendingNewKey || $rotationAt) {
            $this->warn('Incomplete rotation state detected.');
            $this->line('QR_ENCRYPTION_KEY_NEW: ' . ($pendingNewKey ? 'set' : 'missing'));
            $this->line('QR_ENCRYPTION_KEY_ROTATION_AT: ' . ($rotationAt ?: 'missing'));

            if (!$this->option('force') && !$this->confirm('Do you want to clear this state and start a new rotation?', false)) {
                return 0;
            }

            $this->cancelRotation();
            $this->startRotation();
            return 0;
        }

        $this->startRotation();

        return 0;
    }

    private function startRotation(): void
    {
        $newKey = $this->option('new-key') ?: base64_encode(random_bytes(32));
        $rotationAt = $this->option('rotation-at') ?: now()->addHour()->toIso8601String();

        $this->info('QR Encryption Key Rotation');
        $this->line('');

        $this->warn('Set the following environment variables in your deployment:');
        $this->line('');
        $this->line('  QR_ENCRYPTION_KEY_NEW=' . $newKey);
        $this->line('  QR_ENCRYPTION_KEY_ROTATION_AT=' . $rotationAt);
        $this->line('');

        $this->info('After deploying with these variables:');
        $this->line('  1. Existing QR codes decrypt with both old and new keys.');
        $this->line('  2. New QR codes are encrypted with the old key.');
        $this->line('  3. After ' . $rotationAt . ', promote the new key.');
        $this->line('');

        if (!$this->option('force') && !$this->confirm('Do you want to continue and set these values in the current .env?', false)) {
            return;
        }

        $this->updateEnv([
            'QR_ENCRYPTION_KEY_NEW' => $newKey,
            'QR_ENCRYPTION_KEY_ROTATION_AT' => $rotationAt,
        ]);

        $this->info('Rotation variables set in .env.');
        $this->warn('Remember: after ' . $rotationAt . ', run this command again to complete rotation.');
    }

    private function completeRotation(string $currentKey, string $newKey): void
    {
        $this->info('Completing QR Encryption Key Rotation');
        $this->line('');

        $this->warn('This will:');
        $this->line('  - Set QR_ENCRYPTION_KEY to the new key');
        $this->line('  - Remove QR_ENCRYPTION_KEY_NEW');
        $this->line('  - Remove QR_ENCRYPTION_KEY_ROTATION_AT');
        $this->line('');
        $this->warn('All QR codes issued before this rotation will become invalid and must be re-issued.');
        $this->line('');

        if (!$this->option('force') && !$this->confirm('Are you sure you want to complete the rotation?', false)) {
            return;
        }

        $this->updateEnv([
            'QR_ENCRYPTION_KEY' => $newKey,
            'QR_ENCRYPTION_KEY_NEW' => null,
            'QR_ENCRYPTION_KEY_ROTATION_AT' => null,
        ]);

        $this->info('Rotation complete.');
        $this->line('QR_ENCRYPTION_KEY has been updated to the new key.');
    }

    private function cancelRotation(): void
    {
        $this->updateEnv([
            'QR_ENCRYPTION_KEY_NEW' => null,
            'QR_ENCRYPTION_KEY_ROTATION_AT' => null,
        ]);

        $this->info('Pending rotation cleared.');
    }

    private function updateEnv(array $values): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            $this->error('.env file not found at ' . $envPath);
            return;
        }

        $env = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            if ($value === null) {
                $env = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', '', $env);
                continue;
            }

            $escaped = addcslashes($value, '/');
            if (preg_match('/^' . preg_quote($key, '/') . '=/m', $env)) {
                $env = preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $key . '=' . $value, $env);
            } else {
                $env .= PHP_EOL . $key . '=' . $value;
            }
        }

        file_put_contents($envPath, $env);
    }
}
