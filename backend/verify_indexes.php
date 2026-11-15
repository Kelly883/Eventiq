<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$db = Illuminate\Support\Facades\DB::connection();

echo "=== fraud_events indexes ===\n";
$indexes = $db->select('PRAGMA index_list(fraud_events)');
foreach ($indexes as $idx) {
    $unique = $idx->unique ? 'yes' : 'no';
    echo "  {$idx->name} (unique: $unique)\n";
}

echo "\n=== payments indexes ===\n";
$indexes = $db->select('PRAGMA index_list(payments)');
foreach ($indexes as $idx) {
    $unique = $idx->unique ? 'yes' : 'no';
    echo "  {$idx->name} (unique: $unique)\n";
}

echo "\n=== check_ins indexes ===\n";
$indexes = $db->select('PRAGMA index_list(check_ins)');
foreach ($indexes as $idx) {
    $unique = $idx->unique ? 'yes' : 'no';
    echo "  {$idx->name} (unique: $unique)\n";
}

echo "\n=== check_ins has deleted_at? ===\n";
$columns = $db->select("PRAGMA table_info('check_ins')");
$hasDeletedAt = false;
foreach ($columns as $col) {
    if ($col->name === 'deleted_at') {
        $hasDeletedAt = true;
        break;
    }
}
echo $hasDeletedAt ? 'YES' : 'NO';
