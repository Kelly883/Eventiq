<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$pdo = DB::connection()->getPdo();
function hcol2($pdo,$t,$c){foreach($pdo->query("PRAGMA table_info($t)") as $r){if($r["name"]===$c)return true;}return false;}
$adds = [
  "gateway_transaction_id VARCHAR", "settlement_id VARCHAR", "settled_at DATETIME",
  "idempotency_key VARCHAR", "refunded_by VARCHAR", "refunded_at DATETIME",
  "refund_reason TEXT", "fees NUMERIC", "net_amount NUMERIC",
  "card_last_four VARCHAR", "card_brand VARCHAR"
];
foreach($adds as $def){
  $col = explode(" ", $def)[0];
  if(hcol2($pdo,"payments",$col)){ echo "  = payments.$col exists\n"; continue; }
  try {
    DB::statement("ALTER TABLE payments ADD COLUMN $def");
    echo "  + added payments.$col\n";
  } catch(\Throwable $e){ echo "  [ERR] $col: ".$e->getMessage()."\n"; }
}
echo "\n=== payments columns ===\n";
foreach($pdo->query("PRAGMA table_info(payments)") as $r){echo "  ".$r["name"]." : ".$r["type"]."\n";}
echo "\nDONE\n";
