<?php

namespace App\Services\VirusScanning;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class VirusScanner
{
    private ?VirusScannerInterface $scanner = null;

    public function __construct()
    {
        $this->resolveScanner();
    }

    public function scan(UploadedFile $file): ScanResult
    {
        if ($this->scanner === null) {
            $this->resolveScanner();
        }

        if ($this->scanner === null) {
            Log::warning('No virus scanner available, falling back to allowing upload');

            return ScanResult::unavailable('No virus scanner configured');
        }

        return $this->scanner->scan($file);
    }

    public function isAvailable(): bool
    {
        if ($this->scanner === null) {
            $this->resolveScanner();
        }

        return $this->scanner !== null && $this->scanner->isAvailable();
    }

    private function resolveScanner(): void
    {
        try {
            $scanner = new ClamAvVirusScanner();

            if ($scanner->isAvailable()) {
                $this->scanner = $scanner;

                return;
            }
        } catch (\Throwable $e) {
            Log::info('ClamAV scanner not available, falling back to basic scanner', ['error' => $e->getMessage()]);
        }

        $this->scanner = new BasicImageScanner();
    }
}
