<?php

$dir = __DIR__ . '/database/migrations';
$files = glob($dir . '/*.php');

echo "=== Linting all migration files ===\n";
$errors = [];
foreach ($files as $file) {
    $output = [];
    $exitCode = 0;
    $cmd = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file);
    exec($cmd . ' 2>&1', $output, $exitCode);
    if ($exitCode !== 0) {
        $errors[] = basename($file) . ":\n" . implode("\n", $output);
    }
}

if (empty($errors)) {
    echo "ALL " . count($files) . " migration files pass php -l\n";
} else {
    echo "SYNTAX ERRORS FOUND in " . count($errors) . " file(s):\n";
    foreach ($errors as $e) {
        echo $e . "\n---\n";
    }
}
echo "\nDONE\n";
