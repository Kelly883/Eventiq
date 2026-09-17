<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add unique index on refund_policies.organizer_id
        if (! Schema::hasTable('refund_policies')) {
            return;
        }

        if (! $this->indexExists('refund_policies', 'refund_policies_organizer_id_unique')) {
            try {
                Schema::table('refund_policies', function (Blueprint $table) {
                    $table->unique('organizer_id', 'refund_policies_organizer_id_unique');
                });
            } catch (\Throwable $e) {
                // Index may already exist on other platforms
            }
        }

        // Add unique index on refund_appeals.refund_request_id
        // to prevent duplicate appeals for the same refund request
        if (! Schema::hasTable('refund_appeals')) {
            return;
        }

        if (! $this->indexExists('refund_appeals', 'refund_appeals_refund_request_id_unique')) {
            try {
                Schema::table('refund_appeals', function (Blueprint $table) {
                    $table->unique('refund_request_id', 'refund_appeals_refund_request_id_unique');
                });
            } catch (\Throwable $e) {
                // Index may already exist on other platforms
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list({$table})");
            foreach ($indexes as $row) {
                if ($row->name === $index) {
                    return true;
                }
            }

            return false;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $index]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = current_schema() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );

        return $row !== null;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('refund_policies')) {
            try {
                Schema::table('refund_policies', function (Blueprint $table) {
                    $table->dropUnique('refund_policies_organizer_id_unique');
                });
            } catch (\Throwable $e) {
                // Index may not exist
            }
        }

        if (Schema::hasTable('refund_appeals')) {
            try {
                Schema::table('refund_appeals', function (Blueprint $table) {
                    $table->dropUnique('refund_appeals_refund_request_id_unique');
                });
            } catch (\Throwable $e) {
                // Index may not exist
            }
        }
    }
};
