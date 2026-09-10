<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure organizer_id + start_datetime composite for GET /organizers/{id}/events
        // Already added in 2026_07_22_065001 as idx_events_organizer_date, but ensure idempotent
        if (Schema::hasTable('events') && Schema::hasColumn('events', 'organizer_id') && Schema::hasColumn('events', 'start_datetime')) {
            $hasIndex = false;
            try {
                $indexes = DB::select("SELECT name, sql FROM sqlite_master WHERE type='index' AND tbl_name='events'");
                foreach ($indexes as $idx) {
                    if (str_contains($idx->sql ?? '', 'organizer_id') && str_contains($idx->sql ?? '', 'start_datetime')) {
                        $hasIndex = true;
                        break;
                    }
                    if (($idx->name ?? '') === 'idx_events_organizer_date') {
                        $hasIndex = true;
                        break;
                    }
                }
                if (!$hasIndex) {
                    // Check via Doctrine for PG/MySQL
                    try {
                        $sm = DB::connection()->getDoctrineSchemaManager();
                        $idxs = $sm->listTableIndexes('events');
                        foreach ($idxs as $name => $idx) {
                            $cols = array_map('strtolower', $idx->getColumns());
                            if (in_array('organizer_id', $cols) && in_array('start_datetime', $cols)) {
                                $hasIndex = true;
                                break;
                            }
                        }
                    } catch (\Throwable $e) {
                    }
                }
            } catch (\Throwable $e) {
            }

            if (!$hasIndex) {
                Schema::table('events', function (Blueprint $table) {
                    $table->index(['organizer_id', 'start_datetime'], 'idx_events_organizer_date');
                });
            }
        }
    }

    public function down(): void
    {
        // Do not drop — keep for performance
    }
};
