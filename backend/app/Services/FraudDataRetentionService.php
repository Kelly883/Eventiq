<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Features\Fraud\Models\FraudEvent;

class FraudDataRetentionService
{
    /**
     * Archive fraud events older than retention period.
     *
     * Compliance requirement: Keep fraud records for 7 years (2555 days).
     * After retention period, move to cold storage (archived status).
     *
     * @param int $retentionDays Default 2555 days (7 years)
     * @return int Number of records archived
     */
    public function archiveOldRecords(int $retentionDays = 2555): int
    {
        $cutoffDate = now()->subDays($retentionDays);
        
        $archivedCount = FraudEvent::where('created_at', '<', $cutoffDate)
            ->where('is_archived', false)
            ->update([
                'is_archived' => true,
                'archived_at' => now(),
            ]);

        if ($archivedCount > 0) {
            Log::info("Fraud data retention: Archived {$archivedCount} records older than {$retentionDays} days", [
                'cutoff_date' => $cutoffDate->toDateTimeString(),
                'archived_count' => $archivedCount,
            ]);
        }

        return $archivedCount;
    }

    /**
     * Get statistics about data retention status.
     *
     * @return array
     */
    public function getRetentionStatistics(): array
    {
        $totalRecords = FraudEvent::count();
        $archivedRecords = FraudEvent::where('is_archived', true)->count();
        $activeRecords = FraudEvent::where('is_archived', false)->count();
        
        $oldestRecord = FraudEvent::orderBy('created_at', 'asc')->first();
        $newestRecord = FraudEvent::orderBy('created_at', 'desc')->first();

        $recordsNearRetentionLimit = FraudEvent::where('created_at', '<', now()->subDays(2550))
            ->where('is_archived', false)
            ->count();

        return [
            'total_records' => $totalRecords,
            'active_records' => $activeRecords,
            'archived_records' => $archivedRecords,
            'oldest_record_date' => $oldestRecord?->created_at,
            'newest_record_date' => $newestRecord?->created_at,
            'records_nearing_retention_limit' => $recordsNearRetentionLimit,
            'retention_period_days' => 2555,
            'retention_period_years' => 7,
        ];
    }

    /**
     * Verify data integrity for archived records.
     *
     * @return array
     */
    public function verifyArchivedDataIntegrity(): array
    {
        $issues = [];

        // Check for orphaned records (order/user deleted but fraud event remains)
        $orphanedFraudEvents = FraudEvent::where('is_archived', true)
            ->whereDoesntHave('order')
            ->count();

        if ($orphanedFraudEvents > 0) {
            $issues[] = "Found {$orphanedFraudEvents} archived fraud events with missing orders";
        }

        $orphanedUsers = FraudEvent::where('is_archived', true)
            ->whereDoesntHave('user')
            ->count();

        if ($orphanedUsers > 0) {
            $issues[] = "Found {$orphanedUsers} archived fraud events with missing users";
        }

        // Check for records without required fields
        $incompleteRecords = FraudEvent::where('is_archived', true)
            ->whereNull('fraud_type')
            ->orWhereNull('risk_level')
            ->orWhereNull('detection_method')
            ->count();

        if ($incompleteRecords > 0) {
            $issues[] = "Found {$incompleteRecords} archived records with missing required fields";
        }

        return [
            'is_valid' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Export archived records for cold storage (CSV/JSON).
     *
     * @param string $format 'csv' or 'json'
     * @param int $batchSize
     * @return string
     */
    public function exportArchivedRecords(string $format = 'json', int $batchSize = 1000): string
    {
        $fileName = 'fraud_events_archive_' . now()->format('Y_m_d_H_i_s') . '.' . $format;
        $filePath = storage_path('app/archives/' . $fileName);

        // Ensure directory exists
        if (!file_exists(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        $handle = fopen($filePath, 'w');

        if ($format === 'csv') {
            // Write CSV header
            fputcsv($handle, [
                'id', 'order_id', 'user_id', 'fraud_type', 'risk_score', 'risk_level',
                'detection_method', 'status', 'created_at', 'archived_at',
                'payment_details', 'velocity_metrics', 'device_info'
            ]);
        }

        FraudEvent::where('is_archived', true)
            ->orderBy('created_at', 'asc')
            ->chunk($batchSize, function ($records) use ($handle, $format) {
                foreach ($records as $record) {
                    if ($format === 'json') {
                        fwrite($handle, json_encode($record) . "\n");
                    } else {
                        fputcsv($handle, [
                            $record->id,
                            $record->order_id,
                            $record->user_id,
                            $record->fraud_type,
                            $record->risk_score,
                            $record->risk_level,
                            $record->detection_method,
                            $record->status,
                            $record->created_at,
                            $record->archived_at,
                            $record->payment_details,
                            $record->velocity_metrics,
                            $record->device_info,
                        ]);
                    }
                }
            });

        fclose($handle);

        Log::info("Fraud data retention: Exported archived records to {$filePath}");

        return $filePath;
    }
}