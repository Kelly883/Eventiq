<?php

namespace App\Console\Commands;

use App\Features\Refunds\Models\RefundRequest;
use App\Features\Refunds\Models\RefundPolicy;
use App\Features\Refunds\Models\RefundAppeal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VerifyRefundMigrations extends Command
{
    protected $signature = 'refund:verify-step74';
    protected $description = 'Verify refund tables migrations (Step 74) - tables, fields, indexes, FKs, model counts';

    public function handle()
    {
        $this->info('============================================================');
        $this->info(' STEP 74 VERIFICATION: Refund Tables Migrations');
        $this->info('============================================================');
        $this->newLine();

        $errors = [];
        $warnings = [];

        // ============================================================
        // 1. TABLE EXISTENCE
        // ============================================================
        $this->line('[1] TABLE EXISTENCE');
        $this->line(str_repeat('-', 60));

        $tables = ['refund_requests', 'refund_policies', 'refund_appeals'];
        foreach ($tables as $table) {
            $exists = Schema::hasTable($table);
            if (!$exists) {
                $errors[] = "Table '$table' does not exist";
            }
            $this->line(sprintf('    %-25s %s', $table, $exists ? 'YES' : 'FAIL'));
        }
        $this->newLine();

        // ============================================================
        // 2. ELOQUENT MODEL COUNTS (RefundRequest::count() etc.)
        // ============================================================
        $this->line('[2] ELOQUENT MODEL COUNTS');
        $this->line(str_repeat('-', 60));

        try {
            $rrCount = RefundRequest::count();
            $this->line(sprintf('    RefundRequest::count()  => %d %s', $rrCount, $rrCount === 0 ? 'OK' : '(expected 0)'));
            if ($rrCount !== 0) {
                $warnings[] = "RefundRequest::count() returned $rrCount (expected 0)";
            }
        } catch (\Exception $e) {
            $errors[] = "RefundRequest::count() failed: " . $e->getMessage();
            $this->error('    RefundRequest::count() ERROR: ' . $e->getMessage());
        }

        try {
            $rpCount = RefundPolicy::count();
            $this->line(sprintf('    RefundPolicy::count()   => %d %s', $rpCount, $rpCount === 0 ? 'OK' : '(expected 0)'));
            if ($rpCount !== 0) {
                $warnings[] = "RefundPolicy::count() returned $rpCount (expected 0)";
            }
        } catch (\Exception $e) {
            $errors[] = "RefundPolicy::count() failed: " . $e->getMessage();
            $this->error('    RefundPolicy::count() ERROR: ' . $e->getMessage());
        }

        try {
            $raCount = RefundAppeal::count();
            $this->line(sprintf('    RefundAppeal::count()   => %d %s', $raCount, $raCount === 0 ? 'OK' : '(expected 0)'));
            if ($raCount !== 0) {
                $warnings[] = "RefundAppeal::count() returned $raCount (expected 0)";
            }
        } catch (\Exception $e) {
            $errors[] = "RefundAppeal::count() failed: " . $e->getMessage();
            $this->error('    RefundAppeal::count() ERROR: ' . $e->getMessage());
        }

        // RefundRequest::all()
        $this->newLine();
        $this->line('    RefundRequest::all() test:');
        try {
            $all = RefundRequest::all();
            $this->line(sprintf('      ->all() returned %d record(s) %s', $all->count(), $all->count() === 0 ? 'OK' : '(expected 0)'));
        } catch (\Exception $e) {
            $errors[] = "RefundRequest::all() failed: " . $e->getMessage();
            $this->error('      ->all() ERROR: ' . $e->getMessage());
        }
        $this->newLine();

        // ============================================================
        // 3. REFUND_REQUESTS SCHEMA
        // ============================================================
        $this->line('[3] REFUND_REQUESTS TABLE SCHEMA');
        $this->line(str_repeat('-', 60));

        if (Schema::hasTable('refund_requests')) {
            $cols = Schema::getColumnListing('refund_requests');
            $expected = [
                'id', 'ticket_id', 'order_id', 'event_id', 'user_id', 'refund_policy_id',
                'status', 'requested_amount', 'approved_amount', 'original_amount',
                'refund_amount', 'refund_percentage', 'reason', 'explanation',
                'refund_method', 'rejection_reason', 'admin_notes', 'approved_by',
                'approved_at', 'reviewed_at', 'reviewed_by', 'processing_started_at',
                'completed_at', 'payment_gateway_refund_id', 'payment_gateway_response',
                'appeal_count', 'last_appeal_at', 'created_at', 'updated_at'
            ];
            $missing = array_diff($expected, $cols);
            $this->line(sprintf('    Columns: %d/%d present %s', count($expected) - count($missing), count($expected), empty($missing) ? 'OK' : 'FAIL'));
            if (!empty($missing)) {
                $errors[] = "refund_requests missing columns: " . implode(', ', $missing);
                $this->line('    Missing: ' . implode(', ', $missing));
            }

            // Indexes
            $this->line('    Indexes:');
            $driver = DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='refund_requests' AND name NOT LIKE 'sqlite_%'");
                $indexNames = array_column($indexes, 'name');
            } else {
                $indexes = DB::select("SHOW INDEX FROM refund_requests");
                $indexNames = array_column($indexes, 'Key_name');
            }
            $expectedIndexes = ['idx_refund_user_status', 'idx_refund_event_status', 'refund_requests_ticket_id_index'];
            foreach ($expectedIndexes as $idx) {
                $exists = in_array($idx, $indexNames);
                if (!$exists) {
                    $errors[] = "refund_requests missing index: $idx";
                }
                $this->line(sprintf('      %-40s %s', $idx, $exists ? 'OK' : 'FAIL'));
            }

            // Foreign keys
            $this->line('    Foreign Keys:');
            if ($driver === 'sqlite') {
                $fks = DB::select("PRAGMA foreign_key_list(refund_requests)");
                $expectedFKs = [
                    'ticket_id'        => 'tickets',
                    'user_id'          => 'users',
                    'refund_policy_id' => 'refund_policies',
                    'reviewed_by'      => 'users',
                ];
                foreach ($expectedFKs as $col => $refTable) {
                    $found = false;
                    foreach ($fks as $fk) {
                        if ($fk->from === $col && $fk->table === $refTable) {
                            $found = true;
                            $this->line(sprintf('      %-20s OK (-> %s, on_delete=%s)', $col, $fk->table, $fk->on_delete));
                            break;
                        }
                    }
                    if (!$found) {
                        $errors[] = "refund_requests missing FK: $col -> $refTable";
                        $this->line(sprintf('      %-20s FAIL (missing FK to %s)', $col, $refTable));
                    }
                }
            } else {
                $this->line('      (FK check skipped for non-SQLite driver)');
            }
        }
        $this->newLine();

        // ============================================================
        // 4. REFUND_POLICIES SCHEMA
        // ============================================================
        $this->line('[4] REFUND_POLICIES TABLE SCHEMA');
        $this->line(str_repeat('-', 60));

        if (Schema::hasTable('refund_policies')) {
            $cols = Schema::getColumnListing('refund_policies');
            $expected = [
                'id', 'event_id', 'organizer_id', 'name', 'description',
                'refund_window_days', 'refund_percentage', 'refund_percentage_before_event',
                'refund_percentage_after_event_start', 'allow_refunds_after_event_start',
                'processing_time_business_days', 'allowed_refund_methods', 'requires_approval',
                'auto_approve_threshold', 'max_refunds_per_user', 'refund_reasons',
                'cancellation_policy', 'is_active', 'created_at', 'updated_at'
            ];
            $missing = array_diff($expected, $cols);
            $this->line(sprintf('    Columns: %d/%d present %s', count($expected) - count($missing), count($expected), empty($missing) ? 'OK' : 'FAIL'));
            if (!empty($missing)) {
                $errors[] = "refund_policies missing columns: " . implode(', ', $missing);
                $this->line('    Missing: ' . implode(', ', $missing));
            }

            // Foreign keys
            $this->line('    Foreign Keys:');
            $driver = DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                $fks = DB::select("PRAGMA foreign_key_list(refund_policies)");
                $eventFK = array_filter($fks, fn($fk) => $fk->from === 'event_id');
                if (!empty($eventFK)) {
                    $fk = reset($eventFK);
                    $this->line(sprintf('      event_id OK (-> %s, on_delete=%s)', $fk->table, $fk->on_delete));
                } else {
                    $errors[] = "refund_policies missing FK on event_id";
                    $this->line('      event_id FAIL (missing FK)');
                }
            }
        }
        $this->newLine();

        // ============================================================
        // 5. REFUND_APPEALS SCHEMA
        // ============================================================
        $this->line('[5] REFUND_APPEALS TABLE SCHEMA');
        $this->line(str_repeat('-', 60));

        if (Schema::hasTable('refund_appeals')) {
            $cols = Schema::getColumnListing('refund_appeals');
            $expected = [
                'id', 'refund_request_id', 'user_id', 'appeal_reason', 'reason',
                'status', 'admin_notes', 'review_notes', 'reviewed_by', 'reviewed_at',
                'created_at', 'updated_at'
            ];
            $missing = array_diff($expected, $cols);
            $this->line(sprintf('    Columns: %d/%d present %s', count($expected) - count($missing), count($expected), empty($missing) ? 'OK' : 'FAIL'));
            if (!empty($missing)) {
                $errors[] = "refund_appeals missing columns: " . implode(', ', $missing);
                $this->line('    Missing: ' . implode(', ', $missing));
            }

            // Foreign keys
            $this->line('    Foreign Keys:');
            $driver = DB::connection()->getDriverName();
            if ($driver === 'sqlite') {
                $fks = DB::select("PRAGMA foreign_key_list(refund_appeals)");
                $expectedFKs = [
                    'refund_request_id' => 'refund_requests',
                    'user_id'           => 'users',
                    'reviewed_by'       => 'users',
                ];
                foreach ($expectedFKs as $col => $refTable) {
                    $found = false;
                    foreach ($fks as $fk) {
                        if ($fk->from === $col && $fk->table === $refTable) {
                            $found = true;
                            $this->line(sprintf('      %-20s OK (-> %s, on_delete=%s)', $col, $fk->table, $fk->on_delete));
                            break;
                        }
                    }
                    if (!$found) {
                        $errors[] = "refund_appeals missing FK: $col -> $refTable";
                        $this->line(sprintf('      %-20s FAIL (missing FK to %s)', $col, $refTable));
                    }
                }
            }
        }
        $this->newLine();

        // ============================================================
        // 6. SUMMARY
        // ============================================================
        $this->info('============================================================');
        $this->info(' VERIFICATION SUMMARY');
        $this->info('============================================================');
        $this->newLine();

        if (empty($errors)) {
            $this->info('ALL CRITICAL CHECKS PASSED');
        } else {
            $this->error('ERRORS FOUND:');
            foreach ($errors as $e) {
                $this->error('  - ' . $e);
            }
        }

        if (!empty($warnings)) {
            $this->newLine();
            $this->warn('WARNINGS:');
            foreach ($warnings as $w) {
                $this->warn('  - ' . $w);
            }
        }

        $this->newLine();
        $this->info('=== Step 74 Verification Complete ===');

        return empty($errors) ? 0 : 1;
    }
}