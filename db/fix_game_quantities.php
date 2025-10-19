<?php
/**
 * Fix Game Quantity Mismatch
 * This script corrects cases where available_quantity > total_quantity
 */

require_once __DIR__ . '/db_connect.php';

echo "<h2>Game Quantity Fix Script</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .warning { color: orange; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: blue; background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #667eea; color: white; }
</style>";

try {
    echo "<h3>Step 1: Identifying games with quantity issues...</h3>";
    
    // Find games where available > total
    $stmt = $pdo->query("
        SELECT id, title, platform, total_quantity, available_quantity, status
        FROM games 
        WHERE available_quantity > total_quantity
    ");
    $problematic_games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($problematic_games)) {
        echo "<div class='success'>✓ No quantity issues found! All games have valid quantities.</div>";
    } else {
        echo "<div class='warning'>⚠️ Found " . count($problematic_games) . " game(s) with quantity issues:</div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Platform</th><th>Total Qty</th><th>Available Qty</th><th>Status</th></tr>";
        foreach ($problematic_games as $game) {
            echo "<tr>";
            echo "<td>{$game['id']}</td>";
            echo "<td>{$game['title']}</td>";
            echo "<td>{$game['platform']}</td>";
            echo "<td>{$game['total_quantity']}</td>";
            echo "<td style='color: red; font-weight: bold;'>{$game['available_quantity']}</td>";
            echo "<td>{$game['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Step 2: Fixing quantity issues...</h3>";
        
        foreach ($problematic_games as $game) {
            $game_id = $game['id'];
            $title = $game['title'];
            
            // Calculate how many are actually borrowed
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as borrowed_count
                FROM borrow_transactions
                WHERE game_id = ? AND status = 'borrowed'
            ");
            $stmt->execute([$game_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $borrowed_count = $result['borrowed_count'];
            
            // Correct available_quantity
            $correct_available = $game['total_quantity'] - $borrowed_count;
            
            echo "<div class='info'>";
            echo "<strong>{$title}</strong>:<br>";
            echo "- Total Quantity: {$game['total_quantity']}<br>";
            echo "- Currently Borrowed: $borrowed_count<br>";
            echo "- Correct Available: $correct_available<br>";
            echo "- Wrong Available: {$game['available_quantity']}<br>";
            echo "</div>";
            
            // Update the game
            $stmt = $pdo->prepare("
                UPDATE games 
                SET available_quantity = ?,
                    status = CASE 
                        WHEN ? = 0 THEN 'borrowed' 
                        ELSE 'available' 
                    END
                WHERE id = ?
            ");
            $stmt->execute([$correct_available, $correct_available, $game_id]);
            
            echo "<div class='success'>✓ Fixed: {$title} - Available quantity set to $correct_available</div>";
        }
        
        echo "<h3>Step 3: Verification...</h3>";
        
        // Check if any issues remain
        $stmt = $pdo->query("
            SELECT COUNT(*) as count
            FROM games 
            WHERE available_quantity > total_quantity
        ");
        $remaining = $stmt->fetchColumn();
        
        if ($remaining == 0) {
            echo "<div class='success'><h2>✅ SUCCESS! All quantity issues have been fixed!</h2></div>";
        } else {
            echo "<div class='error'>⚠️ Warning: $remaining issue(s) remain. Manual intervention may be needed.</div>";
        }
    }
    
    // Show all games summary
    echo "<h3>All Games Summary:</h3>";
    $stmt = $pdo->query("
        SELECT id, title, platform, total_quantity, available_quantity, status,
               (SELECT COUNT(*) FROM borrow_transactions WHERE game_id = games.id AND status = 'borrowed') as borrowed_count
        FROM games
        ORDER BY title
    ");
    $all_games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Title</th><th>Platform</th><th>Total</th><th>Available</th><th>Borrowed</th><th>Status</th><th>Check</th></tr>";
    foreach ($all_games as $game) {
        $is_valid = ($game['available_quantity'] + $game['borrowed_count'] == $game['total_quantity']);
        $check_mark = $is_valid ? "✓" : "❌";
        $row_color = $is_valid ? "" : "background-color: #ffebee;";
        
        echo "<tr style='$row_color'>";
        echo "<td>{$game['id']}</td>";
        echo "<td>{$game['title']}</td>";
        echo "<td>{$game['platform']}</td>";
        echo "<td>{$game['total_quantity']}</td>";
        echo "<td>{$game['available_quantity']}</td>";
        echo "<td>{$game['borrowed_count']}</td>";
        echo "<td>{$game['status']}</td>";
        echo "<td>$check_mark</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='font-size: 0.9em; color: #666;'>";
    echo "Formula: Available + Borrowed = Total<br>";
    echo "✓ = Quantities match correctly<br>";
    echo "❌ = Quantities don't match (needs manual review)";
    echo "</p>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='../customer/dashboard.php'>Back to Dashboard</a> | <a href='../admin/games.php'>Manage Games</a></p>";
?>
