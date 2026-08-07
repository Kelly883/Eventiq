<?php

$dir = __DIR__ . '/database/migrations';
$files = glob($dir . '/*.php');

echo "=== Scanning migrations for issues ===\n\n";

foreach ($files as $file) {
    $content = file_get_contents($file);
    $base = basename($file);
    $lines = explode("\n", $content);
    $issues = [];

    foreach ($lines as $i => $line) {
        $ln = $i + 1;
        $trim = trim($line);
        // Conflict markers
        if (str_starts_with($trim, '<<<<<<<') || str_starts_with($trim, '>>>>>>>') || $trim === '=======') {
            $issues[] = "  L{$ln}: CONFLICT MARKER: " . substr($trim, 0, 60);
        }
        // group_name reference (permissions uses 'group')
        if (strpos($content, 'group_name') !== false && strpos($content, 'permissions') !== false) {
            if (preg_match('/(group_name)/', $line)) {
                $issues[] = "  L{$ln}: group_name reference";
            }
        }
    }

    if (!empty($issues)) {
        echo "\n### {$base}\n";
        foreach ($issues as $issue) {
            echo $issue . "\n";
        }
    }
}

echo "\n\n=== Done scan ===\n";
