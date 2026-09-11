<?php

namespace App\Services\VirusScanning;

use Illuminate\Http\UploadedFile;

interface VirusScannerInterface
{
    /**
     * Scan a file for viruses/malware.
     *
     * @return ScanResult
     */
    public function scan(UploadedFile $file): ScanResult;

    /**
     * Check if the scanner is available and configured.
     */
    public function isAvailable(): bool;
}
