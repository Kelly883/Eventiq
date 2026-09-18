<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->createSqliteTriggers();
        } elseif ($driver === 'pgsql') {
            $this->createPostgreSqlTriggers();
        }
    }

    private function createSqliteTriggers(): void
    {
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

    private function createPostgreSqlTriggers(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_pricing_windows_prevent_overlap ON pricing_windows');
        DB::statement('DROP TRIGGER IF EXISTS trg_pricing_windows_prevent_overlap_update ON pricing_windows');
        DB::statement('DROP FUNCTION IF EXISTS trg_pricing_windows_prevent_overlap()');
        DB::statement('DROP FUNCTION IF EXISTS trg_pricing_windows_prevent_overlap_update()');

        DB::statement('
            CREATE OR REPLACE FUNCTION trg_pricing_windows_prevent_overlap()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF NEW.is_active = true AND NEW.deleted_at IS NULL THEN
                    IF EXISTS (
                        SELECT 1 FROM pricing_windows
                        WHERE event_id = NEW.event_id
                          AND ticket_category_id = NEW.ticket_category_id
                          AND is_active = true
                          AND deleted_at IS NULL
                          AND (
                              (NEW.start_date_time BETWEEN start_date_time AND end_date_time)
                              OR (NEW.end_date_time BETWEEN start_date_time AND end_date_time)
                              OR (start_date_time <= NEW.start_date_time AND end_date_time >= NEW.end_date_time)
                          )
                    ) THEN
                        RAISE EXCEPTION "An active pricing window already exists for this ticket category with overlapping dates.";
                    END IF;
                END IF;
                RETURN NEW;
            END;
            $$');
        DB::statement('CREATE TRIGGER trg_pricing_windows_prevent_overlap BEFORE INSERT ON pricing_windows FOR EACH ROW EXECUTE FUNCTION trg_pricing_windows_prevent_overlap()');

        DB::statement('
            CREATE OR REPLACE FUNCTION trg_pricing_windows_prevent_overlap_update()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                IF NEW.is_active = true AND NEW.deleted_at IS NULL THEN
                    IF EXISTS (
                        SELECT 1 FROM pricing_windows
                        WHERE event_id = NEW.event_id
                          AND ticket_category_id = NEW.ticket_category_id
                          AND is_active = true
                          AND deleted_at IS NULL
                          AND id != OLD.id
                          AND (
                              (NEW.start_date_time BETWEEN start_date_time AND end_date_time)
                              OR (NEW.end_date_time BETWEEN start_date_time AND end_date_time)
                              OR (start_date_time <= NEW.start_date_time AND end_date_time >= NEW.end_date_time)
                          )
                    ) THEN
                        RAISE EXCEPTION "An active pricing window already exists for this ticket category with overlapping dates.";
                    END IF;
                END IF;
                RETURN NEW;
            END;
            $$');
        DB::statement('CREATE TRIGGER trg_pricing_windows_prevent_overlap_update BEFORE UPDATE ON pricing_windows FOR EACH ROW EXECUTE FUNCTION trg_pricing_windows_prevent_overlap_update()');
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_pricing_windows_prevent_overlap');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_pricing_windows_prevent_overlap_update');
        } elseif ($driver === 'pgsql') {
            $this->dropPostgreSqlTriggers();
        }
    }

    private function dropPostgreSqlTriggers(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_pricing_windows_prevent_overlap ON pricing_windows');
        DB::statement('DROP TRIGGER IF EXISTS trg_pricing_windows_prevent_overlap_update ON pricing_windows');
        DB::statement('DROP FUNCTION IF EXISTS trg_pricing_windows_prevent_overlap()');
        DB::statement('DROP FUNCTION IF EXISTS trg_pricing_windows_prevent_overlap_update()');
    }
};
