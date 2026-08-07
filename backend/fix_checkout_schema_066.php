<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
$pdo = DB::connection()->getPdo();
function hasCol($pdo,$t,$c){foreach($pdo->query("PRAGMA table_info($t)")->fetchAll(PDO::FETCH_ASSOC) as $r){if($r["name"]===$c)return true;}return false;}
function idxExists($pdo,$t,$n){foreach($pdo->query("PRAGMA index_list($t)")->fetchAll(PDO::FETCH_ASSOC) as $r){if($r["name"]===$n)return true;}return false;}
DB::statement("PRAGMA foreign_keys = OFF");

echo "=== 1. ORDERS FIXES ===\n";
if(hasCol($pdo,"orders","payment_reference") && !hasCol($pdo,"orders","payment_intent_id")){
  DB::statement("ALTER TABLE orders RENAME COLUMN payment_reference TO payment_intent_id");
  echo "  renamed payment_reference -> payment_intent_id\n";
}
$oidxs=[["orders_user_id_index","user_id"],["orders_status_index","status"],["idx_orders_event_id","event_id"],["idx_orders_user_status","user_id, status"]];
foreach($oidxs as [$n,$cols]){if(!idxExists($pdo,"orders",$n)){DB::statement("CREATE INDEX $n ON orders ($cols)");echo "  + index $n\n";}else{echo "  = index $n exists\n";}}

echo "=== 2. ORDER_ITEMS FIXES ===\n";
if(!idxExists($pdo,"order_items","order_items_order_id_index")){DB::statement("CREATE INDEX order_items_order_id_index ON order_items (order_id)");echo "  + order_id index\n";}else{echo "  = order_id index exists\n";}

echo "=== 3. TICKETS FIXES ===\n";
if(!hasCol($pdo,"tickets","qr_code_data")){DB::statement("ALTER TABLE tickets ADD COLUMN qr_code_data TEXT");echo "  + qr_code_data column\n";}else{echo "  = qr_code_data exists\n";}
$tixs=[["tickets_user_id_index","user_id"],["tickets_event_id_index","event_id"],["idx_tickets_order_id","order_id"]];
foreach($tixs as [$n,$cols]){if(!idxExists($pdo,"tickets",$n)){DB::statement("CREATE INDEX $n ON tickets ($cols)");echo "  + index $n\n";}else{echo "  = index $n exists\n";}}

echo "=== 4. PAYMENTS FIXES ===\n";
if(hasCol($pdo,"payments","gateway_reference") && !hasCol($pdo,"payments","payment_intent_id")){
  DB::statement("ALTER TABLE payments RENAME COLUMN gateway_reference TO payment_intent_id");
  echo "  renamed gateway_reference -> payment_intent_id\n";
}
if(!idxExists($pdo,"payments","payments_order_id_index")){DB::statement("CREATE INDEX payments_order_id_index ON payments (order_id)");echo "  + order_id index\n";}else{echo "  = order_id index exists\n";}

DB::statement("PRAGMA foreign_keys = ON");
echo "\nDONE SCHEMA FIXES\n";
