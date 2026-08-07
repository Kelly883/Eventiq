<?php
require __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$pdo = DB::connection()->getPdo();
foreach(["orders","order_items","tickets","payments"] as $t){
  echo "=== $t CREATE SQL ===\n";
  $sql = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$t'")->fetch();
  echo ($sql ? $sql["sql"] : "(none)")."\n\n";
}
