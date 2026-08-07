<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
// Rebuild orders table to make total_amount NUMERIC (decimal-compatible)
DB::transaction(function () {
    DB::statement("PRAGMA foreign_keys = OFF");
    DB::statement("CREATE TABLE orders_new (
        id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        user_id VARCHAR,
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
    DB::statement("INSERT INTO orders_new (id, user_id, event_id, status, total_amount, currency, payment_gateway, payment_intent_id, device_id, ip_address, created_at, updated_at)
        SELECT id, user_id, event_id, status, total_amount, currency, payment_gateway, payment_intent_id, device_id, ip_address, created_at, updated_at FROM orders");
    DB::statement("DROP TABLE orders");
    DB::statement("ALTER TABLE orders_new RENAME TO orders");
    DB::statement("CREATE INDEX IF NOT EXISTS orders_user_id_index ON orders(user_id)");
    DB::statement("CREATE INDEX IF NOT EXISTS orders_event_id_index ON orders(event_id)");
    DB::statement("CREATE INDEX IF NOT EXISTS orders_status_index ON orders(status)");
    DB::statement("CREATE INDEX IF NOT EXISTS idx_orders_event_id ON orders(event_id)");
    DB::statement("CREATE INDEX IF NOT EXISTS idx_orders_user_status ON orders(user_id, status)");
    DB::statement("PRAGMA foreign_keys = ON");
});
$pdo = DB::connection()->getPdo();
$sql=$pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='orders'")->fetch();
echo "=== orders after rebuild ===\n";
echo $sql["sql"]."\n";
echo "\n=== total_amount type ===\n";
foreach($pdo->query("PRAGMA table_info(orders)") as $r){if($r["name"]==="total_amount")echo "  total_amount : ".$r["type"]."\n";}
echo "\nDONE\n";
