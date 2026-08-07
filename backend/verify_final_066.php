<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$pdo = DB::connection()->getPdo();
echo "=== orders CREATE SQL ===\n";
$sql=$pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='orders'")->fetch();
echo ($sql["sql"]??"MISSING")."\n\n";
echo "=== orders columns/types ===\n";
foreach($pdo->query("PRAGMA table_info(orders)") as $r){echo "  ".$r["name"]." : ".$r["type"]." null=".($r["notnull"]?"N":"Y")."\n";}
echo "\n=== orders indexes ===\n";
foreach($pdo->query("PRAGMA index_list(orders)") as $r){echo "  ".$r["name"]."\n";}
echo "\n=== payments columns ===\n";
foreach($pdo->query("PRAGMA table_info(payments)") as $r){echo "  ".$r["name"]." : ".$r["type"]."\n";}
echo "\n=== sample data check ===\n";
echo "orders count: ".DB::table("orders")->count()."\n";
echo "payments count: ".DB::table("payments")->count()."\n";
echo "tickets count: ".DB::table("tickets")->count()."\n";
echo "order_items count: ".DB::table("order_items")->count()."\n";
