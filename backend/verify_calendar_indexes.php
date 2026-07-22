<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== VERIFYING CALENDAR INDEXES ===\n\n";

// Check if migration was run by verifying the view exists
$viewExists = DB::select("SELECT name FROM sqlite_master WHERE type='view' AND name='events_by_date'");
echo "1. Migration Status:\n";
if ($viewExists) {
    echo "   ✓ Migration ran successfully - events_by_date view exists\n";
} else {
    echo "   ✗ Migration not yet run - events_by_date view missing\n";
    echo "   Run: php artisan migrate --path=database/migrations/2026_07_22_065001_add_calendar_indexes_to_events_table.php\n";
    exit(1);
}

// Verify indexes on events table
echo "\n2. Indexes on events table:\n";
$indexes = DB::select("SELECT name, sql FROM sqlite_master WHERE type='index' AND tbl_name='events' AND sql IS NOT NULL");
foreach ($indexes as $index) {
    echo "   ✓ {$index->name}\n";
    echo "     SQL: {$index->sql}\n";
}

if (empty($indexes)) {
    echo "   ✗ No indexes found on events table\n";
}

// Verify database view
echo "\n3. Database View (events_by_date):\n";
$viewInfo = DB::select("SELECT sql FROM sqlite_master WHERE type='view' AND name='events_by_date'");
if ($viewInfo) {
    echo "   ✓ View exists\n";
    echo "   Definition:\n";
    echo "   " . $viewInfo[0]->sql . "\n";
    
    // Test the view
    echo "\n   Testing view query:\n";
    $start = microtime(true);
    $result = DB::select("SELECT * FROM events_by_date LIMIT 5");
    $duration = (microtime(true) - $start) * 1000;
    echo "   ✓ Query completed in " . round($duration, 2) . "ms\n";
    echo "   Sample results:\n";
    foreach ($result as $row) {
        echo "     - {$row->event_date}: {$row->total_events} events, {$row->published_events} published\n";
    }
}

// Test query performance with EXPLAIN
echo "\n4. Query Performance Analysis:\n";

// Test 1: Fetch published events in March 2024
echo "\n   Test 1: Fetch published events in March 2024\n";
$start = microtime(true);
$explain = DB::select("
    EXPLAIN QUERY PLAN
    SELECT * FROM events 
    WHERE status = 'published' 
    AND start_datetime >= '2024-03-01' 
    AND start_datetime < '2024-04-01'
");
$duration = (microtime(true) - $start) * 1000;
echo "   Query plan:\n";
foreach ($explain as $row) {
    echo "     {$row->detail}\n";
}
echo "   ✓ EXPLAIN completed in " . round($duration, 2) . "ms\n";

// Test 2: Fetch events by category (if category_id exists)
$hasCategoryId = Schema::hasColumn('events', 'category_id');
if ($hasCategoryId) {
    echo "\n   Test 2: Fetch events by category\n";
    $start = microtime(true);
    $explain = DB::select("
        EXPLAIN QUERY PLAN
        SELECT * FROM events 
        WHERE category_id = 1
        AND status = 'published'
    ");
    $duration = (microtime(true) - $start) * 1000;
    echo "   Query plan:\n";
    foreach ($explain as $row) {
        echo "     {$row->detail}\n";
    }
    echo "   ✓ EXPLAIN completed in " . round($duration, 2) . "ms\n";
} else {
    echo "\n   Test 2: SKIPPED - category_id column not found\n";
}

// Test 3: Joining with ticket_inventory
echo "\n   Test 3: Join events with ticket_inventory\n";
$hasInventory = Schema::hasTable('ticket_inventory');
if ($hasInventory) {
    $start = microtime(true);
    $explain = DB::select("
        EXPLAIN QUERY PLAN
        SELECT e.*, ti.quantity 
        FROM events e
        INNER JOIN ticket_inventory ti ON e.id = ti.event_id
        WHERE e.status = 'published'
        LIMIT 10
    ");
    $duration = (microtime(true) - $start) * 1000;
    echo "   Query plan:\n";
    foreach ($explain as $row) {
        echo "     {$row->detail}\n";
    }
    echo "   ✓ EXPLAIN completed in " . round($duration, 2) . "ms\n";
} else {
    echo "   SKIPPED - ticket_inventory table not found\n";
}

// Test 4: Joining with pricing_windows
echo "\n   Test 4: Join events with pricing_windows\n";
$hasPricingWindows = Schema::hasTable('pricing_windows');
if ($hasPricingWindows) {
    $start = microtime(true);
    $explain = DB::select("
        EXPLAIN QUERY PLAN
        SELECT e.*, pw.price 
        FROM events e
        INNER JOIN pricing_windows pw ON e.id = pw.event_id
        WHERE e.status = 'published'
        LIMIT 10
    ");
    $duration = (microtime(true) - $start) * 1000;
    echo "   Query plan:\n";
    foreach ($explain as $row) {
        echo "     {$row->detail}\n";
    }
    echo "   ✓ EXPLAIN completed in " . round($duration, 2) . "ms\n";
} else {
    echo "   SKIPPED - pricing_windows table not found\n";
}

// Summary
echo "\n=== SUMMARY ===\n";
echo "✓ Indexes created:\n";
foreach ($indexes as $index) {
    echo "  - {$index->name}\n";
}
echo "\n✓ Database view created: events_by_date\n";
echo "\nNext steps:\n";
echo "1. Run actual queries to measure performance\n";
echo "2. Check that calendar queries complete in < 100ms\n";
echo "3. Test with thousands of events to ensure scalability\n";
echo "\n=== VERIFICATION COMPLETE ===\n";