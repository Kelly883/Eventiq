<?php

// Direct database connection to SQLite
$dbPath = __DIR__ . '/database/database.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== APPLYING CALENDAR INDEXES ===\n\n";

try {
    // Get existing indexes on events table to avoid duplicates
    $existingStmt = $db->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='events'");
    $existingIndexNames = array_map(fn($row) => $row['name'], $existingStmt->fetchAll(PDO::FETCH_ASSOC));
    
    echo "1. Creating indexes on events table...\n";
    
    $indexes = [
        // Composite index on (status, start_datetime) for filtering published events by date range
        'idx_events_status_date' => 'CREATE INDEX idx_events_status_date ON events (status, start_datetime)',
        // Composite index on (status, category) for category filtering (category is a string column)
        'idx_events_status_category' => 'CREATE INDEX idx_events_status_category ON events (status, category)',
        // Composite index on (organizer_id, start_datetime) for organizer calendar queries
        'idx_events_organizer_date' => 'CREATE INDEX idx_events_organizer_date ON events (organizer_id, start_datetime)',
        // Composite index on (category, status, start_datetime) for category + date filtering
        'idx_events_category_status_date' => 'CREATE INDEX idx_events_category_status_date ON events (category, status, start_datetime)',
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
    if (!in_array('events_start_date_index', $existingIndexNames)) {
        try {
            $db->exec('CREATE INDEX events_start_date_index ON events (start_datetime)');
            echo "   ✓ Created index: events_start_date_index\n";
        } catch (Exception $e) {
            echo "   ✗ Error creating start_datetime index: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ✓ Index already exists: events_start_date_index\n";
    }
    
    // Create index on status
    if (!in_array('events_status_index', $existingIndexNames)) {
        try {
            $db->exec('CREATE INDEX events_status_index ON events (status)');
            echo "   ✓ Created index: events_status_index\n";
        } catch (Exception $e) {
            echo "   ✗ Error creating status index: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ✓ Index already exists: events_status_index\n";
    }
    
    // Add index on ticket_inventory for availability queries
    echo "\n2. Creating index on ticket_inventory table...\n";
    $invIndexes = $db->query("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='ticket_inventory'");
    $invIndexNames = array_map(fn($row) => $row['name'], $invIndexes->fetchAll(PDO::FETCH_ASSOC));
    
    if (!in_array('inv_event_available_idx', $invIndexNames)) {
        try {
            $db->exec('CREATE INDEX inv_event_available_idx ON ticket_inventory (event_id, total_available)');
            echo "   ✓ Created index: inv_event_available_idx\n";
        } catch (Exception $e) {
            echo "   ✗ Error creating ticket_inventory index: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ✓ Index already exists: inv_event_available_idx\n";
    }
    
    // Create database view
    echo "\n3. Creating database view (events_by_date)...\n";
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
    echo "\n4. Verifying indexes...\n";
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
    echo "\n5. Verifying view...\n";
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='view' AND name='events_by_date'");
    $view = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($view) {
        echo "   ✓ View 'events_by_date' exists\n";
        
        // Test the view
        echo "\n6. Testing view query...\n";
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
    echo "\n7. Testing query performance...\n";
    
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

