<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Step 70 QR code and check-in fields to the tickets table.
     * The initial tickets table (2026_07_06_100001) was created before
     * Step 70 was designed, so these fields need to be added now.
     *
     * Fields added:
     * - qr_code_data: base64-encoded encrypted QR payload
     * - qr_code_secret: bcrypt-hashed secret for QR verification
     * - qr_code_generated_at: timestamp of QR generation
     * - qr_code_expires_at: expiry (typically event end + 7 days)
     * - checked_in_at: already exists from earlier migration
     * - checked_in_by: convert legacy BIGINT to UUID if needed, then ensure proper FK to users
     * - qr_code_scanned_count: scan counter
     * - last_qr_scan_at: timestamp of last scan
     *
     * Indexes:
     * - (event_id, status): already exists
     * - (event_id, checked_in_at): already exists
     * - (qr_code_expires_at): for finding expired QRs
     */
    public function up(): void
    {
        $hasTicketsQrCodeData = Schema::hasColumn('tickets', 'qr_code_data');
        $hasTicketsQrCodeSecret = Schema::hasColumn('tickets', 'qr_code_secret');
        $hasTicketsQrCodeGeneratedAt = Schema::hasColumn('tickets', 'qr_code_generated_at');
        $hasTicketsQrCodeExpiresAt = Schema::hasColumn('tickets', 'qr_code_expires_at');
        $hasTicketsQrCodeScannedCount = Schema::hasColumn('tickets', 'qr_code_scanned_count');
        $hasTicketsLastQrScanAt = Schema::hasColumn('tickets', 'last_qr_scan_at');
        $hasTicketsCheckedInBy = Schema::hasColumn('tickets', 'checked_in_by');

        $needsUuidConversion = false;

        if ($hasTicketsCheckedInBy) {
            $driver = DB::getDriverName();
            $checkedInByType = null;

            if ($driver === 'sqlite') {
                $columns = DB::select('PRAGMA table_info(tickets)');
                foreach ($columns as $col) {
                    if ($col->name === 'checked_in_by') {
                        $checkedInByType = $col->type ?? null;
                        break;
                    }
                }
            } elseif ($driver === 'pgsql') {
                $row = DB::selectOne(
                    'SELECT data_type FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? AND column_name = ?',
                    ['tickets', 'checked_in_by']
                );
                $checkedInByType = $row ? $row->data_type : null;
            } else {
                $columns = DB::select('SHOW COLUMNS FROM tickets WHERE Field = ?', ['checked_in_by']);
                if (!empty($columns)) {
                    $checkedInByType = $columns[0]->Type;
                }
            }

            if ($checkedInByType !== null && stripos($checkedInByType, 'int') !== false) {
                $hasLegacyData = DB::table('tickets')->whereNotNull('checked_in_by')->exists();
                if ($hasLegacyData) {
                    throw new \RuntimeException(
                        'Migration aborted: tickets.checked_in_by contains legacy BIGINT values that cannot be safely converted to UUID.'
                    );
                }
                $needsUuidConversion = true;
            }
        }

        // Fix checked_in_by column type and FK if it is still BIGINT from legacy migration
        if ($needsUuidConversion) {
            Schema::table('tickets', function (Blueprint $table) {
                if ($this->foreignKeyExists('tickets', 'checked_in_by')) {
                    $table->dropForeign(['checked_in_by']);
                }
                $table->dropColumn('checked_in_by');
            });

            Schema::table('tickets', function (Blueprint $table) {
                $table->uuid('checked_in_by')->nullable()->after('checked_in_at');
            });

            Schema::table('tickets', function (Blueprint $table) {
                $table->foreign('checked_in_by')->references('id')->on('users')->onDelete('set null');
            });
        } elseif ($hasTicketsCheckedInBy) {
            Schema::table('tickets', function (Blueprint $table) {
                try {
                    $table->foreign('checked_in_by')->references('id')->on('users')->onDelete('set null');
                } catch (\Exception $e) {
                    // FK may already exist
                }
            });
        }

        // Add QR columns
        Schema::table('tickets', function (Blueprint $table) use ($hasTicketsQrCodeData, $hasTicketsQrCodeSecret, $hasTicketsQrCodeGeneratedAt, $hasTicketsQrCodeExpiresAt, $hasTicketsQrCodeScannedCount, $hasTicketsLastQrScanAt) {
            // ── QR Code Fields ────────────────────────────────────
            if (! $hasTicketsQrCodeData) {
                $table->text('qr_code_data')->nullable()->after('tier');
            }

            if (! $hasTicketsQrCodeSecret) {
                $table->string('qr_code_secret')->nullable()->after('qr_code_data');
            }

            if (! $hasTicketsQrCodeGeneratedAt) {
                $table->timestamp('qr_code_generated_at')->nullable()->after('qr_code_secret');
            }

            if (! $hasTicketsQrCodeExpiresAt) {
                $table->timestamp('qr_code_expires_at')->nullable()->after('qr_code_generated_at');
            }

            // ── QR Scan Tracking ──────────────────────────────────
            if (! $hasTicketsQrCodeScannedCount) {
                $table->integer('qr_code_scanned_count')->default(0)->after('checked_in_by');
            }

            if (! $hasTicketsLastQrScanAt) {
                $table->timestamp('last_qr_scan_at')->nullable()->after('qr_code_scanned_count');
            }
        });

        // ── Indexes ───────────────────────────────────────────────
        // qr_code_expires_at index for finding expired QR codes
        try {
            Schema::table('tickets', function (Blueprint $table) {
                $table->index('qr_code_expires_at', 'idx_tickets_qr_expires');
            });
        } catch (\Exception $e) {
            // Index may already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasTicketsQrCodeData = Schema::hasColumn('tickets', 'qr_code_data');
        $hasTicketsQrCodeSecret = Schema::hasColumn('tickets', 'qr_code_secret');
        $hasTicketsQrCodeGeneratedAt = Schema::hasColumn('tickets', 'qr_code_generated_at');
        $hasTicketsQrCodeExpiresAt = Schema::hasColumn('tickets', 'qr_code_expires_at');
        $hasTicketsQrCodeScannedCount = Schema::hasColumn('tickets', 'qr_code_scanned_count');
        $hasTicketsLastQrScanAt = Schema::hasColumn('tickets', 'last_qr_scan_at');
        Schema::table('tickets', function (Blueprint $table) use ($hasTicketsQrCodeData, $hasTicketsQrCodeSecret, $hasTicketsQrCodeGeneratedAt, $hasTicketsQrCodeExpiresAt, $hasTicketsQrCodeScannedCount, $hasTicketsLastQrScanAt) {
            // Drop index
            try {
                $table->dropIndex('idx_tickets_qr_expires');
            } catch (\Exception $e) {
                // Index may not exist
            }

            // Drop columns that were added
            $columns = [];
            if ($hasTicketsQrCodeData) {
                $columns[] = 'qr_code_data';
            }
            if ($hasTicketsQrCodeSecret) {
                $columns[] = 'qr_code_secret';
            }
            if ($hasTicketsQrCodeGeneratedAt) {
                $columns[] = 'qr_code_generated_at';
            }
            if ($hasTicketsQrCodeExpiresAt) {
                $columns[] = 'qr_code_expires_at';
            }
            if ($hasTicketsQrCodeScannedCount) {
                $columns[] = 'qr_code_scanned_count';
            }
            if ($hasTicketsLastQrScanAt) {
                $columns[] = 'last_qr_scan_at';
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
    private function foreignKeyExists(string $table, string $column): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'sqlite') {
            $rows = DB::select("PRAGMA foreign_key_list('{$table}')");

            foreach ($rows as $row) {
                if (($row->from ?? null) === $column) {
                    return true;
                }
            }

            return false;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT c.conname FROM pg_constraint c '
                . 'JOIN pg_class t ON c.conrelid = t.oid '
                . 'JOIN pg_namespace n ON t.relnamespace = n.oid '
                . "WHERE n.nspname = current_schema() "
                . "AND t.relname = ? "
                . "AND c.contype = 'f' "
                . 'AND EXISTS ('
                . '  SELECT 1 FROM pg_attribute a '
                . '  WHERE a.attrelid = t.oid '
                . '  AND a.attname = ? '
                . '  AND a.attnum = ANY(c.conkey)'
                . ')',
                [$table, $column]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT column_name FROM information_schema.key_column_usage WHERE table_schema = current_schema() AND table_name = ? AND column_name = ? AND referenced_table_name IS NOT NULL',
            [$table, $column]
        );

        return $row !== null;
    }
};