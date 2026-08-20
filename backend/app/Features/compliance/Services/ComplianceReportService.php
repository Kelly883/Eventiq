<?php

namespace App\Features\Compliance\Services;

use App\Features\Checkout\Models\Order;
use Illuminate\Support\Facades\Log;

class ComplianceReportService
{
    public function generate(string $reportCode, array $filters = []): array
    {
        $batchSize = max(1, (int) ($filters['batch_size'] ?? config('compliance.reports.batch_size', 250)));
        $summary = [
            'report_code' => $reportCode,
            'processed' => 0,
            'total_amount' => 0.0,
            'status_counts' => [],
        ];

        $query = Order::query()->select(['id', 'status', 'total_amount']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }
        if (! empty($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        $query->chunkById($batchSize, function ($orders) use (&$summary, $batchSize) {
            foreach ($orders as $order) {
                $summary['processed']++;
                $summary['total_amount'] += (float) $order->total_amount;
                $status = (string) $order->status;
                $summary['status_counts'][$status] = ($summary['status_counts'][$status] ?? 0) + 1;
            }

            Log::info('ComplianceReportService progress', [
                'batch_size' => $batchSize,
                'processed' => $summary['processed'],
            ]);
        });

        return [
            'queued' => false,
            'result_location' => null,
            'summary' => $summary,
        ];
    }
}
