<?php
require_once '../includes/session_config.php';

// Include authentication check
require_once '../includes/auth_check.php';

// Set security headers
setSecurityHeaders();

// Validate session and require admin access
validateSession();
requireAdmin();

require_once '../db/db_connect.php';

if($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['id'])) {
    $transaction_id = $_GET['id'];
    
    // Get transaction details
    $stmt = $pdo->prepare("SELECT bt.*, g.id as game_id, g.title, u.username 
                           FROM borrow_transactions bt 
                           JOIN games g ON bt.game_id = g.id 
                           JOIN users u ON bt.user_id = u.id 
                           WHERE bt.id = ? AND bt.status = 'borrowed'");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch();
    
    if($transaction) {
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Update borrow transaction
            $stmt = $pdo->prepare("UPDATE borrow_transactions SET return_date = NOW(), status = 'returned' WHERE id = ?");
            $stmt->execute([$transaction_id]);
            
            // Update game status
            $stmt = $pdo->prepare("UPDATE games SET status = 'available' WHERE id = ?");
            $stmt->execute([$transaction['game_id']]);
            
            $pdo->commit();
            
            // Redirect with success message
            header('Location: dashboard.php?message=Game returned successfully for ' . $transaction['username'] . '!');
            exit();
        } catch(Exception $e) {
            $pdo->rollback();
            header('Location: dashboard.php?error=Failed to return game. Please try again.');
            exit();
        }
    } else {
        header('Location: dashboard.php?error=Invalid transaction or game already returned.');
        exit();
    }
} else {
    // If not GET request or no ID, redirect to dashboard
    header('Location: dashboard.php');
    exit();
}
?>
