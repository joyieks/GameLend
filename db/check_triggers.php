<?php
/**
 * Check Database Trigger Status
 */

require_once __DIR__ . '/db_connect.php';

echo "<h2>Database Trigger Diagnostic</h2>";

try {
    // Check if trigger exists
    echo "<h3>1. Checking Trigger Existence</h3>";
    $stmt = $pdo->query("
        SELECT trigger_name, event_manipulation, event_object_table, action_statement, action_timing
        FROM information_schema.triggers 
        WHERE trigger_name LIKE '%game_availability%'
    ");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($triggers) > 0) {
        echo "✓ Found " . count($triggers) . " trigger(s):<br>";
        foreach ($triggers as $trigger) {
            echo "<pre>";
            print_r($trigger);
            echo "</pre>";
        }
    } else {
        echo "⚠️ WARNING: No game availability trigger found!<br>";
        echo "This means game quantities won't update automatically when borrowing.<br>";
    }
    
    // Check trigger function
    echo "<h3>2. Checking Trigger Function</h3>";
    $stmt = $pdo->query("
        SELECT routine_name, routine_definition
        FROM information_schema.routines
        WHERE routine_name LIKE '%game_availability%'
    ");
    $functions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($functions) > 0) {
        echo "✓ Found trigger function(s):<br>";
        foreach ($functions as $func) {
            echo "<strong>Function: " . $func['routine_name'] . "</strong><br>";
        }
    } else {
        echo "⚠️ WARNING: Trigger function not found!<br>";
    }
    
    // Test a simple insert to see if it would work
    echo "<h3>3. Testing Borrow Transaction Schema</h3>";
    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns
        WHERE table_name = 'borrow_transactions'
        ORDER BY ordinal_position
    ");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Column</th><th>Type</th><th>Nullable</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['column_name']}</td>";
        echo "<td>{$col['data_type']}</td>";
        echo "<td>{$col['is_nullable']}</td>";
        echo "<td>{$col['column_default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check games table
    echo "<h3>4. Games Table Structure</h3>";
    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable
        FROM information_schema.columns
        WHERE table_name = 'games'
        ORDER BY ordinal_position
    ");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Column</th><th>Type</th><th>Nullable</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['column_name']}</td>";
        echo "<td>{$col['data_type']}</td>";
        echo "<td>{$col['is_nullable']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check for any constraints that might fail
    echo "<h3>5. Table Constraints</h3>";
    $stmt = $pdo->query("
        SELECT tc.constraint_name, tc.constraint_type, kcu.column_name
        FROM information_schema.table_constraints tc
        JOIN information_schema.key_column_usage kcu 
            ON tc.constraint_name = kcu.constraint_name
        WHERE tc.table_name IN ('borrow_transactions', 'games')
        ORDER BY tc.table_name, tc.constraint_type
    ");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Constraint</th><th>Type</th><th>Column</th></tr>";
    foreach ($constraints as $con) {
        echo "<tr>";
        echo "<td>{$con['constraint_name']}</td>";
        echo "<td>{$con['constraint_type']}</td>";
        echo "<td>{$con['column_name']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='../debug_borrow.php'>Back to Borrow Debug</a></p>";
?>
