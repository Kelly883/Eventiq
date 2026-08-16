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

        $indexes = DB::select('PRAGMA index_list(refund_policies)');
        $hasOrganizerUnique = false;
        foreach ($indexes as $index) {
            if ($index->name === 'refund_policies_organizer_id_unique') {
                $hasOrganizerUnique = true;
                break;
            }
        }

        if (! $hasOrganizerUnique) {
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

        $indexes = DB::select('PRAGMA index_list(refund_appeals)');
        $hasAppealUnique = false;
        foreach ($indexes as $index) {
            if ($index->name === 'refund_appeals_refund_request_id_unique') {
                $hasAppealUnique = true;
                break;
            }
        }

        if (! $hasAppealUnique) {
            try {
                Schema::table('refund_appeals', function (Blueprint $table) {
                    $table->unique('refund_request_id', 'refund_appeals_refund_request_id_unique');
                });
            } catch (\Throwable $e) {
                // Index may already exist on other platforms
            }
        }
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
