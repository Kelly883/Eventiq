<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fraud_events')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS prevent_fraud_events_update');
            DB::statement("
                CREATE TRIGGER prevent_fraud_events_update
                BEFORE UPDATE ON fraud_events
                FOR EACH ROW
                BEGIN
                    SELECT RAISE(ABORT, 'UPDATE operations are not allowed on fraud_events. This table is immutable.');
                END
            ");
        } elseif ($driver === 'mysql') {
            DB::statement('DROP TRIGGER IF EXISTS prevent_fraud_events_update');
            DB::statement("
                CREATE TRIGGER prevent_fraud_events_update
                BEFORE UPDATE ON fraud_events
                FOR EACH ROW
                BEGIN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'UPDATE operations are not allowed on fraud_events. This table is immutable.';
                END
            ");
        } elseif ($driver === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS prevent_fraud_events_update ON fraud_events');
            DB::statement("
                CREATE OR REPLACE FUNCTION prevent_fraud_events_update_func()
                RETURNS trigger AS $$
                BEGIN
                    RAISE EXCEPTION 'UPDATE operations are not allowed on fraud_events. This table is immutable.';
                    RETURN OLD;
                END;
                $$ LANGUAGE plpgsql;
            ");
            DB::statement("
                CREATE TRIGGER prevent_fraud_events_update
                BEFORE UPDATE ON fraud_events
                FOR EACH ROW
                EXECUTE FUNCTION prevent_fraud_events_update_func();
            ");
        }

        try {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_fraud_ticket_event ON fraud_events (ticket_id, event_id)');
        } catch (\Exception $e) {
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS prevent_fraud_events_update');
        } elseif ($driver === 'mysql') {
            DB::statement('DROP TRIGGER IF EXISTS prevent_fraud_events_update');
        } elseif ($driver === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS prevent_fraud_events_update ON fraud_events');
            DB::statement('DROP FUNCTION IF EXISTS prevent_fraud_events_update_func()');
        }

        try {
            DB::statement('DROP INDEX IF EXISTS idx_fraud_ticket_event');
        } catch (\Exception $e) {
        }
    }
};
