<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins');
            DB::statement("
                CREATE TRIGGER prevent_event_delete_with_checkins
                BEFORE DELETE ON events
                FOR EACH ROW
                BEGIN
                    SELECT RAISE(ABORT, 'Cannot delete event with existing check-ins or tickets. Consider cancelling instead.')
                    WHERE EXISTS (
                        SELECT 1 FROM tickets
                        WHERE event_id = OLD.id AND status IN ('checked_in', 'void')
                    )
                    OR EXISTS (
                        SELECT 1 FROM check_ins
                        WHERE event_id = OLD.id
                    );
                END
            ");
            return;
        }

        if ($driver === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins');
            DB::unprepared("
                CREATE TRIGGER prevent_event_delete_with_checkins
                BEFORE DELETE ON events
                FOR EACH ROW
                BEGIN
                    IF EXISTS (
                        SELECT 1 FROM tickets
                        WHERE event_id = OLD.id AND status IN ('checked_in', 'void')
                    )
                    OR EXISTS (
                        SELECT 1 FROM check_ins
                        WHERE event_id = OLD.id
                    ) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Cannot delete event with existing check-ins or tickets. Consider cancelling instead.';
                    END IF;
                END
            ");
            return;
        }

        if ($driver === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins ON events');
            DB::unprepared("
                CREATE OR REPLACE FUNCTION prevent_event_delete_with_checkins_func()
                RETURNS trigger AS $$
                BEGIN
                    IF EXISTS (
                        SELECT 1 FROM tickets
                        WHERE event_id = OLD.id AND status IN ('checked_in', 'void')
                    )
                    OR EXISTS (
                        SELECT 1 FROM check_ins
                        WHERE event_id = OLD.id
                    ) THEN
                        RAISE EXCEPTION 'Cannot delete event with existing check-ins or tickets. Consider cancelling instead.';
                    END IF;
                    RETURN OLD;
                END;
                $$ LANGUAGE plpgsql;
            ");
            DB::unprepared("
                CREATE TRIGGER prevent_event_delete_with_checkins
                BEFORE DELETE ON events
                FOR EACH ROW
                EXECUTE FUNCTION prevent_event_delete_with_checkins_func();
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins');
            return;
        }

        if ($driver === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins');
            return;
        }

        if ($driver === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins ON events');
            DB::unprepared('DROP FUNCTION IF EXISTS prevent_event_delete_with_checkins_func()');
        }
    }
};
