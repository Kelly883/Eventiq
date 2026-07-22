<?php
$db = new PDO('sqlite:database/database.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== users_columns ===\n";
foreach ($db->query("PRAGMA table_info('users')") as $c) {
  echo $c['name']."\n";
}

echo "=== permission_requests_indexes ===\n";
foreach ($db->query("PRAGMA index_list('permission_requests')") as $idx) {
  $name = $idx['name'];
  echo $name.'|unique='.$idx['unique']."\n";
  foreach ($db->query("PRAGMA index_info('{$name}')") as $ic) {
    echo '  - '.($ic['name'] ?? '(expr)')."\n";
  }
}

echo "=== role_permission_sync_sample ===\n";
$rows = $db->query("SELECT role_id, permission_id FROM permission_role ORDER BY role_id, permission_id LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows)) {
  echo "no_rows\n";
} else {
  foreach ($rows as $row) {
    echo json_encode($row)."\n";
  }
}
