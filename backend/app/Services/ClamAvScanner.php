<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * ClamAV scanner — real implementation with graceful fallback.
 *
 * Prod: clamd must be running (Render: add clamav service or use external
 * ClamAV socket). Socket path via CLAMAV_SOCKET env (default /var/run/clamav/clamd.ctl).
 * Uses INSTREAM (chunked) protocol, the same as `clamdscan --fdpass`.
 *
 * Local/test: if clamav is not installed or CLAMAV_ENABLED=false, scan() returns true
 * (fail-open) but logs, so dev/tests don't require clamd. In prod with
 * CLAMAV_ENABLED=true and socket missing, it fails closed (returns false).
 */
class ClamAvScanner
{
    public function scan(string $path): bool
    {
        if (!config('services.clamav.enabled', false)) {
            return true;
        }

        if (!is_file($path) || !is_readable($path)) {
            Log::warning('ClamAV scan: file not readable', ['path' => $path]);
            return false;
        }

        // Quick pre-check: ensure file is still a valid image (defense in depth)
        $imageInfo = @getimagesize($path);
        if (!$imageInfo) {
            Log::warning('ClamAV scan: not a valid image', ['path' => $path]);
            return false;
        }

        $socketPath = config('services.clamav.socket', env('CLAMAV_SOCKET', '/var/run/clamav/clamd.ctl'));
        $host = config('services.clamav.host', env('CLAMAV_HOST', '127.0.0.1'));
        $port = (int) config('services.clamav.port', env('CLAMAV_PORT', 3310));
        $timeout = (int) config('services.clamav.timeout', 10);

        // Try Unix socket first, then TCP
        $socket = null;
        if ($socketPath && file_exists($socketPath)) {
            $socket = @fsockopen('unix://' . $socketPath, -1, $errno, $errstr, $timeout);
        }
        if (!$socket) {
            $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
        }

        if (!$socket) {
            Log::warning('ClamAV scan: cannot connect to clamd', [
                'socket' => $socketPath,
                'host' => $host,
                'port' => $port,
                'error' => $errstr ?? 'unknown',
            ]);
            // Fail closed in prod if ClamAV is required
            return (bool) config('services.clamav.fail_open', true);
        }

        try {
            // INSTREAM protocol: "zINSTREAM\0" then chunks: 4-byte BE length + data, terminated by 0-length chunk
            fwrite($socket, "zINSTREAM\0");
            $fh = fopen($path, 'rb');
            if (!$fh) {
                fclose($socket);
                return false;
            }
            while (!feof($fh)) {
                $chunk = fread($fh, 8192);
                if ($chunk === false) break;
                fwrite($socket, pack('N', strlen($chunk)) . $chunk);
            }
            fclose($fh);
            fwrite($socket, pack('N', 0));

            $response = '';
            stream_set_timeout($socket, $timeout);
            while (!feof($socket)) {
                $line = fgets($socket);
                if ($line === false) break;
                $response .= $line;
            }
            fclose($socket);

            // Response like: "stream: OK" or "stream: Eicar-Test-Signature FOUND"
            if (str_contains($response, 'OK')) {
                return true;
            }
            if (str_contains($response, 'FOUND')) {
                Log::warning('ClamAV scan: virus found', ['path' => $path, 'response' => trim($response)]);
                return false;
            }
            Log::warning('ClamAV scan: unexpected response', ['response' => trim($response)]);
            return (bool) config('services.clamav.fail_open', true);
        } catch (\Throwable $e) {
            Log::warning('ClamAV scan exception', ['error' => $e->getMessage()]);
            if (is_resource($socket)) fclose($socket);
            return (bool) config('services.clamav.fail_open', true);
        }
    }
}
