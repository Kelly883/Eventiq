<?php

/**
 * Step 74 Tinker Verification: Eloquent Model Counts
 *
 * Bootstraps the Laravel application and tests the actual Eloquent models:
 *   - RefundRequest::count()
 *   - RefundPolicy::count()
 *   - RefundAppeal::count()
 *   - RefundRequest::all()
 *
 * This is the equivalent of running these in `php artisan tinker`.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Features\Refunds\Models\RefundRequest;
use App\Features\Refunds\Models\RefundPolicy;
use App\Features\Refunds\Models\RefundAppeal;

echo "============================================================\n";
echo " STEP 74 TINKER VERIFICATION: Eloquent Model Counts\n";
echo "============================================================\n\n";

$results = [];
$allPass = true;

// RefundRequest::count()
try {
    $count = RefundRequest::count();
    $status = ($count === 0) ? '✓' : '⚠ (expected 0)';
    if ($count !== 0) $allPass = false;
    $results[] = ['RefundRequest::count()', $count, $status];
    echo sprintf("  RefundRequest::count()  => %d %s\n", $count, $status);
} catch (Exception $e) {
    $results[] = ['RefundRequest::count()', 'ERROR', '✗'];
    $allPass = false;
    echo sprintf("  RefundRequest::count()  => ERROR: %s ✗\n", $e->getMessage());
}

// RefundPolicy::count()
try {
    $count = RefundPolicy::count();
    $status = ($count === 0) ? '✓' : '⚠ (expected 0)';
    if ($count !== 0) $allPass = false;
    $results[] = ['RefundPolicy::count()', $count, $status];
    echo sprintf("  RefundPolicy::count()   => %d %s\n", $count, $status);
} catch (Exception $e) {
    $results[] = ['RefundPolicy::count()', 'ERROR', '✗'];
    $allPass = false;
    echo sprintf("  RefundPolicy::count()   => ERROR: %s ✗\n", $e->getMessage());
}

// RefundAppeal::count()
try {
    $count = RefundAppeal::count();
    $status = ($count === 0) ? '✓' : '⚠ (expected 0)';
    if ($count !== 0) $allPass = false;
    $results[] = ['RefundAppeal::count()', $count, $status];
    echo sprintf("  RefundAppeal::count()   => %d %s\n", $count, $status);
} catch (Exception $e) {
    $results[] = ['RefundAppeal::count()', 'ERROR', '✗'];
    $allPass = false;
    echo sprintf("  RefundAppeal::count()   => ERROR: %s ✗\n", $e->getMessage());
}

// RefundRequest::all()
echo "\n  RefundRequest::all() test:\n";
try {
    $all = RefundRequest::all();
    echo sprintf("    ->all() returned %d record(s) %s\n", $all->count(), $all->count() === 0 ? '✓' : '⚠');
} catch (Exception $e) {
    echo sprintf("    ->all() ERROR: %s ✗\n", $e->getMessage());
    $allPass = false;
}

// Test query with where clause (verifies model + table work together)
echo "\n  Query tests (verifying model-to-table mapping):\n";
try {
    $pending = RefundRequest::where('status', 'pending')->count();
    echo sprintf("    RefundRequest::where('status','pending')->count() => %d ✓\n", $pending);
} catch (Exception $e) {
    echo sprintf("    Query test ERROR: %s ✗\n", $e->getMessage());
    $allPass = false;
}

try {
    $activePolicies = RefundPolicy::where('is_active', true)->count();
    echo sprintf("    RefundPolicy::where('is_active',true)->count()   => %d ✓\n", $activePolicies);
} catch (Exception $e) {
    echo sprintf("    Query test ERROR: %s ✗\n", $e->getMessage());
    $allPass = false;
}

try {
    $pendingAppeals = RefundAppeal::where('status', 'pending')->count();
    echo sprintf("    RefundAppeal::where('status','pending')->count() => %d ✓\n", $pendingAppeals);
} catch (Exception $e) {
    echo sprintf("    Query test ERROR: %s ✗\n", $e->getMessage());
    $allPass = false;
}

echo "\n============================================================\n";
if ($allPass) {
    echo " ✓ ALL ELOQUENT MODEL CHECKS PASSED\n";
} else {
    echo " ⚠ SOME CHECKS RETURNED NON-ZERO (see above)\n";
}
echo "============================================================\n";