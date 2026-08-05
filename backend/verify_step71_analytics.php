<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cols = Illuminate\Support\Facades\Schema::getColumnListing('analytics_events_metrics');
echo "analytics_events_metrics columns:\n";
echo implode("\n", $cols);
echo "\n\n";

$hasTotalCheckedIn = in_array('total_checked_in', $cols);
$hasCheckInRate = in_array('check_in_rate', $cols);
$hasLastUpdated = in_array('last_updated_at', $cols);

echo "Has total_checked_in: " . ($hasTotalCheckedIn ? "YES" : "NO") . "\n";
echo "Has check_in_rate: " . ($hasCheckInRate ? "YES" : "NO") . "\n";
echo "Has last_updated_at: " . ($hasLastUpdated ? "YES" : "NO") . "\n";

if ($hasTotalCheckedIn && $hasCheckInRate && $hasLastUpdated) {
    echo "\nSUCCESS: All Step 71 analytics columns exist!\n";
} else {
    echo "\nFAIL: Missing columns!\n";
}