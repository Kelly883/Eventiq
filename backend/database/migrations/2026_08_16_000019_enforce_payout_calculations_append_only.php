<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->createSqliteTriggers();
        } elseif ($driver === 'pgsql') {
            $this->createPostgreSqlTriggers();
        } else {
            $this->createMySqlTriggers();
        }
    }

    private function createSqliteTriggers(): void
    {
        DB::statement('CREATE TRIGGER IF NOT EXISTS trg_payout_calculations_prevent_update BEFORE UPDATE ON payout_calculations FOR EACH ROW BEGIN SELECT RAISE(ABORT, \'payout_calculations is append-only\'); END');
        DB::statement('CREATE TRIGGER IF NOT EXISTS trg_payout_calculations_prevent_delete BEFORE DELETE ON payout_calculations FOR EACH ROW BEGIN SELECT RAISE(ABORT, \'payout_calculations is append-only\'); END');
    }

    private function createMySqlTriggers(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_payout_calculations_prevent_update');
        DB::statement('DROP TRIGGER IF EXISTS trg_payout_calculations_prevent_delete');

        DB::statement('CREATE TRIGGER trg_payout_calculations_prevent_update BEFORE UPDATE ON payout_calculations FOR EACH ROW BEGIN SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'payout_calculations is append-only\'; END');
        DB::statement('CREATE TRIGGER trg_payout_calculations_prevent_delete BEFORE DELETE ON payout_calculations FOR EACH ROW BEGIN SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'payout_calculations is append-only\'; END');
    }

    private function createPostgreSqlTriggers(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_payout_calculations_prevent_update ON payout_calculations');
        DB::statement('DROP TRIGGER IF EXISTS trg_payout_calculations_prevent_delete ON payout_calculations');
        DB::statement('DROP FUNCTION IF EXISTS trg_payout_calculations_prevent_update()');
        DB::statement('DROP FUNCTION IF EXISTS trg_payout_calculations_prevent_delete()');

        DB::statement('CREATE OR REPLACE FUNCTION trg_payout_calculations_prevent_update() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION \'payout_calculations is append-only\'; RETURN NEW; END; $$');
        DB::statement('CREATE OR REPLACE FUNCTION trg_payout_calculations_prevent_delete() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION \'payout_calculations is append-only\'; RETURN NEW; END; $$');

        DB::statement('CREATE TRIGGER trg_payout_calculations_prevent_update BEFORE UPDATE ON payout_calculations FOR EACH ROW EXECUTE FUNCTION trg_payout_calculations_prevent_update()');
        DB::statement('CREATE TRIGGER trg_payout_calculations_prevent_delete BEFORE DELETE ON payout_calculations FOR EACH ROW EXECUTE FUNCTION trg_payout_calculations_prevent_delete()');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS trg_payout_calculations_prevent_update');
            DB::statement('DROP TRIGGER IF EXISTS trg_payout_calculations_prevent_delete');
        } elseif ($driver === 'pgsql') {
            $this->dropPostgreSqlTriggers();
        } else {
            DB::statement('DROP TRIGGER IF EXISTS trg_payout_calculations_prevent_update');
            DB::statement('DROP TRIGGER IF EXISTS trg_payout_calculations_prevent_delete');
        }
    }

    private function dropPostgreSqlTriggers(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS trg_payout_calculations_prevent_update ON payout_calculations');
        DB::statement('DROP TRIGGER IF EXISTS trg_payout_calculations_prevent_delete ON payout_calculations');
        DB::statement('DROP FUNCTION IF EXISTS trg_payout_calculations_prevent_update()');
        DB::statement('DROP FUNCTION IF EXISTS trg_payout_calculations_prevent_delete()');
    }
};
