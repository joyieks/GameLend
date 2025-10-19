<?php
/**
 * Debug Return Game Functionality
 */

session_start();
require_once __DIR__ . '/db/db_connect.php';

echo "<h2>Return Game Debug</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: blue; background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #667eea; color: white; }
</style>";

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div class='error'>❌ Not logged in! Please <a href='login.php'>login first</a>.</div>";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "<div class='info'>Logged in as User ID: $user_id (Email: {$_SESSION['email']})</div>";

// Get borrowed games
echo "<h3>Step 1: Finding your borrowed games...</h3>";
try {
    $stmt = $pdo->prepare("SELECT bt.id as transaction_id, bt.borrow_date, bt.due_date, bt.status, 
                           g.id as game_id, g.title, g.platform, g.available_quantity, g.total_quantity
                           FROM borrow_transactions bt 
                           JOIN games g ON bt.game_id = g.id 
                           WHERE bt.user_id = ? AND bt.status = 'borrowed'");
    $stmt->execute([$user_id]);
    $borrowed = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($borrowed)) {
        echo "<div class='error'>❌ You have no borrowed games to return!</div>";
        echo "<p><a href='customer/games.php'>Borrow a game first</a></p>";
        exit;
    }
    
    echo "<div class='success'>✓ Found " . count($borrowed) . " borrowed game(s)</div>";
    echo "<table>";
    echo "<tr><th>Transaction ID</th><th>Game ID</th><th>Title</th><th>Platform</th><th>Borrowed Date</th><th>Status</th></tr>";
    foreach ($borrowed as $b) {
        echo "<tr>";
        echo "<td>{$b['transaction_id']}</td>";
        echo "<td>{$b['game_id']}</td>";
        echo "<td>{$b['title']}</td>";
        echo "<td>{$b['platform']}</td>";
        echo "<td>{$b['borrow_date']}</td>";
        echo "<td>{$b['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Use first borrowed game for testing
    $test_transaction = $borrowed[0];
    $transaction_id = $test_transaction['transaction_id'];
    $game_id = $test_transaction['game_id'];
    
    echo "<div class='info'>Will test return with Transaction ID: $transaction_id, Game ID: $game_id</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error fetching borrowed games: " . $e->getMessage() . "</div>";
    exit;
}

// Verify transaction ownership
echo "<h3>Step 2: Verifying transaction ownership...</h3>";
try {
    $stmt = $pdo->prepare("SELECT bt.*, g.id as game_id, g.title 
                           FROM borrow_transactions bt 
                           JOIN games g ON bt.game_id = g.id 
                           WHERE bt.id = ? AND bt.user_id = ? AND bt.status = 'borrowed'");
    $stmt->execute([$transaction_id, $user_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaction) {
        echo "<div class='error'>❌ Transaction not found or already returned!</div>";
        exit;
    }
    
    echo "<div class='success'>✓ Transaction verified</div>";
    echo "<pre>Transaction Details: " . print_r($transaction, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error verifying transaction: " . $e->getMessage() . "</div>";
    exit;
}

// Test return process
echo "<h3>Step 3: Attempting to return game...</h3>";
try {
    $pdo->beginTransaction();
    
    echo "<div class='info'>Starting transaction...</div>";
    
    // Update borrow transaction
    echo "<div class='info'>Updating borrow_transactions table...</div>";
    $stmt = $pdo->prepare("UPDATE borrow_transactions SET return_date = NOW(), status = 'returned' WHERE id = ?");
    $updateTransaction = $stmt->execute([$transaction_id]);
    
    if (!$updateTransaction) {
        throw new Exception("Failed to update transaction status. Error: " . print_r($stmt->errorInfo(), true));
    }
    echo "<div class='success'>✓ Transaction updated to 'returned'</div>";
    
    // Update game status and increase available quantity
    echo "<div class='info'>Updating games table...</div>";
    $stmt = $pdo->prepare("UPDATE games SET available_quantity = available_quantity + 1, status = CASE WHEN available_quantity + 1 > 0 THEN 'available' ELSE status END WHERE id = ?");
    $updateGame = $stmt->execute([$transaction['game_id']]);
    
    if (!$updateGame) {
        throw new Exception("Failed to update game availability. Error: " . print_r($stmt->errorInfo(), true));
    }
    echo "<div class='success'>✓ Game availability updated</div>";
    
    $pdo->commit();
    echo "<div class='success'>✓✓ Transaction committed successfully!</div>";
    
    // Verify changes
    echo "<h3>Step 4: Verifying changes...</h3>";
    
    // Check transaction status
    $stmt = $pdo->prepare("SELECT * FROM borrow_transactions WHERE id = ?");
    $stmt->execute([$transaction_id]);
    $updated_transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>Updated Transaction Status: {$updated_transaction['status']}</div>";
    echo "<div class='info'>Return Date: {$updated_transaction['return_date']}</div>";
    
    // Check game availability
    $stmt = $pdo->prepare("SELECT available_quantity, total_quantity, status FROM games WHERE id = ?");
    $stmt->execute([$transaction['game_id']]);
    $updated_game = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>Updated Game Availability: {$updated_game['available_quantity']} / {$updated_game['total_quantity']}</div>";
    echo "<div class='info'>Game Status: {$updated_game['status']}</div>";
    
    echo "<hr>";
    echo "<div class='success'><h2>✅ SUCCESS! Return process completed successfully!</h2></div>";
    echo "<p><a href='customer/borrowed.php'>View Borrowed Games</a> | <a href='customer/dashboard.php'>Dashboard</a></p>";
    
} catch (Exception $e) {
    $pdo->rollback();
    echo "<div class='error'>❌ RETURN FAILED!</div>";
    echo "<div class='error'><strong>Error:</strong> " . $e->getMessage() . "</div>";
    echo "<pre><strong>Stack Trace:</strong>\n" . $e->getTraceAsString() . "</pre>";
    
    // Additional debugging
    echo "<h3>Additional Debug Info:</h3>";
    echo "<pre>PDO Error Info: " . print_r($pdo->errorInfo(), true) . "</pre>";
}

echo "<hr>";
echo "<p><a href='customer/dashboard.php'>Back to Dashboard</a></p>";
?>
