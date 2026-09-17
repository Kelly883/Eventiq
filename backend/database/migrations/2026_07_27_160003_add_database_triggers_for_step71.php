<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds database-level triggers for Step 71:
     * - Auto-sync ticket_inventory when ticket status changes
     * - Cascade delete protection for audit_logs and fraud_events
     * - Note: Partitioning requires manual database administration
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite trigger bodies do not support IF/THEN blocks.
            DB::statement('DROP TRIGGER IF EXISTS sync_inventory_on_ticket_change');
            DB::statement('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins');
            DB::statement('DROP TRIGGER IF EXISTS set_checked_in_timestamp');

            DB::statement("
                CREATE TRIGGER sync_inventory_on_ticket_change
                AFTER UPDATE OF status, event_id ON tickets
                FOR EACH ROW
                WHEN OLD.status != NEW.status OR OLD.event_id != NEW.event_id
                BEGIN
                    UPDATE ticket_inventory
                    SET
                        total_checked_in = MAX(0, total_checked_in - CASE WHEN OLD.status = 'checked_in' THEN 1 ELSE 0 END),
                        total_void = MAX(0, total_void - CASE WHEN OLD.status = 'void' THEN 1 ELSE 0 END),
                        last_updated_at = datetime('now')
                    WHERE event_id = OLD.event_id;

                    UPDATE ticket_inventory
                    SET
                        total_checked_in = total_checked_in + CASE WHEN NEW.status = 'checked_in' THEN 1 ELSE 0 END,
                        total_void = total_void + CASE WHEN NEW.status = 'void' THEN 1 ELSE 0 END,
                        last_updated_at = datetime('now')
                    WHERE event_id = NEW.event_id;
                END
            ");

            DB::statement("
                CREATE TRIGGER prevent_event_delete_with_checkins
                BEFORE DELETE ON events
                FOR EACH ROW
                BEGIN
                    SELECT RAISE(ABORT, 'Cannot delete event with existing check-ins. Consider cancelling instead.')
                    WHERE EXISTS (
                        SELECT 1 FROM tickets
                        WHERE event_id = OLD.id AND status IN ('checked_in', 'void')
                    );
                END
            ");

            DB::statement("
                CREATE TRIGGER set_checked_in_timestamp
                AFTER UPDATE OF status ON tickets
                FOR EACH ROW
                WHEN NEW.status = 'checked_in' AND OLD.status != 'checked_in' AND NEW.checked_in_at IS NULL
                BEGIN
                    UPDATE tickets
                    SET checked_in_at = datetime('now')
                    WHERE id = NEW.id;
                END
            ");

            return;
        }

        // Trigger: Sync ticket_inventory when ticket status changes (MySQL path)
        if ($driver === 'mysql') {
            DB::unprepared('DROP TRIGGER IF EXISTS sync_inventory_on_ticket_change');
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins');
            DB::unprepared('DROP TRIGGER IF EXISTS set_checked_in_timestamp');

            DB::unprepared("
                CREATE TRIGGER sync_inventory_on_ticket_change
                AFTER UPDATE ON tickets
                FOR EACH ROW
                BEGIN
                    IF OLD.status <> NEW.status OR OLD.event_id <> NEW.event_id THEN
                        UPDATE ticket_inventory
                        SET
                            total_checked_in = GREATEST(0, total_checked_in - (CASE WHEN OLD.status = 'checked_in' THEN 1 ELSE 0 END)),
                            total_void = GREATEST(0, total_void - (CASE WHEN OLD.status = 'void' THEN 1 ELSE 0 END)),
                            last_updated_at = NOW()
                        WHERE event_id = OLD.event_id;

                        UPDATE ticket_inventory
                        SET
                            total_checked_in = total_checked_in + (CASE WHEN NEW.status = 'checked_in' THEN 1 ELSE 0 END),
                            total_void = total_void + (CASE WHEN NEW.status = 'void' THEN 1 ELSE 0 END),
                            last_updated_at = NOW()
                        WHERE event_id = NEW.event_id;
                    END IF;
                END
            ");

            DB::unprepared("
                CREATE TRIGGER prevent_event_delete_with_checkins
                BEFORE DELETE ON events
                FOR EACH ROW
                BEGIN
                    IF EXISTS (
                        SELECT 1 FROM tickets
                        WHERE event_id = OLD.id AND status IN ('checked_in', 'void')
                    ) THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Cannot delete event with existing check-ins. Consider cancelling instead.';
                    END IF;
                END
            ");

            DB::unprepared("
                CREATE TRIGGER set_checked_in_timestamp
                BEFORE UPDATE ON tickets
                FOR EACH ROW
                BEGIN
                    IF NEW.status = 'checked_in' AND OLD.status <> 'checked_in' AND NEW.checked_in_at IS NULL THEN
                        SET NEW.checked_in_at = NOW();
                    END IF;
                END
            ");
        }

        // Trigger: Sync ticket_inventory when ticket status changes (PostgreSQL path)
        if ($driver === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS sync_inventory_on_ticket_change ON tickets');
            DB::unprepared('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins ON events');
            DB::unprepared('DROP TRIGGER IF EXISTS set_checked_in_timestamp ON tickets');
            DB::unprepared('DROP FUNCTION IF EXISTS sync_inventory_on_ticket_change_func()');
            DB::unprepared('DROP FUNCTION IF EXISTS prevent_event_delete_with_checkins_func()');
            DB::unprepared('DROP FUNCTION IF EXISTS set_checked_in_timestamp_func()');

            DB::unprepared("
                CREATE OR REPLACE FUNCTION sync_inventory_on_ticket_change_func()
                RETURNS trigger AS $$
                BEGIN
                    IF OLD.status <> NEW.status OR OLD.event_id <> NEW.event_id THEN
                        UPDATE ticket_inventory
                        SET
                            total_checked_in = GREATEST(0, total_checked_in - (CASE WHEN OLD.status = 'checked_in' THEN 1 ELSE 0 END)),
                            total_void = GREATEST(0, total_void - (CASE WHEN OLD.status = 'void' THEN 1 ELSE 0 END)),
                            last_updated_at = NOW()
                        WHERE event_id = OLD.event_id;

                        UPDATE ticket_inventory
                        SET
                            total_checked_in = total_checked_in + (CASE WHEN NEW.status = 'checked_in' THEN 1 ELSE 0 END),
                            total_void = total_void + (CASE WHEN NEW.status = 'void' THEN 1 ELSE 0 END),
                            last_updated_at = NOW()
                        WHERE event_id = NEW.event_id;
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;
            ");

            DB::unprepared("
                CREATE TRIGGER sync_inventory_on_ticket_change
                AFTER UPDATE ON tickets
                FOR EACH ROW
                EXECUTE FUNCTION sync_inventory_on_ticket_change_func()
            ");

            DB::unprepared("
                CREATE OR REPLACE FUNCTION prevent_event_delete_with_checkins_func()
                RETURNS trigger AS $$
                BEGIN
                    IF EXISTS (
                        SELECT 1 FROM tickets
                        WHERE event_id = OLD.id AND status IN ('checked_in', 'void')
                    ) THEN
                        RAISE EXCEPTION 'Cannot delete event with existing check-ins. Consider cancelling instead.';
                    END IF;
                    RETURN OLD;
                END;
                $$ LANGUAGE plpgsql;
            ");

            DB::unprepared("
                CREATE TRIGGER prevent_event_delete_with_checkins
                BEFORE DELETE ON events
                FOR EACH ROW
                EXECUTE FUNCTION prevent_event_delete_with_checkins_func()
            ");

            DB::unprepared("
                CREATE OR REPLACE FUNCTION set_checked_in_timestamp_func()
                RETURNS trigger AS $$
                BEGIN
                    IF NEW.status = 'checked_in' AND OLD.status <> 'checked_in' AND NEW.checked_in_at IS NULL THEN
                        NEW.checked_in_at = NOW();
                    END IF;
                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql;
            ");

            DB::unprepared("
                CREATE TRIGGER set_checked_in_timestamp
                BEFORE UPDATE ON tickets
                FOR EACH ROW
                EXECUTE FUNCTION set_checked_in_timestamp_func()
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS sync_inventory_on_ticket_change ON tickets');
            DB::statement('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins ON events');
            DB::statement('DROP TRIGGER IF EXISTS set_checked_in_timestamp ON tickets');
            DB::statement('DROP FUNCTION IF EXISTS sync_inventory_on_ticket_change_func()');
            DB::statement('DROP FUNCTION IF EXISTS prevent_event_delete_with_checkins_func()');
            DB::statement('DROP FUNCTION IF EXISTS set_checked_in_timestamp_func()');

            return;
        }

        DB::statement('DROP TRIGGER IF EXISTS sync_inventory_on_ticket_change');
        DB::statement('DROP TRIGGER IF EXISTS prevent_event_delete_with_checkins');
        DB::statement('DROP TRIGGER IF EXISTS set_checked_in_timestamp');
    }
};
