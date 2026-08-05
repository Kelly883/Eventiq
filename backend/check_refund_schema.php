<?php
$pdo = new PDO('sqlite:database/database.sqlite');

echo "=== REFUND_REQUESTS PRAGMA ===\n";
$cols = $pdo->query('PRAGMA table_info(refund_requests)')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['name'] . ' | type=' . $c['type'] . ' | notnull=' . $c['notnull'] . ' | pk=' . $c['pk'] . ' | dflt=' . ($c['dflt_value'] ?? 'NULL') . "\n";
}

echo "\n=== REFUND_POLICIES PRAGMA ===\n";
$cols = $pdo->query('PRAGMA table_info(refund_policies)')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['name'] . ' | type=' . $c['type'] . ' | notnull=' . $c['notnull'] . ' | pk=' . $c['pk'] . "\n";
}

echo "\n=== REFUND_APPEALS PRAGMA ===\n";
$cols = $pdo->query('PRAGMA table_info(refund_appeals)')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['name'] . ' | type=' . $c['type'] . ' | notnull=' . $c['notnull'] . ' | pk=' . $c['pk'] . "\n";
}

echo "\n=== REFUND_REQUESTS INDEXES ===\n";
$idxs = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='refund_requests' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($idxs as $i) echo $i['name'] . "\n";

echo "\n=== REFUND_POLICIES INDEXES ===\n";
$idxs = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='refund_policies' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($idxs as $i) echo $i['name'] . "\n";

echo "\n=== REFUND_APPEALS INDEXES ===\n";
$idxs = $pdo->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='refund_appeals' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($idxs as $i) echo $i['name'] . "\n";

echo "\n=== FOREIGN KEYS ===\n";
echo "\nrefund_policies:\n";
$fks = $pdo->query('PRAGMA foreign_key_list(refund_policies)')->fetchAll(PDO::FETCH_ASSOC);
foreach ($fks as $fk) echo "  {$fk['from']} -> {$fk['table']}.{$fk['to']} (on delete: {$fk['on_delete']})\n";

echo "\nrefund_requests:\n";
$fks = $pdo->query('PRAGMA foreign_key_list(refund_requests)')->fetchAll(PDO::FETCH_ASSOC);
foreach ($fks as $fk) echo "  {$fk['from']} -> {$fk['table']}.{$fk['to']} (on delete: {$fk['on_delete']})\n";

echo "\nrefund_appeals:\n";
$fks = $pdo->query('PRAGMA foreign_key_list(refund_appeals)')->fetchAll(PDO::FETCH_ASSOC);
foreach ($fks as $fk) echo "  {$fk['from']} -> {$fk['table']}.{$fk['to']} (on delete: {$fk['on_delete']})\n";