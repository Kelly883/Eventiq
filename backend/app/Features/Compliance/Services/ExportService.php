<?php

namespace App\Features\Compliance\Services;

class ExportService
{
    private function maskIp(?string $ip): ?string
    {
        if (!$ip) {
            return null;
        }

        if (str_contains($ip, ':')) {
            $parts = explode(':', $ip);
            return $parts[0] . ':xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx';
        }

        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.xxx.xxx.xxx';
        }

        return $ip;
    }

    private function sanitizeRecord(array $record): array
    {
        return [
            'id' => $record['id'] ?? null,
            'user_id' => $record['user_id'] ?? null,
            'action' => $record['action'] ?? null,
            'target_type' => $record['target_type'] ?? null,
            'target_id' => $record['target_id'] ?? null,
            'status' => $record['status'] ?? null,
            'compliance_classification' => $record['compliance_classification'] ?? null,
            'ip_address' => $this->maskIp($record['ip_address'] ?? null),
            'created_at' => $record['created_at'] ?? null,
        ];
    }

    public function export($payload, string $format = 'csv'): string
    {
        if ($payload instanceof \Illuminate\Database\Eloquent\Builder || $payload instanceof \Illuminate\Database\Query\Builder) {
            return $this->exportFromQuery($payload, $format);
        }

        $sanitized = array_map([$this, 'sanitizeRecord'], is_array($payload) ? $payload : []);

        if ($format === 'json') {
            return json_encode($sanitized, JSON_PRETTY_PRINT);
        }

        return $this->buildCsv($sanitized);
    }

    private function exportFromQuery($query, string $format): string
    {
        if ($format === 'json') {
            $chunks = [];
            $query->orderBy('created_at')->chunk(1000, function ($rows) use (&$chunks) {
                foreach ($rows as $row) {
                    $chunks[] = $this->sanitizeRecord($row->toArray());
                }
            });

            return json_encode(array_values($chunks), JSON_PRETTY_PRINT);
        }

        $buffer = fopen('php://temp', 'r+');
        $firstChunk = true;

        $query->orderBy('created_at')->chunk(1000, function ($rows) use ($buffer, &$firstChunk) {
            $rows = $rows->map(fn($row) => $this->sanitizeRecord($row->toArray()))->all();
            $csv = $this->buildCsv($rows, $firstChunk);
            fwrite($buffer, $csv);
            $firstChunk = false;
        });

        rewind($buffer);
        $content = stream_get_contents($buffer);
        fclose($buffer);

        return $content;
    }

    private function buildCsv(array $records, bool $includeHeader = true): string
    {
        if (empty($records)) {
            return '';
        }

        $lines = [];
        if ($includeHeader) {
            $headers = array_keys($records[0]);
            $lines[] = implode(',', array_map([$this, 'escapeCsvValue'], $headers));
        }

        foreach ($records as $row) {
            $lines[] = implode(',', array_map([$this, 'escapeCsvValue'], $row));
        }

        return implode("\n", $lines) . "\n";
    }

    private function escapeCsvValue(mixed $value): string
    {
        $string = (string) $value;

        if (str_contains($string, ',') || str_contains($string, '"') || str_contains($string, "\n")) {
            return '"' . str_replace('"', '""', $string) . '"';
        }

        return $string;
    }
}
