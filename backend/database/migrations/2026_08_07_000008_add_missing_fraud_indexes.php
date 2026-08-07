<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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