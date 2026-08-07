<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rebuilds orders table on SQLite to make total_amount decimal-compatible.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite' || !DB::getSchemaBuilder()->hasTable('orders')) {
            return;
        }

        // CRITICAL GUARD: If orders was created as a UUID table (Step 66
        // PRD requirement), do NOT rebuild it - this legacy migration
        // would otherwise replace the UUID primary key with an integer
        // auto-increment key and corrupt the checkout schema.
        $pkType = (string) DB::table('sqlite_master')
            ->where('type', 'table')
            ->where('name', 'orders')
            ->value('sql');

        if (preg_match('/"id"\s+varchar\b/i', $pkType) === 1) {
            return;
        }

        // Skip if already decimal-compatible.
        $createSql = (string) DB::table('sqlite_master')
            ->where('type', 'table')
            ->where('name', 'orders')
            ->value('sql');

        if ($createSql !== '' && preg_match('/"total_amount"\s+numeric/i', $createSql) === 1) {
            return;
        }

        DB::transaction(function () {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement("CREATE TABLE orders_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                user_id INTEGER,
                event_id INTEGER,
                status VARCHAR NOT NULL DEFAULT 'pending',
                total_amount NUMERIC NOT NULL DEFAULT '0',
                currency VARCHAR NOT NULL DEFAULT 'NGN',
                payment_gateway VARCHAR,
                payment_intent_id VARCHAR,
                device_id VARCHAR,
                ip_address VARCHAR,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME,
                gateway_transaction_id VARCHAR,
                subtotal NUMERIC,
                tax_amount NUMERIC,
                discount_amount NUMERIC NOT NULL DEFAULT '0',
                coupon_code VARCHAR,
                billing_name VARCHAR,
                billing_email VARCHAR,
                billing_phone VARCHAR,
                failure_reason TEXT,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE SET NULL
            )");

            DB::statement('INSERT INTO orders_new (
                id, user_id, event_id, status, total_amount, currency, payment_gateway,
                payment_intent_id, device_id, ip_address, created_at, updated_at, deleted_at,
                gateway_transaction_id, subtotal, tax_amount, discount_amount, coupon_code,
                billing_name, billing_email, billing_phone, failure_reason
            )
            SELECT
                id, user_id, event_id, status, total_amount, currency, payment_gateway,
                payment_intent_id, device_id, ip_address, created_at, updated_at, deleted_at,
                gateway_transaction_id, subtotal, tax_amount, discount_amount, coupon_code,
                billing_name, billing_email, billing_phone, failure_reason
            FROM orders');

            DB::statement('DROP TABLE orders');
            DB::statement('ALTER TABLE orders_new RENAME TO orders');

            // Recreate key indexes used by checkout and analytics flows.
            DB::statement('CREATE INDEX IF NOT EXISTS orders_user_id_index ON orders(user_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS orders_event_id_index ON orders(event_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS orders_status_index ON orders(status)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_orders_event_id ON orders(event_id)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_orders_user_status ON orders(user_id, status)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_orders_created_at ON orders(created_at)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_orders_event_status ON orders(event_id, status)');
            DB::statement('CREATE INDEX IF NOT EXISTS idx_orders_user_created ON orders(user_id, created_at DESC)');

            // SQLite partial index used for active intent dedup.
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uq_orders_payment_intent_active ON orders (payment_intent_id, status) WHERE payment_intent_id IS NOT NULL AND status IN ('pending', 'processing')");

            DB::statement('PRAGMA foreign_keys = ON');
        });
    }

    public function down(): void
    {
        // Intentionally irreversible: this is a type normalization rebuild.
    }
};
