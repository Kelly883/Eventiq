<?php

$dir = __DIR__ . '/database/migrations';
$files = glob($dir . '/*.php');

echo "=== Scanning for Git conflict markers across all migrations ===\n";
$found = false;
foreach ($files as $file) {
    $lines = file($file);
    foreach ($lines as $i => $line) {
        $trim = rtrim($line);
        if (str_starts_with($trim, '<<<<<<<') || $trim === '=======' || str_starts_with($trim, '>>>>>>>')) {
            echo basename($file) . " L" . ($i + 1) . ": " . $trim . "\n";
            $found = true;
        }
    }
}
if (!$found) {
    echo "No conflict markers found. All migrations are clean.\n";
}
echo "\nDONE\n";
