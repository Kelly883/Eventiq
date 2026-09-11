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
        // 1. Try external API first (VirusTotal, etc.)
        try {
            $apiScanner = new VirusTotalScanner();
            if ($apiScanner->isAvailable()) {
                $this->scanner = $apiScanner;
                Log::info('Using VirusTotal scanner');

                return;
            }
        } catch (\Throwable $e) {
            Log::info('VirusTotal scanner not available', ['error' => $e->getMessage()]);
        }

        // 2. Try ClamAV if available locally
        try {
            $clamAvScanner = new ClamAvVirusScanner();
            if ($clamAvScanner->isAvailable()) {
                $this->scanner = $clamAvScanner;
                Log::info('Using ClamAV scanner');

                return;
            }
        } catch (\Throwable $e) {
            Log::info('ClamAV scanner not available', ['error' => $e->getMessage()]);
        }

        // 3. Fall back to basic validation
        $this->scanner = new BasicImageScanner();
        Log::info('Using basic image scanner fallback');
    }
}
