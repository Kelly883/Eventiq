<?php
require __DIR__ . '/vendor/autoload.php';
use Illuminate\Foundation\Application;
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$pdo = DB::connection()->getPdo();
function hasCol($pdo,$table,$col){foreach($pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC) as $c){if($c["name"]===$col)return true;}return false;}
function getIndexes($pdo,$table){return array_map(fn($r)=>$r["name"],$pdo->query("PRAGMA index_list($table)")->fetchAll(PDO::FETCH_ASSOC));}
function getFks($pdo,$table){return $pdo->query("PRAGMA foreign_key_list($table)")->fetchAll(PDO::FETCH_ASSOC);}
echo "=== CHECKOUT SCHEMA VERIFICATION ===\n\n";
$tables=["orders"=>["id","user_id","event_id","total_amount","currency","status","payment_gateway","payment_intent_id","created_at","updated_at"],"order_items"=>["id","order_id","ticket_tier_id","quantity","unit_price","created_at","updated_at"],"tickets"=>["id","order_id","user_id","event_id","ticket_tier_id","qr_code_data","status","checked_in_at","created_at","updated_at"],"payments"=>["id","order_id","payment_intent_id","amount","currency","status","gateway","gateway_response","created_at","updated_at"]];
foreach($tables as $t=>$cols){echo "--- $t ---\n";if(!$pdo->query("SELECT name FROM sqlite_master WHERE type=\"table\" AND name=\"$t\"")->fetch()){echo "  [MISSING TABLE]\n";continue;}foreach($cols as $c){echo "  ".(hasCol($pdo,$t,$c)?"[OK]":"[MISSING]")." $t.$c\n";}echo "  Indexes: ".implode(", ",getIndexes($pdo,$t))."\n";echo "  FKs:\n";foreach(getFks($pdo,$t) as $fk){echo "    ".$fk["from"]." -> ".$fk["table"].".".$fk["to"]." (".$fk["on_delete"].")\n";}echo "\n";}
echo "--- Extra payment-real fields ---\n";
foreach(["gateway_transaction_id","subtotal","tax_amount","discount_amount","coupon_code","billing_name","billing_email","billing_phone","failure_reason"] as $c){echo "  ".(hasCol($pdo,"orders",$c)?"[OK]":"[absent]")." orders.$c\n";}
foreach(["gateway_transaction_id","settlement_id","settled_at","idempotency_key","refunded_by","refunded_at","refund_reason","fees","net_amount","card_last_four","card_brand"] as $c){echo "  ".(hasCol($pdo,"payments",$c)?"[OK]":"[absent]")." payments.$c\n";}
echo "\nDONE\n";
