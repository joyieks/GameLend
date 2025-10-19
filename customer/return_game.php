<?php
require_once '../includes/session_config.php';

// Include authentication check
require_once '../includes/auth_check.php';

// Set security headers
setSecurityHeaders();

// Validate session and require customer access
validateSession();
requireCustomer();

require_once '../db/db_connect.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction_id'])) {
    $transaction_id = $_POST['transaction_id'];
    $user_id = $_SESSION['user_id'];
    
    // Verify the transaction belongs to the current user
    $stmt = $pdo->prepare("SELECT bt.*, g.id as game_id FROM borrow_transactions bt 
                           JOIN games g ON bt.game_id = g.id 
                           WHERE bt.id = ? AND bt.user_id = ? AND bt.status = 'borrowed'");
    $stmt->execute([$transaction_id, $user_id]);
    $transaction = $stmt->fetch();
    
    if($transaction) {
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Get current game quantities to verify constraint
            $stmt = $pdo->prepare("SELECT total_quantity, available_quantity FROM games WHERE id = ?");
            $stmt->execute([$transaction['game_id']]);
            $game_data = $stmt->fetch();
            
            // Check if incrementing would violate constraint
            if ($game_data['available_quantity'] + 1 > $game_data['total_quantity']) {
                throw new Exception("Cannot return game: available quantity would exceed total quantity. Please contact admin.");
            }
            
            // Update borrow transaction
            $stmt = $pdo->prepare("UPDATE borrow_transactions SET return_date = NOW(), status = 'returned' WHERE id = ?");
            $updateTransaction = $stmt->execute([$transaction_id]);
            
            if (!$updateTransaction) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Failed to update transaction. SQLSTATE: {$errorInfo[0]}, Error: {$errorInfo[2]}");
            }
            
            // Manually update game availability (don't rely on trigger)
            $stmt = $pdo->prepare("UPDATE games SET available_quantity = available_quantity + 1, status = CASE WHEN available_quantity + 1 > 0 THEN 'available' ELSE status END WHERE id = ?");
            $updateGame = $stmt->execute([$transaction['game_id']]);
            
            if (!$updateGame) {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Failed to update game. SQLSTATE: {$errorInfo[0]}, Error: {$errorInfo[2]}");
            }
            
            $pdo->commit();
            
            // Redirect with success message
            header('Location: dashboard.php?message=Game returned successfully!');
            exit();
        } catch(Exception $e) {
            $pdo->rollback();
            error_log("Return Error - Transaction: $transaction_id, User: $user_id, Game: {$transaction['game_id']}, Error: " . $e->getMessage());
            header('Location: dashboard.php?error=' . urlencode($e->getMessage()));
            exit();
        }
    } else {
        header('Location: dashboard.php?error=Invalid transaction or game already returned.');
        exit();
    }
} else {
    // If not POST request, redirect to dashboard
    header('Location: dashboard.php');
    exit();
}
?>
