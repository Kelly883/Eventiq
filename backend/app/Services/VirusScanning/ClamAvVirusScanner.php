<?php

namespace App\Services\VirusScanning;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ClamAvVirusScanner implements VirusScannerInterface
{
    private string $clamscanPath;

    public function __construct()
    {
        $this->clamscanPath = trim((string) config('services.clamav.path', '/usr/bin/clamscan'));
    }

    public function isAvailable(): bool
    {
        if (!file_exists($this->clamscanPath) || !is_executable($this->clamscanPath)) {
            return false;
        }

        try {
            $output = [];
            $returnCode = 0;
            exec($this->clamscanPath . ' --version 2>&1', $output, $returnCode);

            return $returnCode === 0 && !empty($output);
        } catch (\Throwable $e) {
            Log::warning('ClamAV availability check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function scan(UploadedFile $file): ScanResult
    {
        if (!$this->isAvailable()) {
            return ScanResult::unavailable('ClamAV scanner is not available');
        }

        $tempPath = $file->getRealPath();

        if (!$tempPath || !file_exists($tempPath)) {
            return ScanResult::unavailable('Temporary file not found');
        }

        try {
            $output = [];
            $returnCode = 0;
            $command = escapeshellcmd($this->clamscanPath) . ' --no-summary ' . escapeshellarg($tempPath) . ' 2>&1';
            exec($command, $output, $returnCode);

            $rawOutput = implode("\n", $output);

            // ClamAV return codes: 0 = clean, 1 = infected, 2 = error
            if ($returnCode === 0) {
                return ScanResult::clean('clamav');
            }

            if ($returnCode === 1) {
                $threatName = $this->parseThreatName($rawOutput);

                return ScanResult::infected($threatName, $rawOutput);
            }

            Log::warning('ClamAV scan error', ['return_code' => $returnCode, 'output' => $rawOutput]);

            return ScanResult::unavailable("ClamAV scan failed with code {$returnCode}: {$rawOutput}");
        } catch (\Throwable $e) {
            Log::error('ClamAV scan exception', ['error' => $e->getMessage()]);

            return ScanResult::unavailable($e->getMessage());
        }
    }

    private function parseThreatName(string $rawOutput): ?string
    {
        if (preg_match('/: (.+?) FOUND$/', $rawOutput, $matches)) {
            return $matches[1];
        }

        return 'Unknown threat';
    }
}
