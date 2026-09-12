<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // SQLite does not support partial unique indexes, so we enforce
            // the "one active window per event+tier" rule with a trigger that
            // raises an error when an INSERT or UPDATE would create an overlap.
            DB::unprepared('
                CREATE TRIGGER IF NOT EXISTS trg_pricing_windows_prevent_overlap
                BEFORE INSERT ON pricing_windows
                FOR EACH ROW
                WHEN NEW.is_active = 1 AND NEW.deleted_at IS NULL
                BEGIN
                    SELECT
                        CASE
                            WHEN EXISTS (
                                SELECT 1 FROM pricing_windows
                                WHERE event_id = NEW.event_id
                                   AND ticket_category_id = NEW.ticket_category_id
                                   AND is_active = 1
                                   AND deleted_at IS NULL
                                   AND (
                                       (start_date_time BETWEEN NEW.start_date_time AND NEW.end_date_time)
                                       OR (end_date_time BETWEEN NEW.start_date_time AND NEW.end_date_time)
                                       OR (start_date_time <= NEW.start_date_time AND end_date_time >= NEW.end_date_time)
                                   )
                            )
                            THEN RAISE(ABORT, "An active pricing window already exists for this ticket category with overlapping dates.")
                        END;
                END
            ');

            DB::unprepared('
                CREATE TRIGGER IF NOT EXISTS trg_pricing_windows_prevent_overlap_update
                BEFORE UPDATE ON pricing_windows
                FOR EACH ROW
                WHEN NEW.is_active = 1 AND NEW.deleted_at IS NULL
                BEGIN
                    SELECT
                        CASE
                            WHEN EXISTS (
                                SELECT 1 FROM pricing_windows
                                WHERE event_id = NEW.event_id
                                   AND ticket_category_id = NEW.ticket_category_id
                                   AND is_active = 1
                                   AND deleted_at IS NULL
                                   AND id != OLD.id
                                   AND (
                                       (start_date_time BETWEEN NEW.start_date_time AND NEW.end_date_time)
                                       OR (end_date_time BETWEEN NEW.start_date_time AND NEW.end_date_time)
                                       OR (start_date_time <= NEW.start_date_time AND end_date_time >= NEW.end_date_time)
                                   )
                            )
                            THEN RAISE(ABORT, "An active pricing window already exists for this ticket category with overlapping dates.")
                        END;
                END
            ');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_pricing_windows_prevent_overlap');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_pricing_windows_prevent_overlap_update');
        }
    }
};
