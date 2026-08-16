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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP TRIGGER IF EXISTS trg_payout_calculations_prevent_update');
            DB::statement('DROP TRIGGER IF EXISTS trg_payout_calculations_prevent_delete');
        } else {
            DB::statement('DROP TRIGGER IF EXISTS trg_payout_calculations_prevent_update');
            DB::statement('DROP TRIGGER IF EXISTS trg_payout_calculations_prevent_delete');
        }
    }
};
