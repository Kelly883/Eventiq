<?php
$pdo = new PDO('sqlite:database/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "TABLES\n";
foreach (['events','organizers','ticket_tiers','pricing_windows','ticket_inventory','analytics_events_metrics'] as $t) {
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='" . $t . "'");
    echo $t . ': ' . ($stmt->fetch() ? 'YES' : 'NO') . "\n";
}

echo "\nEVENTS_COLUMNS\n";
$cols = $pdo->query("PRAGMA table_info(events)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    $dflt = is_null($c['dflt_value']) ? 'NULL' : $c['dflt_value'];
    echo $c['name'] . '|' . $c['type'] . '|notnull=' . $c['notnull'] . '|dflt=' . $dflt . "\n";
}

echo "\nEVENTS_INDEXES\n";
$idx = $pdo->query("PRAGMA index_list(events)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($idx as $i) {
    echo $i['name'] . '|unique=' . $i['unique'] . "\n";
    $ii = $pdo->query("PRAGMA index_info('" . $i['name'] . "')")->fetchAll(PDO::FETCH_ASSOC);
    $cols = array_map(fn($r) => $r['name'], $ii);
    echo '  cols=' . implode(',', $cols) . "\n";
}

echo "\nEVENTS_FKS\n";
$fks = $pdo->query("PRAGMA foreign_key_list(events)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($fks as $f) {
    echo $f['from'] . '->' . $f['table'] . '.' . $f['to'] . '|on_delete=' . $f['on_delete'] . "\n";
}

foreach (['ticket_tiers','pricing_windows','ticket_inventory','analytics_events_metrics'] as $t) {
    echo "\nFKS_{$t}\n";
    $fks = $pdo->query("PRAGMA foreign_key_list(" . $t . ")")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fks as $f) {
        echo $f['from'] . '->' . $f['table'] . '.' . $f['to'] . '|on_delete=' . $f['on_delete'] . "\n";
    }
}

echo "\nQUERY_PLAN\n";
$plan = $pdo->query("EXPLAIN QUERY PLAN SELECT * FROM events WHERE status='published' ORDER BY start_datetime DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($plan as $p) {
    echo $p['detail'] . "\n";
}
