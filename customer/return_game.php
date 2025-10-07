<?php
session_start();

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
            // Update borrow transaction
            $stmt = $pdo->prepare("UPDATE borrow_transactions SET return_date = NOW(), status = 'returned' WHERE id = ?");
            $stmt->execute([$transaction_id]);
            
            // Update game status
            $stmt = $pdo->prepare("UPDATE games SET status = 'available' WHERE id = ?");
            $stmt->execute([$transaction['game_id']]);
            
            $pdo->commit();
            
            // Redirect with success message
            header('Location: dashboard.php?message=Game returned successfully!');
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
    // If not POST request, redirect to dashboard
    header('Location: dashboard.php');
    exit();
}
?>
