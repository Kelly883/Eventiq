<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckQRKeyConfiguration extends Command
{
    protected $signature = 'qr:check-key-config';

    protected $description = 'Verify QR_ENCRYPTION_KEY is set and warn if falling back to APP_KEY';

    public function handle(): int
    {
        $qrKey = env('QR_ENCRYPTION_KEY');

        if (!$qrKey) {
            $this->warn('QR_ENCRYPTION_KEY is not set. QR codes are using APP_KEY as fallback.');
            $this->warn('Set QR_ENCRYPTION_KEY in your .env file for proper key separation.');
            Log::warning('QR_ENCRYPTION_KEY not set — falling back to APP_KEY');
            return 1;
        }

        if ($qrKey === config('app.key')) {
            $this->error('QR_ENCRYPTION_KEY is identical to APP_KEY. They must be different values.');
            return 1;
        }

        $this->info('QR_ENCRYPTION_KEY is configured and separate from APP_KEY.');
        return 0;
    }
}
