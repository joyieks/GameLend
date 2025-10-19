<?php
/**
 * Debug Borrow Functionality
 * This script helps identify why borrowing is failing
 */

session_start();
require_once __DIR__ . '/db/db_connect.php';
require_once __DIR__ . '/includes/settings_helper.php';

echo "<h2>Borrow Debug Information</h2>";

// Check session
echo "<h3>1. Session Check</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✓ User ID in session: " . $_SESSION['user_id'] . "<br>";
    echo "✓ Email: " . ($_SESSION['email'] ?? 'Not set') . "<br>";
    echo "✓ Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
} else {
    echo "❌ No user_id in session! You need to log in first.<br>";
    echo "<a href='login.php'>Go to Login</a><br>";
    exit;
}

// Check settings
echo "<h3>2. Settings Check</h3>";
try {
    $borrow_duration = getBorrowDuration();
    echo "✓ Borrow Duration: $borrow_duration days<br>";
    
    $late_fee = getLateFeePerDay();
    echo "✓ Late Fee: $" . number_format($late_fee, 2) . " per day<br>";
} catch (Exception $e) {
    echo "❌ Settings Error: " . $e->getMessage() . "<br>";
}

// Check available games
echo "<h3>3. Available Games Check</h3>";
try {
    $stmt = $pdo->query("SELECT id, title, platform, available_quantity, status FROM games WHERE available_quantity > 0 AND status != 'maintenance' LIMIT 5");
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($games) > 0) {
        echo "✓ Found " . count($games) . " available games:<br>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Platform</th><th>Available</th><th>Status</th></tr>";
        foreach ($games as $game) {
            echo "<tr>";
            echo "<td>{$game['id']}</td>";
            echo "<td>{$game['title']}</td>";
            echo "<td>{$game['platform']}</td>";
            echo "<td>{$game['available_quantity']}</td>";
            echo "<td>{$game['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ No available games found<br>";
    }
} catch (Exception $e) {
    echo "❌ Games Query Error: " . $e->getMessage() . "<br>";
}

// Check if user already has games borrowed
echo "<h3>4. Current Borrowed Games</h3>";
try {
    $stmt = $pdo->prepare("SELECT bt.*, g.title FROM borrow_transactions bt 
                          JOIN games g ON bt.game_id = g.id 
                          WHERE bt.user_id = ? AND bt.status = 'borrowed'");
    $stmt->execute([$_SESSION['user_id']]);
    $borrowed = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($borrowed) > 0) {
        echo "✓ You have " . count($borrowed) . " games borrowed:<br>";
        foreach ($borrowed as $b) {
            echo "- {$b['title']} (Due: {$b['due_date']})<br>";
        }
    } else {
        echo "✓ You have no games currently borrowed<br>";
    }
} catch (Exception $e) {
    echo "❌ Borrow Query Error: " . $e->getMessage() . "<br>";
}

// Test borrow logic
echo "<h3>5. Test Borrow Logic</h3>";
echo "<p>Attempting a test borrow calculation...</p>";

try {
    $borrow_duration = getBorrowDuration();
    $due_date = date('Y-m-d H:i:s', strtotime("+$borrow_duration days"));
    echo "✓ Due date calculation works: $due_date<br>";
    echo "✓ (Current time + $borrow_duration days)<br>";
} catch (Exception $e) {
    echo "❌ Due Date Calculation Error: " . $e->getMessage() . "<br>";
}

echo "<h3>6. Database Connection</h3>";
try {
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "✓ PostgreSQL Connection OK<br>";
    echo "✓ Version: $version<br>";
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><a href='customer/games.php'>Back to Games</a> | <a href='customer/dashboard.php'>Dashboard</a></p>";
?>
