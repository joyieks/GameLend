<?php
/**
 * Row Level Security (RLS) Context Setup
 * 
 * This file provides functions to set up the PostgreSQL session context
 * for Row Level Security policies to work correctly with your application.
 * 
 * Call setRLSContext() after connecting to the database and authenticating the user.
 */

/**
 * Set the RLS context for the current database session
 * 
 * @param PDO $pdo Database connection
 * @param int $userId The authenticated user's ID
 * @return bool Success status
 */
function setRLSContext($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SET LOCAL app.user_id = ?");
        $stmt->execute([$userId]);
        return true;
    } catch (PDOException $e) {
        error_log("Failed to set RLS context: " . $e->getMessage());
        return false;
    }
}

/**
 * Clear the RLS context (useful for logout or session cleanup)
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function clearRLSContext($pdo) {
    try {
        $pdo->exec("RESET app.user_id");
        return true;
    } catch (PDOException $e) {
        error_log("Failed to clear RLS context: " . $e->getMessage());
        return false;
    }
}

/**
 * Initialize RLS context from session
 * Call this function after user authentication
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function initializeRLSFromSession($pdo) {
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return setRLSContext($pdo, $_SESSION['user_id']);
    }
    return false;
}

/**
 * Bypass RLS for admin operations (use with caution!)
 * This disables RLS for the current session
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function bypassRLS($pdo) {
    try {
        $pdo->exec("SET LOCAL row_security = off");
        return true;
    } catch (PDOException $e) {
        error_log("Failed to bypass RLS: " . $e->getMessage());
        return false;
    }
}

/**
 * Re-enable RLS after bypass
 * 
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function enableRLS($pdo) {
    try {
        $pdo->exec("SET LOCAL row_security = on");
        return true;
    } catch (PDOException $e) {
        error_log("Failed to enable RLS: " . $e->getMessage());
        return false;
    }
}
