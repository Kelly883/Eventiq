<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$pdo = DB::connection()->getPdo();
echo "=== payments final columns ===\n";
foreach($pdo->query("PRAGMA table_info(payments)") as $r){echo "  ".$r["name"]." : ".$r["type"]."\n";}
echo "\n=== payments indexes ===\n";
foreach($pdo->query("PRAGMA index_list(payments)") as $r){echo "  ".$r["name"]."\n";}
echo "\n=== orders final columns ===\n";
foreach($pdo->query("PRAGMA table_info(orders)") as $r){echo "  ".$r["name"]." : ".$r["type"]."\n";}
echo "\n=== orders indexes ===\n";
foreach($pdo->query("PRAGMA index_list(orders)") as $r){echo "  ".$r["name"]."\n";}
echo "\n=== tickets indexes ===\n";
foreach($pdo->query("PRAGMA index_list(tickets)") as $r){echo "  ".$r["name"]."\n";}
echo "\n=== order_items indexes ===\n";
foreach($pdo->query("PRAGMA index_list(order_items)") as $r){echo "  ".$r["name"]."\n";}
echo "\nDONE\n";
