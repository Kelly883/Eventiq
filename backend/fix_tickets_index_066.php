<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$pdo = DB::connection()->getPdo();
echo "=== tickets indexes BEFORE ===\n";
foreach($pdo->query("PRAGMA index_list(tickets)") as $r){echo "  ".$r["name"]."\n";}
// Drop the corrupt qr_code_expires_at index
try{DB::statement("DROP INDEX tickets_qr_code_expires_at_index");echo "  dropped tickets_qr_code_expires_at_index\n";}catch(\Throwable $e){echo "  err: ".$e->getMessage()."\n";}
// Also check tickets schema for the qr_code_expires column situation
echo "=== tickets columns ===\n";
foreach($pdo->query("PRAGMA table_info(tickets)") as $r){echo "  ".$r["name"]."\n";}
echo "=== tickets indexes AFTER ===\n";
foreach($pdo->query("PRAGMA index_list(tickets)") as $r){echo "  ".$r["name"]."\n";}
