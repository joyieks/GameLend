<?php
/**
 * Test Borrow Transaction - Shows Exact Error
 */

session_start();
require_once __DIR__ . '/db/db_connect.php';
require_once __DIR__ . '/includes/settings_helper.php';

echo "<h2>Test Borrow Transaction</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; }
    .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; }
    .info { color: blue; background: #e3f2fd; padding: 10px; border-radius: 5px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div class='error'>❌ Not logged in! Please <a href='login.php'>login first</a>.</div>";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "<div class='info'>Testing with User ID: $user_id (Email: {$_SESSION['email']})</div>";

// Get a test game
echo "<h3>Step 1: Finding an available game...</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM games WHERE available_quantity > 0 AND status != 'maintenance' LIMIT 1");
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$game) {
        echo "<div class='error'>❌ No available games found!</div>";
        exit;
    }
    
    echo "<div class='success'>✓ Found game: {$game['title']} (Platform: {$game['platform']})</div>";
    echo "<pre>Game Details: " . print_r($game, true) . "</pre>";
    
    $game_id = $game['id'];
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error finding game: " . $e->getMessage() . "</div>";
    exit;
}

// Check if already borrowed
echo "<h3>Step 2: Checking if already borrowed...</h3>";
try {
    $stmt = $pdo->prepare("SELECT id FROM borrow_transactions WHERE user_id = ? AND game_id = ? AND status = 'borrowed'");
    $stmt->execute([$user_id, $game_id]);
    
    if ($stmt->rowCount() > 0) {
        echo "<div class='error'>⚠️ You already have this game borrowed!</div>";
        exit;
    }
    
    echo "<div class='success'>✓ Game not currently borrowed by you</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error checking borrow status: " . $e->getMessage() . "</div>";
    exit;
}

// Get borrow duration
echo "<h3>Step 3: Getting borrow duration...</h3>";
try {
    $borrow_duration = getBorrowDuration();
    echo "<div class='success'>✓ Borrow duration: $borrow_duration days</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error getting settings: " . $e->getMessage() . "</div>";
    echo "<div class='info'>Falling back to default: 14 days</div>";
    $borrow_duration = 14;
}

// Calculate due date
echo "<h3>Step 4: Calculating due date...</h3>";
try {
    $due_date = date('Y-m-d H:i:s', strtotime("+$borrow_duration days"));
    echo "<div class='success'>✓ Due date: $due_date</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error calculating date: " . $e->getMessage() . "</div>";
    exit;
}

// Attempt to borrow
echo "<h3>Step 5: Attempting to insert borrow transaction...</h3>";
echo "<div class='info'>SQL: INSERT INTO borrow_transactions (user_id, game_id, borrow_date, due_date, status) VALUES ($user_id, $game_id, NOW(), '$due_date', 'borrowed')</div>";

try {
    $pdo->beginTransaction();
    
    // Insert borrow transaction
    $stmt = $pdo->prepare("INSERT INTO borrow_transactions (user_id, game_id, borrow_date, due_date, status) VALUES (?, ?, NOW(), ?, 'borrowed')");
    $result = $stmt->execute([$user_id, $game_id, $due_date]);
    
    if ($result) {
        echo "<div class='success'>✓ Borrow transaction inserted successfully!</div>";
        echo "<div class='info'>Transaction ID: " . $pdo->lastInsertId() . "</div>";
    } else {
        echo "<div class='error'>❌ Insert returned false</div>";
        echo "<pre>Error Info: " . print_r($stmt->errorInfo(), true) . "</pre>";
    }
    
    $pdo->commit();
    
    echo "<div class='success'>✓✓ Transaction committed! Borrow successful!</div>";
    
    // Check updated game quantity
    $stmt = $pdo->prepare("SELECT available_quantity, status FROM games WHERE id = ?");
    $stmt->execute([$game_id]);
    $updated_game = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>Updated game quantities: Available = {$updated_game['available_quantity']}, Status = {$updated_game['status']}</div>";
    
    echo "<hr>";
    echo "<p><strong>✅ SUCCESS!</strong> The borrow worked correctly.</p>";
    echo "<p><a href='customer/borrowed.php'>View Your Borrowed Games</a></p>";
    
} catch (Exception $e) {
    $pdo->rollback();
    echo "<div class='error'>❌ BORROW FAILED!</div>";
    echo "<div class='error'><strong>Error Message:</strong> " . $e->getMessage() . "</div>";
    echo "<div class='error'><strong>Error Code:</strong> " . $e->getCode() . "</div>";
    echo "<pre><strong>Stack Trace:</strong>\n" . $e->getTraceAsString() . "</pre>";
    
    // Try to get more details
    $errorInfo = $pdo->errorInfo();
    if ($errorInfo[0] != '00000') {
        echo "<pre><strong>PDO Error Info:</strong>\n" . print_r($errorInfo, true) . "</pre>";
    }
}

echo "<hr>";
echo "<p><a href='debug_borrow.php'>Back to Debug Page</a> | <a href='customer/games.php'>Back to Games</a></p>";
?>
