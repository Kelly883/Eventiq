<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Disable the implicit transaction wrapper for this migration.
    // On managed PostgreSQL (Render, RDS, etc.), a prior statement failure
    // in the same transaction aborts the entire transaction, causing
    // subsequent CREATE TABLE/ALTER TABLE to fail with "current transaction is aborted".
    // By opting out, each statement runs in its own autocommit transaction.
    public $withinTransaction = false;
    /**
     * Add the missing indexes that failed to create in previous migration.
     *
     * @return void
     */
    public function up(): void
    {
        try {
            DB::statement('CREATE INDEX idx_fraud_user_email ON fraud_events(user_email)');
        } catch (\Exception $e) {
            // Index may already exist
        }

        try {
            DB::statement('CREATE INDEX idx_fraud_proxy_vpn ON fraud_events(proxy_vpn_detected)');
        } catch (\Exception $e) {
            // Index may already exist
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        try {
            DB::statement('DROP INDEX IF EXISTS idx_fraud_user_email');
        } catch (\Exception $e) {}

        try {
            DB::statement('DROP INDEX IF EXISTS idx_fraud_proxy_vpn');
        } catch (\Exception $e) {}
    }
};