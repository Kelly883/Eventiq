<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Prevent hard deletes on fraud_events table.
     *
     * Fraud audit trails must be IMMUTABLE. This migration creates
     * database-level protection against DELETE statements.
     *
     * For SQLite: Creates a BEFORE DELETE trigger that raises an error.
     * For MySQL: Creates a BEFORE DELETE trigger that signals an error.
     * For PostgreSQL: Creates a RULE that prevents deletes.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->createSqliteTrigger();
        } elseif ($driver === 'mysql') {
            $this->createMySqlTrigger();
        } elseif ($driver === 'pgsql') {
            $this->createPostgreSqlRule();
        }
    }

    /**
     * Create SQLite trigger to prevent deletes.
     */
    private function createSqliteTrigger(): void
    {
        DB::statement('
            CREATE TRIGGER IF NOT EXISTS prevent_fraud_events_delete
            BEFORE DELETE ON fraud_events
            FOR EACH ROW
            BEGIN
                SELECT RAISE(ABORT, "DELETE operations are not allowed on fraud_events. Use is_archived flag instead.");
            END
        ');
    }

    /**
     * Create MySQL trigger to prevent deletes.
     */
    private function createMySqlTrigger(): void
    {
        DB::statement('
            CREATE TRIGGER IF NOT EXISTS prevent_fraud_events_delete
            BEFORE DELETE ON fraud_events
            FOR EACH ROW
            BEGIN
                SIGNAL SQLSTATE "45000"
                SET MESSAGE_TEXT = "DELETE operations are not allowed on fraud_events. Use is_archived flag instead.";
            END
        ');
    }

    /**
     * Create PostgreSQL RULE to prevent deletes.
     */
    private function createPostgreSqlRule(): void
    {
        DB::statement('
            CREATE RULE prevent_fraud_events_delete AS
            ON DELETE TO fraud_events
            DO INSTEAD NOTHING
        ');
    }

    /**
     * Remove the delete prevention mechanism.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS prevent_fraud_events_delete');
        } elseif ($driver === 'mysql') {
            DB::statement('DROP TRIGGER IF EXISTS prevent_fraud_events_delete');
        } elseif ($driver === 'pgsql') {
            DB::statement('DROP RULE IF EXISTS prevent_fraud_events_delete ON fraud_events');
        }
    }
};