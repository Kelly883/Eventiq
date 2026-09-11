<?php

namespace App\Services\VirusScanning;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VirusTotalScanner implements VirusScannerInterface
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('services.virustotal.api_key');
        $this->baseUrl = rtrim((string) config('services.virustotal.base_url', 'https://www.virustotal.com/api/v3'), '/');
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function scan(UploadedFile $file): ScanResult
    {
        if (!$this->isAvailable()) {
            return ScanResult::unavailable('VirusTotal API key not configured');
        }

        try {
            $fileHash = hash_file('sha256', $file->getRealPath());
            
            // First, check if we've already scanned this file
            $reportResponse = Http::withHeaders([
                'x-apikey' => $this->apiKey,
            ])->get("{$this->baseUrl}/files/{$fileHash}");

            if ($reportResponse->successful() && $reportResponse->json('data.attributes.analysis_stats')) {
                $stats = $reportResponse->json('data.attributes.analysis_stats');
                $malicious = $stats['malicious'] ?? 0;
                $suspicious = $stats['suspicious'] ?? 0;

                if ($malicious > 0 || $suspicious > 0) {
                    return ScanResult::infected("VirusTotal detected {$malicious} malicious and {$suspicious} suspicious engines");
                }

                return ScanResult::clean('virustotal');
            }

            // Upload file for scanning
            $uploadResponse = Http::withHeaders([
                'x-apikey' => $this->apiKey,
            ])->attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post("{$this->baseUrl}/files");

            if (!$uploadResponse->successful()) {
                Log::warning('VirusTotal upload failed', ['status' => $uploadResponse->status()]);
                return ScanResult::unavailable('Failed to upload to VirusTotal');
            }

            $uploadData = $uploadResponse->json('data');
            $analysisId = $uploadData['id'] ?? null;

            if (!$analysisId) {
                return ScanResult::unavailable('No analysis ID returned from VirusTotal');
            }

            // Poll for results (with timeout)
            $maxAttempts = 10;
            $delay = 2; // seconds

            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                sleep($delay);
                
                $analysisResponse = Http::withHeaders([
                    'x-apikey' => $this->apiKey,
                ])->get("{$this->baseUrl}/analyses/{$analysisId}");

                if ($analysisResponse->successful()) {
                    $analysis = $analysisResponse->json('data.attributes');
                    $stats = $analysis['stats'] ?? [];
                    $status = $analysis['status'] ?? 'pending';

                    if ($status === 'completed') {
                        $malicious = $stats['malicious'] ?? 0;
                        $suspicious = $stats['suspicious'] ?? 0;

                        if ($malicious > 0 || $suspicious > 0) {
                            return ScanResult::infected("VirusTotal detected {$malicious} malicious and {$suspicious} suspicious engines");
                        }

                        return ScanResult::clean('virustotal');
                    }
                }
            }

            return ScanResult::unavailable('VirusTotal analysis timeout');
        } catch (\Throwable $e) {
            Log::error('VirusTotal scan exception', ['error' => $e->getMessage()]);
            return ScanResult::unavailable($e->getMessage());
        }
    }
}
