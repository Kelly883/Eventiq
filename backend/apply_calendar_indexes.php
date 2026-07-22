<?php

// Direct database connection to SQLite
$dbPath = __DIR__ . '/database/database.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== APPLYING CALENDAR INDEXES ===\n\n";

try {
    // Create indexes on events table
    echo "1. Creating indexes on events table...\n";
    
    $indexes = [
        'idx_events_status_date' => 'CREATE INDEX idx_events_status_date ON events (status, start_datetime)',
        'idx_events_status_organizer' => 'CREATE INDEX idx_events_status_organizer ON events (status, organizer_id)',
    ];
    
    foreach ($indexes as $name => $sql) {
        try {
            $db->exec($sql);
            echo "   ✓ Created index: $name\n";
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'already exists')) {
                echo "   ✓ Index already exists: $name\n";
            } else {
                echo "   ✗ Error creating $name: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Create index on start_datetime
    try {
        $db->exec('CREATE INDEX events_start_datetime_index ON events (start_datetime)');
        echo "   ✓ Created index: events_start_datetime_index\n";
    } catch (Exception $e) {
        if (str_contains($e->getMessage(), 'already exists')) {
            echo "   ✓ Index already exists: events_start_datetime_index\n";
        } else {
            echo "   ✗ Error creating start_datetime index: " . $e->getMessage() . "\n";
        }
    }
    
    // Create database view
    echo "\n2. Creating database view (events_by_date)...\n";
    $db->exec("DROP VIEW IF EXISTS events_by_date");
    $viewSQL = "CREATE VIEW events_by_date AS
        SELECT 
            DATE(start_datetime) as event_date,
            COUNT(*) as total_events,
            SUM(capacity) as total_capacity,
            SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_events,
            SUM(CASE WHEN status = 'published' THEN capacity ELSE 0 END) as published_capacity
        FROM events
        WHERE start_datetime IS NOT NULL
        GROUP BY DATE(start_datetime)
        ORDER BY event_date
    ";
    
    $db->exec($viewSQL);
    echo "   ✓ View created successfully\n";
    
    // Verify indexes
    echo "\n3. Verifying indexes...\n";
    $stmt = $db->query("SELECT name, sql FROM sqlite_master WHERE type='index' AND tbl_name='events' AND sql IS NOT NULL");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($indexes) > 0) {
        echo "   ✓ Found " . count($indexes) . " indexes on events table:\n";
        foreach ($indexes as $index) {
            echo "     - {$index['name']}\n";
        }
    } else {
        echo "   ✗ No indexes found\n";
    }
    
    // Verify view
    echo "\n4. Verifying view...\n";
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='view' AND name='events_by_date'");
    $view = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($view) {
        echo "   ✓ View 'events_by_date' exists\n";
        
        // Test the view
        echo "\n5. Testing view query...\n";
        $start = microtime(true);
        $stmt = $db->query("SELECT * FROM events_by_date LIMIT 5");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $duration = (microtime(true) - $start) * 1000;
        
        echo "   ✓ Query completed in " . round($duration, 2) . "ms\n";
        echo "   Sample results:\n";
        foreach ($results as $row) {
            echo "     - {$row['event_date']}: {$row['total_events']} events, {$row['published_events']} published\n";
        }
    } else {
        echo "   ✗ View not found\n";
    }
    
    // Test performance with EXPLAIN
    echo "\n6. Testing query performance...\n";
    
    // Test 1: Published events by date range
    echo "\n   Test 1: Published events in March 2024\n";
    $start = microtime(true);
    $stmt = $db->query("
        EXPLAIN QUERY PLAN
        SELECT * FROM events 
        WHERE status = 'published' 
        AND start_datetime >= '2024-03-01' 
        AND start_datetime < '2024-04-01'
    ");
    $plan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $duration = (microtime(true) - $start) * 1000;
    
    echo "   Plan:\n";
    foreach ($plan as $row) {
        echo "     {$row['detail']}\n";
    }
    echo "   ✓ Completed in " . round($duration, 2) . "ms\n";
    
    echo "\n=== SUCCESS ===\n";
    echo "All indexes and view have been created successfully.\n";
    echo "The database is now optimized for calendar queries.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

$db = null;
echo "\n=== COMPLETE ===\n";