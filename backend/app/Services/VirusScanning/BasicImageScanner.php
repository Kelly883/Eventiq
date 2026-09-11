<?php

namespace App\Services\VirusScanning;

use Illuminate\Http\UploadedFile;

class BasicImageScanner implements VirusScannerInterface
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function scan(UploadedFile $file): ScanResult
    {
        $tempPath = $file->getRealPath();

        if (!$tempPath || !file_exists($tempPath)) {
            return ScanResult::unavailable('Temporary file not found');
        }

        try {
            // 1. Check file size (reject obviously suspicious files)
            $size = filesize($tempPath);
            if ($size > 10 * 1024 * 1024) { // 10MB hard limit
                return ScanResult::infected('File too large for safe processing');
            }

            // 2. Validate actual image content using getimagesize
            $imageInfo = @getimagesize($tempPath);
            if ($imageInfo === false || !in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
                return ScanResult::infected('Invalid image content or non-image file');
            }

            // 3. Check for suspicious patterns in first bytes
            $handle = fopen($tempPath, 'rb');
            if ($handle === false) {
                return ScanResult::unavailable('Cannot open file for inspection');
            }

            $header = fread($handle, 512);
            fclose($handle);

            // Check for PHP/executable signatures in image files
            $suspiciousPatterns = [
                '<?php',
                '<?=',
                '<script',
                'eval(',
                'exec(',
                'system(',
                'passthru(',
                'shell_exec(',
                'proc_open(',
                'popen(',
                'curl_exec(',
                'base64_decode(',
                'gzuncompress(',
                'str_rot13(',
                'assert(',
                'pcntl_exec(',
            ];

            $lowerHeader = strtolower($header);
            foreach ($suspiciousPatterns as $pattern) {
                if (str_contains($lowerHeader, $pattern)) {
                    return ScanResult::infected("Suspicious pattern detected: {$pattern}");
                }
            }

            return ScanResult::clean('basic-image-validation');
        } catch (\Throwable $e) {
            return ScanResult::unavailable($e->getMessage());
        }
    }
}
