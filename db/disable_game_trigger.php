<?php
/**
 * Disable Game Availability Trigger
 * The trigger was causing double updates when combined with manual PHP updates
 */

require_once __DIR__ . '/db_connect.php';

echo "<h2>Disable Trigger Script</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; background: #e8f5e9; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { color: red; background: #ffebee; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .info { color: blue; background: #e3f2fd; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

try {
    echo "<h3>Step 1: Checking if trigger exists...</h3>";
    
    $stmt = $pdo->query("
        SELECT trigger_name 
        FROM information_schema.triggers 
        WHERE trigger_name = 'trigger_update_game_availability'
    ");
    $trigger = $stmt->fetch();
    
    if ($trigger) {
        echo "<div class='info'>Found trigger: trigger_update_game_availability</div>";
        
        echo "<h3>Step 2: Dropping trigger...</h3>";
        $pdo->exec("DROP TRIGGER IF EXISTS trigger_update_game_availability ON borrow_transactions");
        echo "<div class='success'>✓ Trigger dropped successfully</div>";
        
        echo "<h3>Step 3: Dropping function...</h3>";
        $pdo->exec("DROP FUNCTION IF EXISTS update_game_availability()");
        echo "<div class='success'>✓ Function dropped successfully</div>";
        
    } else {
        echo "<div class='info'>Trigger does not exist (already removed or never created)</div>";
    }
    
    // Verify removal
    echo "<h3>Step 4: Verifying removal...</h3>";
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM information_schema.triggers 
        WHERE trigger_name = 'trigger_update_game_availability'
    ");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "<div class='success'><h2>✅ SUCCESS! Trigger has been disabled.</h2></div>";
        echo "<div class='info'>Game availability updates are now handled manually in PHP code for better control.</div>";
    } else {
        echo "<div class='error'>⚠️ Trigger still exists. Manual intervention may be needed.</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><strong>What this means:</strong></p>";
echo "<ul>";
echo "<li>✓ The trigger that was causing double updates is now disabled</li>";
echo "<li>✓ Game quantities will be updated manually by PHP code</li>";
echo "<li>✓ You have full control over when and how quantities change</li>";
echo "<li>✓ Better error handling and logging</li>";
echo "</ul>";

echo "<p><a href='../customer/dashboard.php'>Back to Dashboard</a> | <a href='fix_game_quantities.php'>Fix Quantities Again</a></p>";
?>
